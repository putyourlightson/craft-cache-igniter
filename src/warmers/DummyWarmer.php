<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\cacheigniter\warmers;

use Craft;

class DummyWarmer extends BaseWarmer
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('cache-igniter', 'None');
    }

    /**
     * @inheritdoc
     */
    public function warmUrlsWithProgress(array $urls, ?callable $setProgressHandler = null): void
    {
    }
}
