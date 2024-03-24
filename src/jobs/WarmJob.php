<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\cacheigniter\jobs;

use Craft;
use craft\base\Batchable;
use craft\helpers\Queue as QueueHelper;
use craft\queue\BaseBatchedJob;
use putyourlightson\cacheigniter\batchers\UrlBatcher;
use putyourlightson\cacheigniter\CacheIgniter;
use yii\queue\Queue;

class WarmJob extends BaseBatchedJob
{
    /**
     * @var Queue
     */
    private Queue $queue;

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();

        $this->batchSize = CacheIgniter::$plugin->settings->warmJobBatchSize;
    }

    /**
     * @inheritdoc
     */
    public function execute($queue): void
    {
        // TODO: move this into the `BaseBatchedJob::before` method in Blitz 5.
        // Decrement (increase) priority so that subsequent batches are prioritised.
        if ($this->itemOffset === 0) {
            $this->priority--;
        }

        $this->queue = $queue;

        /** @var string[] $urls */
        $urls = $this->data()->getSlice($this->itemOffset, $this->batchSize);

        CacheIgniter::$plugin->warmer->warmUrlsWithProgress($urls, [$this, 'setProgressHandler']);
        $this->itemOffset += count($urls);

        // Spawn another job if there are more items
        if ($this->itemOffset < $this->totalItems()) {
            $nextJob = clone $this;
            $nextJob->batchIndex++;
            QueueHelper::push($nextJob, $this->priority, 0, $this->ttr, $queue);
        }
    }

    public function setProgressHandler(int $count, int $total, string $label): void
    {
        $progress = $total > 0 ? ($count / $total) : 0;

        $this->setProgress($this->queue, $progress, $label);
    }

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): string
    {
        return Craft::t('cache-igniter', 'Warming cached URLs');
    }

    /**
     * @inheritdoc
     */
    protected function loadData(): Batchable
    {
        return new UrlBatcher();
    }

    /**
     * @inheritdoc
     */
    protected function processItem(mixed $item): void
    {
    }
}
