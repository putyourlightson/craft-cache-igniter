<?php

namespace putyourlightson\cacheigniter\batchers;

use craft\base\Batchable;
use putyourlightson\cacheigniter\CacheIgniter;

/**
 * @since 1.2.0
 */
class UrlBatcher implements Batchable
{
    public function __construct()
    {
    }

    /**
     * @inheritdoc
     */
    public function count(): int
    {
        return CacheIgniter::$plugin->warm->getWarmableUrlCount();
    }

    /**
     * @inheritdoc
     */
    public function getSlice(int $offset, int $limit): iterable
    {
        return CacheIgniter::$plugin->warm->getWarmableUrlsAndRemove($limit);
    }
}
