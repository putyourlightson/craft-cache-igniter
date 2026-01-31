<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\cacheigniter\controllers;

use Craft;
use craft\web\Controller;
use putyourlightson\cacheigniter\CacheIgniter;
use yii\base\Response;

class WarmerController extends Controller
{
    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        $this->requireAdmin();

        return true;
    }

    /**
     * Returns a description of the warmer’s rate limit.
     */
    public function actionGetRateLimitDescription(): Response|string
    {
        $rateLimitDescription = CacheIgniter::$plugin->warmer->getRateLimitDescription();

        if ($rateLimitDescription === null) {
            return $this->asFailure(Craft::t('cache-igniter', 'There was an error getting the warmer’s rate limit. See the logs for more details.'));
        }

        return $this->asSuccess($rateLimitDescription);
    }
}
