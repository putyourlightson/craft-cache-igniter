<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\cacheigniter\drivers\warmers;

interface WarmerInterface
{
    /**
     * Warms URLs with an optional progress handler.
     */
    public function warmUrlsWithProgress(array $urls, ?callable $setProgressHandler = null): void;

    /**
     * Returns a description of the warmer’s rate limit.
     */
    public function getRateLimitDescription(): ?string;
}
