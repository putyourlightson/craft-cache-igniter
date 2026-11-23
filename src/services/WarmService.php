<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\cacheigniter\services;

use Craft;
use craft\base\Component;
use craft\db\ActiveQuery;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\Queue;
use craft\helpers\StringHelper;
use putyourlightson\cacheigniter\CacheIgniter;
use putyourlightson\cacheigniter\jobs\WarmJob;
use putyourlightson\cacheigniter\records\UrlRecord;
use RuntimeException;
use yii\db\Expression;

class WarmService extends Component
{
    /**
     * Keeps work pending until the driver returns, while allowing new requests to arrive during warming.
     */
    public function warmPendingUrls(?callable $setProgressHandler = null, ?int $limit = null): int
    {
        return $this->withLock('warming', function() use ($setProgressHandler, $limit) {
            $rows = $this->getWarmableUrlQuery()
                ->select(['id', 'url', 'revision'])
                ->orderBy(['priority' => SORT_ASC, 'id' => SORT_ASC])
                ->limit($limit)
                ->asArray()
                ->all();
            if ($rows === []) {
                return 0;
            }

            CacheIgniter::$plugin->warmer->warmUrlsWithProgress(array_column($rows, 'url'), $setProgressHandler);

            foreach (array_chunk($rows, 500) as $chunk) {
                $this->withPendingTransaction(function() use ($chunk) {
                    $condition = ['or'];
                    foreach ($chunk as $row) {
                        // A later request must survive completion of the revision being warmed.
                        $condition[] = ['id' => $row['id'], 'revision' => $row['revision']];
                    }
                    UrlRecord::deleteAll($condition);
                });
            }

            return count($rows);
        });
    }

    public function getWarmableUrlCount(): int
    {
        return (int)$this->getWarmableUrlQuery()->count();
    }

    public function getWarmableUrlPriority(): ?int
    {
        $priority = $this->getWarmableUrlQuery()->min('priority');

        return $priority === null || $priority === false ? null : (int)$priority;
    }

    /**
     * @param string[] $urls
     */
    public function warmUrls(array $urls, ?callable $setProgressHandler = null, bool $immediate = true): void
    {
        if ($setProgressHandler !== null) {
            CacheIgniter::$plugin->warmer->warmUrlsWithProgress(array_values(array_unique($urls)), $setProgressHandler);
        } else {
            $this->prepareUrlsForWarming($urls, $immediate);
        }
    }

    /**
     * Hashes preserve complete, case-sensitive URLs without database index length restrictions.
     *
     * @param string[] $urls
     */
    private function prepareUrlsForWarming(array $urls, bool $immediate): void
    {
        $urlsByHash = [];
        foreach ($urls as $url) {
            if (strlen($url) <= CacheIgniter::$plugin->settings->maxUrlLength) {
                $urlsByHash[md5($url)] ??= $url;
            }
        }
        if ($urlsByHash === []) {
            return;
        }

        $settings = CacheIgniter::$plugin->settings;
        $refreshDelay = $immediate ? 0 : $settings->refreshDelay;
        $warmDate = Db::prepareDateForDb(DateTimeHelper::toDateTime(strtotime('+' . $refreshDelay . ' seconds')));
        $priority = $settings->warmJobPriority;
        $revision = StringHelper::UUID();
        $db = Craft::$app->getDb();

        foreach (array_chunk($urlsByHash, 500, true) as $chunk) {
            $this->withPendingTransaction(function() use ($chunk, $warmDate, $priority, $revision, $db) {
                $existingHashes = UrlRecord::find()
                    ->select('urlHash')
                    ->where(['urlHash' => array_keys($chunk)])
                    ->column();
                if ($existingHashes !== []) {
                    $db->createCommand()->update(UrlRecord::tableName(), [
                        'warmDate' => $warmDate,
                        'priority' => new Expression('LEAST([[priority]], :priority)', [':priority' => $priority]),
                        'revision' => $revision,
                    ], ['urlHash' => $existingHashes], [], false)->execute();
                }

                $existingHashSet = array_fill_keys($existingHashes, true);
                $newRows = [];
                foreach ($chunk as $hash => $url) {
                    if (!isset($existingHashSet[$hash])) {
                        $newRows[] = [$hash, $url, $warmDate, $priority, $revision];
                    }
                }
                if ($newRows !== []) {
                    $db->createCommand()->batchInsert(UrlRecord::tableName(), ['urlHash', 'url', 'warmDate', 'priority', 'revision'], $newRows)->execute();
                }
            });
        }

        Queue::push(
            job: new WarmJob(),
            priority: $priority,
            delay: $refreshDelay,
            queue: CacheIgniter::$plugin->queue,
        );
    }

    private function getWarmableUrlQuery(): ActiveQuery
    {
        return UrlRecord::find()
            ->where(['<=', 'warmDate', Db::prepareDateForDb('now')]);
    }

    /**
     * Serialises enqueueing and completion so workers cannot remove newly requested work.
     */
    private function withPendingTransaction(callable $callback): mixed
    {
        return $this->withLock('pending', fn() => Craft::$app->getDb()->transaction($callback));
    }

    /**
     * Queue workers and the pending-URL console command must not warm the same batch concurrently.
     */
    private function withLock(string $name, callable $callback): mixed
    {
        $mutex = Craft::$app->getMutex();
        $lock = 'cache-igniter-' . $name;
        if (!$mutex->acquire($lock, CacheIgniter::$plugin->settings->mutexTimeout)) {
            throw new RuntimeException('Unable to lock pending Cache Igniter ' . $name . ' work.');
        }

        try {
            return $callback();
        } finally {
            $mutex->release($lock);
        }
    }
}
