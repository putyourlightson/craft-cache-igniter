<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\cacheigniter\warmers;

use Craft;

class HttpWarmer extends BaseWarmer
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('cache-igniter', 'HTTP Warmer (via web server requests)');
    }
}
