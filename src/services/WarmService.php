<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\cacheigniter\services;

use Craft;
use craft\base\Component;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\Queue;
use putyourlightson\cacheigniter\CacheIgniter;
use putyourlightson\cacheigniter\jobs\WarmJob;
use putyourlightson\cacheigniter\records\UrlRecord;

/**
 * @property-read string[] $warmableUrlsAndRemove
 */
class WarmService extends Component
{
    /**
     * Returns URLs ready for warming from and removes them from the DB.
     *
     * @return string[]
     */
    public function getWarmableUrlsAndRemove(): array
    {
        $condition = ['<=', 'warmDate', Db::prepareDateForDb('now')];
        $urls = UrlRecord::find()
            ->select('url')
            ->where($condition)
            ->column();

        UrlRecord::deleteAll($condition);

        return $urls;
    }

    /**
     * Warms URLs, possibly preparing them for later warming.
     *
     * @param string[] $urls
     */
    public function warmUrls(array $urls, ?callable $setProgressHandler = null, bool $immediate = true): void
    {
        if ($setProgressHandler !== null) {
            CacheIgniter::$plugin->warmer->warmUrlsWithProgress($urls, $setProgressHandler);
        } else {
            $this->prepareUrlsForWarming($urls, $immediate);
        }
    }

    /**
     * Prepares URLs for warming by creating records and pushing a warm job onto the queue.
     *
     * @param string[] $urls
     */
    private function prepareUrlsForWarming(array $urls, bool $immediate): void
    {
        $refreshDelay = $immediate ? 0 : CacheIgniter::$plugin->settings->refreshDelay;
        $warmDate = DateTimeHelper::toDateTime(strtotime('+' . $refreshDelay . ' seconds'));

        foreach ($urls as $url) {
            if (strlen($url) <= CacheIgniter::$plugin->settings->maxUrlLength) {
                Craft::$app->getDb()->createCommand()
                    ->upsert(
                        UrlRecord::tableName(),
                        [
                            'url' => $url,
                            'warmDate' => Db::prepareDateForDb($warmDate),
                        ],
                        true,
                        [],
                        false
                    )
                    ->execute();
            }
        }

        Queue::push(
            job: new WarmJob(),
            priority: CacheIgniter::$plugin->settings->warmJobPriority,
            delay: $refreshDelay,
            queue: CacheIgniter::$plugin->queue,
        );
    }
}
