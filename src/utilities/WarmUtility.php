<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\cacheigniter\utilities;

use Craft;
use craft\base\Utility;
use putyourlightson\cacheigniter\CacheIgniter;

class WarmUtility extends Utility
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('cache-igniter', 'Cache Igniter');
    }

    /**
     * @inheritdoc
     */
    public static function id(): string
    {
        return 'cache-igniter';
    }

    /**
     * @inheritdoc
     */
    public static function icon(): ?string
    {
        $iconPath = Craft::getAlias('@putyourlightson/cacheigniter/icon-mask.svg');

        if (!is_string($iconPath)) {
            return null;
        }

        return $iconPath;
    }

    /**
     * @inheritdoc
     */
    public static function contentHtml(): string
    {
        $settings = CacheIgniter::$plugin->settings;

        return Craft::$app->getView()->renderTemplate('cache-igniter/_utility', [
            'settings' => $settings,
            'urls' => $settings->utilityUrls,
        ]);
    }
}
