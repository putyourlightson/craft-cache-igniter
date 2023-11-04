<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\cacheigniter\drivers\warmers;

use Craft;
use craft\base\SavableComponent;
use putyourlightson\cacheigniter\events\WarmUrlsEvent;

/**
 * @property-read string|null $rateLimitDescription
 */
abstract class BaseWarmer extends SavableComponent implements WarmerInterface
{
    /**
     * @event WarmUrlsEvent
     */
    public const EVENT_BEFORE_WARM_URLS = 'beforeWarmUrls';

    /**
     * @event WarmUrlsEvent
     */
    public const EVENT_AFTER_WARM_URLS = 'afterWarmUrls';

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

        foreach ($urls as $url) {
            $this->warmUrl($url);
            $count++;
            $this->updateProgress($setProgressHandler, $count, $total);
        }

        if ($this->hasEventHandlers(self::EVENT_AFTER_WARM_URLS)) {
            $this->trigger(self::EVENT_AFTER_WARM_URLS, $event);
        }
    }

    /**
     * @inheritdoc
     */
    public function getRateLimitDescription(): ?string
    {
        return null;
    }

    /**
     * Warms a URL.
     */
    public function warmUrl(string $url): bool
    {
        return true;
    }

    /**
     * Updates warming progress with a progress handler.
     */
    protected function updateProgress(?callable $setProgressHandler, int $count, int $total): void
    {
        if ($setProgressHandler === null) {
            return;
        }

        $label = Craft::t('cache-igniter', 'Warming {count} of {total} URLs.', [
            'count' => $count,
            'total' => $total,
        ]);

        call_user_func($setProgressHandler, $count, $total, $label);
    }
}
