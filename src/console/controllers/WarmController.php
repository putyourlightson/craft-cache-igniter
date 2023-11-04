<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\cacheigniter\console\controllers;

use Craft;
use craft\helpers\Console;
use putyourlightson\cacheigniter\CacheIgniter;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\BaseConsole;

class WarmController extends Controller
{
    /**
     * @inheritdoc
     */
    public function getHelp(): string
    {
        return Craft::t('cache-igniter', 'Cache Igniter actions.');
    }

    /**
     * @inheritdoc
     */
    public function getHelpSummary(): string
    {
        return $this->getHelp();
    }

    /**
     * Warms the provided URLs.
     */
    public function actionUrls(array $urls = []): int
    {
        if (empty($urls)) {
            $this->stderr(Craft::t('cache-igniter', 'One or more URLs must be provided as an argument.') . PHP_EOL, BaseConsole::FG_RED);

            return ExitCode::OK;
        }

        $this->stdout(Craft::t('cache-igniter', 'Warming URLs...') . PHP_EOL, BaseConsole::FG_YELLOW);

        Console::startProgress(0, count($urls), '', 0.8);
        CacheIgniter::$plugin->warm->warmUrls($urls, [$this, 'setProgressHandler']);
        Console::endProgress();

        $this->stdout(Craft::t('cache-igniter', 'URLs successfully warmed.') . PHP_EOL, BaseConsole::FG_GREEN);

        return ExitCode::OK;
    }

    /**
     * Warms pending URLs.
     */
    public function actionPendingUrls(): int
    {
        $urls = CacheIgniter::$plugin->warm->getWarmableUrlsAndRemove();

        if (empty($urls)) {
            $this->stderr(Craft::t('cache-igniter', 'There are no pending URLs to warm.') . PHP_EOL, BaseConsole::FG_RED);

            return ExitCode::OK;
        }

        $this->stdout(Craft::t('cache-igniter', 'Warming pending URLs...') . PHP_EOL, BaseConsole::FG_YELLOW);

        Console::startProgress(0, count($urls), '', 0.8);
        CacheIgniter::$plugin->warm->warmUrls($urls, [$this, 'setProgressHandler']);
        Console::endProgress();

        $this->stdout(Craft::t('cache-igniter', 'Pending URLs successfully warmed.') . PHP_EOL, BaseConsole::FG_GREEN);

        return ExitCode::OK;
    }

    /**
     * Handles setting the progress.
     */
    public function setProgressHandler(int $count, int $total): void
    {
        Console::updateProgress($count, $total);
    }
}
