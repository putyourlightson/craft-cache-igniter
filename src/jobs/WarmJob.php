<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\cacheigniter\jobs;

use Craft;
use craft\queue\BaseJob;
use craft\queue\QueueInterface;
use putyourlightson\cacheigniter\CacheIgniter;
use yii\queue\Queue;

class WarmJob extends BaseJob
{
    public Queue|QueueInterface|null $queue = null;

    /**
     * @inheritdoc
     */
    public function execute($queue): void
    {
        $urls = CacheIgniter::$plugin->warm->getWarmableUrlsAndRemove();

        if (empty($urls)) {
            return;
        }

        $this->queue = $queue;
        CacheIgniter::$plugin->warmer->warmUrlsWithProgress($urls, [$this, 'setProgressHandler']);
    }

    public function setProgressHandler(int $count, int $total, string $label): void
    {
        $this->setProgress($this->queue, $count / $total, $label);
    }

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): string
    {
        return Craft::t('cache-igniter', 'Warming CDN URLs');
    }
}
