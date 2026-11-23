<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\cacheigniter\jobs;

use Craft;
use craft\helpers\Queue as QueueHelper;
use craft\queue\BaseJob;
use craft\queue\Queue as CraftQueue;
use putyourlightson\cacheigniter\CacheIgniter;
use yii\queue\Queue;
use yii\queue\RetryableJobInterface;

class WarmJob extends BaseJob implements RetryableJobInterface
{
    public int $batchIndex = 0;

    private Queue $queue;

    /**
     * @inheritdoc
     */
    public function getDescription(): string
    {
        $description = $this->description ?? Craft::t('cache-igniter', 'Warming cached URLs');
        $pendingCount = CacheIgniter::$plugin->warm->getWarmableUrlCount();
        $totalBatches = $this->batchIndex + max(1, (int)ceil($pendingCount / CacheIgniter::$plugin->settings->warmJobBatchSize));
        if ($totalBatches <= 1) {
            return $description;
        }

        return Craft::t('app', '{description} (batch {index, number} of {total, number})', [
            'description' => $description,
            'index' => $this->batchIndex + 1,
            'total' => $totalBatches,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getTtr(): int
    {
        return CacheIgniter::$plugin->queue->ttr;
    }

    /**
     * @inheritdoc
     */
    public function canRetry($attempt, $error): bool
    {
        return $attempt < CacheIgniter::$plugin->settings->maxRetryAttempts;
    }

    public function execute($queue): void
    {
        $this->queue = $queue;
        CacheIgniter::$plugin->warm->warmPendingUrls([$this, 'setProgressHandler'], CacheIgniter::$plugin->settings->warmJobBatchSize);

        if ($queue instanceof CraftQueue && !$queue->isReserved($queue->getJobId())) {
            return;
        }
        $priority = CacheIgniter::$plugin->warm->getWarmableUrlPriority();
        if ($priority !== null) {
            $nextJob = new self([
                'description' => $this->description,
                'batchIndex' => $this->batchIndex + 1,
            ]);
            QueueHelper::push($nextJob, $priority - 1, 0, $this->getTtr(), $queue);
        }
    }

    public function setProgressHandler(int $count, int $total, string $label): void
    {
        $progress = $total > 0 ? ($count / $total) : 0;

        $this->setProgress($this->queue, $progress, $label);
    }
}
