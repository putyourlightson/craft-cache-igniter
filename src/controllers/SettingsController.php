<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\cacheigniter\controllers;

use Craft;
use craft\web\Controller;
use putyourlightson\cacheigniter\CacheIgniter;
use putyourlightson\cacheigniter\helpers\WarmerHelper;
use putyourlightson\cacheigniter\warmers\BaseWarmer;
use yii\web\Response;

class SettingsController extends Controller
{
    /**
     * @inerhitdoc
     */
    public function beforeAction($action): bool
    {
        $this->requireAdmin();

        return parent::beforeAction($action);
    }

    /**
     * Edit the plugin settings.
     */
    public function actionEdit(): ?Response
    {
        $settings = CacheIgniter::$plugin->settings;

        // Get site options
        $siteOptions = [];

        foreach (Craft::$app->getSites()->getAllSites() as $site) {
            $siteOptions[] = [
                'value' => $site->id,
                'label' => $site->name,
            ];
        }

        $warmer = WarmerHelper::createWarmer(
            $settings->warmerType,
            $settings->warmerSettings,
        );

        // Validate the driver so that any errors will be displayed
        $warmer->validate();

        $warmers = WarmerHelper::getAllWarmers();

        return $this->renderTemplate('cache-igniter/_settings', [
            'settings' => $settings,
            'siteOptions' => $siteOptions,
            'warmer' => $warmer,
            'warmers' => $warmers,
            'warmerTypeOptions' => array_map([$this, '_getSelectOption'], $warmers),
        ]);
    }

    /**
     * Saves the plugin settings.
     */
    public function actionSave(): ?Response
    {
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();

        $postedSettings = $request->getBodyParam('settings', []);
        $warmerSettings = $request->getBodyParam('warmerSettings', []);

        $settings = CacheIgniter::$plugin->settings;
        $settings->setAttributes($postedSettings, false);

        // Apply storage settings excluding type
        $settings->warmerSettings = $warmerSettings[$settings->warmerType] ?? [];

        // Create the warmer so that we can validate it
        $warmer = WarmerHelper::createWarmer(
            $settings->warmerType,
            $settings->warmerSettings,
        );

        // Validate
        $settings->validate();
        $warmer->validate();

        if ($settings->hasErrors() || $warmer->hasErrors()) {
            Craft::$app->getSession()->setError(Craft::t('cache-igniter', 'Couldn’t save plugin settings.'));

            return null;
        }

        Craft::$app->getPlugins()->savePluginSettings(CacheIgniter::$plugin, $settings->getAttributes());

        Craft::$app->getSession()->setNotice(Craft::t('cache-igniter', 'Plugin settings saved.'));

        return $this->redirectToPostedUrl();
    }

    /**
     * Gets select option from a warmer.
     */
    private function _getSelectOption(BaseWarmer $warmer): array
    {
        return [
            'value' => $warmer::class,
            'label' => $warmer::displayName(),
        ];
    }
}
