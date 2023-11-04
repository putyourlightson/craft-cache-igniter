<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\cacheigniter\helpers;

use craft\events\RegisterComponentTypesEvent;
use putyourlightson\cacheigniter\CacheIgniter;
use putyourlightson\cacheigniter\drivers\warmers\BaseWarmer;
use putyourlightson\cacheigniter\drivers\warmers\DummyWarmer;
use putyourlightson\cacheigniter\drivers\warmers\GlobalPingWarmer;
use putyourlightson\cacheigniter\drivers\warmers\HttpWarmer;
use yii\base\Event;

class WarmerHelper
{
    /**
     * @event RegisterComponentTypesEvent
     */
    public const EVENT_REGISTER_WARMER_TYPES = 'registerWarmerTypes';

    /**
     * Returns all warmer types.
     *
     * @return string[]
     */
    public static function getAllTypes(): array
    {
        $warmerTypes = [
            GlobalPingWarmer::class,
            HttpWarmer::class,
            DummyWarmer::class,
        ];

        $warmerTypes = array_unique(array_merge(
            $warmerTypes,
            CacheIgniter::$plugin->settings->warmerTypes,
        ), SORT_REGULAR);

        $event = new RegisterComponentTypesEvent([
            'types' => $warmerTypes,
        ]);
        Event::trigger(static::class, self::EVENT_REGISTER_WARMER_TYPES, $event);

        return $event->types;
    }

    /**
     * Returns all warmer drivers.
     *
     * @return BaseWarmer[]
     */
    public static function getAllWarmerDrivers(): array
    {
        return self::createWarmers(self::getAllTypes());
    }

    /**
     * Creates warmers of the provided types.
     *
     * @return BaseWarmer[]
     */
    public static function createWarmers(array $types): array
    {
        $warmers = [];

        foreach ($types as $type) {
            if ($type::isSelectable()) {
                $warmers[] = self::createWarmer($type);
            }
        }

        return $warmers;
    }

    /**
     * Creates a warmers of the provided type with the optional settings.
     */
    public static function createWarmer(string $type, array $settings = []): BaseWarmer
    {
        // Create a new object rather than using `Component::createComponent`, which can throw an exception if provided settings do not exist on the class.
        $warmer = new $type();
        $warmer->setAttributes($settings);

        return $warmer;
    }
}
