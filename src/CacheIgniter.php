<?php

/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\cacheigniter;

use Craft;
use craft\base\Plugin;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\log\MonologTarget;
use craft\services\Utilities;
use craft\web\UrlManager;
use Monolog\Formatter\LineFormatter;
use Psr\Log\LogLevel;
use putyourlightson\blitz\events\RefreshCacheEvent;
use putyourlightson\blitz\services\RefreshCacheService;
use putyourlightson\cacheigniter\drivers\warmers\BaseWarmer;
use putyourlightson\cacheigniter\helpers\WarmerHelper;
use putyourlightson\cacheigniter\models\SettingsModel;
use putyourlightson\cacheigniter\services\RefreshService;
use putyourlightson\cacheigniter\services\WarmService;
use putyourlightson\cacheigniter\utilities\WarmUtility;
use yii\base\Event;
use yii\log\Logger;

/**
 * @property-read RefreshService $refresh
 * @property-read WarmService $warm
 * @property-read BaseWarmer $warmer
 * @property-read SettingsModel $settings
 */
class CacheIgniter extends Plugin
{
    public static CacheIgniter $plugin;

    /**
     * @inerhitdoc
     */
    public static function config(): array
    {
        return [
            'components' => [
                'refresh' => ['class' => RefreshService::class],
                'warm' => ['class' => WarmService::class],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public bool $hasCpSettings = true;

    /**
     * @inheritdoc
     */
    public string $schemaVersion = '1.0.0';

    /**
     * @inheritdoc
     */
    public function init(): void
    {
        parent::init();
        self::$plugin = $this;

        $this->_registerComponents();
        $this->_registerLogTarget();
        $this->_registerRefreshEvents();

        if (Craft::$app->getRequest()->getIsCpRequest()) {
            $this->_registerCpUrlRules();
            $this->_registerUtilities();
        }
    }

    /**
     * Logs a message
     */
    public function log(string $message, array $params = [], int $type = Logger::LEVEL_INFO): void
    {
        $message = Craft::t('cache-igniter', $message, $params);

        Craft::getLogger()->log($message, $type, 'cache-igniter');
    }

    /**
     * @inheritdoc
     */
    protected function createSettingsModel(): SettingsModel
    {
        return new SettingsModel();
    }

    /**
     * Registers the components that should be defined via settings, providing
     * they have not already been set in `$pluginConfigs`.
     *
     * @see Plugins::$pluginConfigs
     */
    private function _registerComponents(): void
    {
        if (!$this->has('warmer')) {
            $this->set('warmer', WarmerHelper::createWarmer(
                $this->settings->warmerType,
                $this->settings->warmerSettings,
            ));
        }
    }

    /**
     * Registers a custom log target, keeping the format as simple as possible.
     *
     * @see LineFormatter::SIMPLE_FORMAT
     */
    private function _registerLogTarget(): void
    {
        Craft::getLogger()->dispatcher->targets[] = new MonologTarget([
            'name' => 'cache-igniter',
            'categories' => ['cache-igniter'],
            'level' => LogLevel::INFO,
            'logContext' => false,
            'allowLineBreaks' => false,
            'formatter' => new LineFormatter(
                format: "[%datetime%] %message%\n",
                dateFormat: 'Y-m-d H:i:s',
            ),
        ]);
    }

    private function _registerRefreshEvents(): void
    {
        if (Craft::$app->plugins->getPlugin('blitz') === null) {
            return;
        }

        Event::on(RefreshCacheService::class, RefreshCacheService::EVENT_AFTER_REFRESH_CACHE,
            function(RefreshCacheEvent $event) {
                $this->refresh->refreshSiteUris($event->siteUris);
            }
        );

        Event::on(RefreshCacheService::class, RefreshCacheService::EVENT_AFTER_REFRESH_ALL_CACHE,
            function(RefreshCacheEvent $event) {
                $this->refresh->refreshSiteUris($event->siteUris);
            }
        );
    }

    private function _registerCpUrlRules(): void
    {
        Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function(RegisterUrlRulesEvent $event) {
                // Merge so that settings controller action comes first (important!)
                $event->rules = array_merge([
                    'settings/plugins/cache-igniter' => 'cache-igniter/settings/edit',
                ],
                    $event->rules
                );
            }
        );
    }

    private function _registerUtilities(): void
    {
        Event::on(Utilities::class, Utilities::EVENT_REGISTER_UTILITY_TYPES,
            function(RegisterComponentTypesEvent $event) {
                $event->types[] = WarmUtility::class;
            }
        );
    }
}
