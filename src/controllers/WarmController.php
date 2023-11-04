<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\cacheigniter\controllers;

use Craft;
use craft\web\Controller;
use putyourlightson\cacheigniter\CacheIgniter;
use putyourlightson\cacheigniter\utilities\WarmUtility;
use yii\web\Response;

class WarmController extends Controller
{
    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        $this->requirePostRequest();

        // Require utility permission
        $this->requirePermission(WarmUtility::id());

        return true;
    }

    /**
     * Warms the provided URLs.
     */
    public function actionUrls(): ?Response
    {
        $urls = Craft::$app->getRequest()->getBodyParam('urls');

        // Flatten the multi-dimensional array from the editable table field.
        $urls = array_map(fn($url) => is_array($url) ? reset($url) : $url, $urls);

        if (empty($urls) || empty($urls[0])) {
            return $this->asFailure(Craft::t('cache-igniter', 'At least one URL must be provided.'));
        }

        CacheIgniter::$plugin->warm->warmUrls($urls);

        return $this->asSuccess(Craft::t('cache-igniter', 'URLs successfully queued for warming.'));
    }
}
