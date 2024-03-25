<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\cacheigniter\drivers\warmers;

use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\HttpException;
use Amp\Http\Client\Request;
use Amp\Pipeline\Pipeline;
use Craft;
use craft\helpers\UrlHelper;
use putyourlightson\blitz\drivers\generators\HttpGenerator;
use putyourlightson\cacheigniter\CacheIgniter;
use putyourlightson\cacheigniter\events\WarmUrlsEvent;
use yii\log\Logger;

/**
 * Based on Blitz’s HttpGenerator class.
 *
 * @see HttpGenerator
 *
 * The AMPHP framework is used for making HTTP requests and a concurrent
 * iterator is used to send the requests concurrently.
 *  See https://amphp.org/http-client/ and https://amphp.org/pipeline
 *
 * @property-read null|string $settingsHtml
 */
class HttpWarmer extends BaseWarmer
{
    /**
     * @var int The max number of concurrent requests.
     */
    public int $concurrency = 3;

    /**
     * @var int The timeout for requests in milliseconds.
     */
    public int $timeout = 120000;

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('cache-igniter', 'HTTP Warmer (via web server requests)');
    }

    /**
     * @inheritdoc
     */
    public function warmUrlsWithProgress(array $urls, ?callable $setProgressHandler = null): void
    {
        $event = new WarmUrlsEvent(['urls' => $urls]);
        $this->trigger(self::EVENT_BEFORE_WARM_URLS, $event);

        if (!$event->isValid) {
            return;
        }

        $count = 0;
        $total = count($urls);

        $this->updateProgress($setProgressHandler, $count, $total);

        $client = HttpClientBuilder::buildDefault();

        $concurrentIterator = Pipeline::fromIterable($urls)
            ->concurrent($this->concurrency);

        foreach ($concurrentIterator as $url) {
            $count++;

            if (UrlHelper::isAbsoluteUrl($url) !== false) {
                try {
                    $request = $this->createRequest($url);
                    $client->request($request);

                    if (is_callable($setProgressHandler)) {
                        $this->updateProgress($setProgressHandler, $count, $total);
                    }
                } catch (HttpException $exception) {
                    CacheIgniter::$plugin->log($exception->getMessage() . ' [' . $url . ']', [], Logger::LEVEL_ERROR);
                }
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('cache-igniter/_drivers/warmers/http/settings', [
            'warmer' => $this,
        ]);
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        return [
            [['concurrency'], 'required'],
            [['concurrency'], 'integer', 'min' => 1, 'max' => 100],
        ];
    }

    private function createRequest(string $url): Request
    {
        $request = new Request($url);

        // Set all timeout types, since at least two have been reported:
        // https://github.com/putyourlightson/craft-blitz/issues/467#issuecomment-1410308809
        $request->setTcpConnectTimeout($this->timeout);
        $request->setTlsHandshakeTimeout($this->timeout);
        $request->setTransferTimeout($this->timeout);
        $request->setInactivityTimeout($this->timeout);

        return $request;
    }
}
