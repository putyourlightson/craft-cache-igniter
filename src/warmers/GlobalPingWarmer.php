<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\cacheigniter\warmers;

use Craft;
use craft\helpers\UrlHelper;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;
use putyourlightson\cacheigniter\CacheIgniter;
use yii\log\Logger;

/**
 * @property-read string|null $rateLimitDescription
 * @property-read string|null $settingsHtml
 */
class GlobalPingWarmer extends BaseWarmer
{
    /**
     * @const string
     */
    public const API_ENDPOINT = 'https://api.globalping.io/v1/measurements';

    /**
     * The maximum number of location requests per URL.
     */
    public int $locationRequestLimit = 5;

    /**
     * Locations to warm.
     */
    public array $locations = [
        ['US West'],
        ['US East'],
        ['Brazil'],
        ['Germany'],
        ['Australia'],
    ];

    private bool $_rateLimitExceeded = false;

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('cache-igniter', 'GlobalPing Warmer (via a globally distributed network)');
    }

    /**
     * Guzzle client that can be memoized and mocked.
     */
    public ?Client $client = null;

    private function _getClient(): Client
    {
        if ($this->client !== null) {
            return $this->client;
        }

        // https://www.jsdelivr.com/docs/api.globalping.io#post-/v1/measurements
        $this->client = Craft::createGuzzleClient([
            'base_uri' => self::API_ENDPOINT,
            'headers' => [
                'Accept-Encoding' => 'br',
                'Content-Type' => 'application/json',
                'User-Agent' => 'CacheIgniter/1 (https://putyourlightson.com/plugins/cache-igniter)',
            ],
        ]);

        return $this->client;
    }

    /**
     * @inheritdoc
     */
    public function warmUrl(string $url): bool
    {
        if (UrlHelper::isAbsoluteUrl($url) === false) {
            return false;
        }

        if ($this->_rateLimitExceeded === true) {
            CacheIgniter::$plugin->log('Unable to warm URL `{url}`. GlobalPing API rate limit has been exceeded.', ['url' => $url], Logger::LEVEL_ERROR);

            return false;
        }

        $response = $this->_sendRequest($url);
        if ($response === null) {
            return false;
        }

        $rateLimit = $this->_getRateLimitFromResponse($response);

        if ($rateLimit['remaining'] < $this->locationRequestLimit) {
            $this->_rateLimitExceeded = true;
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function getRateLimitDescription(): ?string
    {
        // Hard-code a live URL so the API doesn’t respond with an error.
        $url = 'https://putyourlightson.com/';

        $response = $this->_sendRequest($url, 1);
        if ($response === null) {
            return null;
        }

        $rateLimit = $this->_getRateLimitFromResponse($response);
        $rateLimit['minutes'] = floor($rateLimit['reset'] / 60);
        $rateLimit['seconds'] = $rateLimit['reset'] % 60;

        return Craft::t('cache-igniter', '{remaining} requests remaining of {limit}. This limit will reset in {minutes} minutes and {seconds} seconds.', $rateLimit);
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml(): ?string
    {
        return Craft::$app->getView()->renderTemplate('cache-igniter/_warmers/globalping/settings', [
            'warmer' => $this,
        ]);
    }

    /**
     * @inheritdoc
     */
    protected function defineRules(): array
    {
        return [
            [['locationRequestLimit'], 'integer', 'min' => 0, 'max' => 100],
        ];
    }

    private function _sendRequest(string $url, ?int $locationRequestLimit = null): ?Response
    {
        $client = $this->_getClient();

        try {
            /** @var Response $response */
            $response = $client->post('', $this->_getRequestBody($url, $locationRequestLimit));
        } catch (RequestException $exception) {
            CacheIgniter::$plugin->log($exception->getMessage(), [], Logger::LEVEL_ERROR);

            return null;
        }

        return $response;
    }

    private function _getRequestBody(string $url, ?int $locationRequestLimit = null): array
    {
        $locations = [];
        foreach ($this->locations as $key => $location) {
            $location = is_array($location) ? ($location[0] ?? null) : $location;
            if (!empty($location)) {
                $locations[$key] = [
                    'magic' => $location,
                ];
            }
        }

        $host = parse_url($url, PHP_URL_HOST);
        $queryString = parse_url($url, PHP_URL_QUERY);
        $path = parse_url($url, PHP_URL_PATH);
        $path .= $queryString ? '?' . $queryString : '';

        // https://www.jsdelivr.com/docs/api.globalping.io#post-/v1/measurements
        return [
            'body' => json_encode([
                'type' => 'http',
                'target' => $host,
                'measurementOptions' => [
                    'request' => [
                        'path' => $path,
                    ],
                ],
                'limit' => $locationRequestLimit ?? $this->locationRequestLimit,
                'locations' => $locations,
            ]),
        ];
    }

    private function _getRateLimitFromResponse(Response $response): array
    {
        $rateLimit = [];

        foreach ($response->getHeaders() as $key => $value) {
            $prefix = 'x-ratelimit-';
            $key = strtolower($key);
            if (str_starts_with($key, $prefix)) {
                $rateLimit[substr($key, strlen($prefix))] = $value[0] ?? $value;
            }
        }

        return $rateLimit;
    }
}
