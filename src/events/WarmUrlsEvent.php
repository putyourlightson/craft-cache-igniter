<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\cacheigniter\events;

use craft\events\CancelableEvent;

class WarmUrlsEvent extends CancelableEvent
{
    /**
     * @var string[]
     */
    public array $urls = [];
}
