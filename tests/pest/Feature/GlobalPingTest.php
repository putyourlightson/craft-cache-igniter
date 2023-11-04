<?php

/**
 * Tests warming URLs.
 */

use putyourlightson\cacheigniter\CacheIgniter;
use putyourlightson\cacheigniter\warmers\GlobalPingWarmer;

beforeEach(function() {
    CacheIgniter::$plugin->set('warmer', new GlobalPingWarmer());
});

test('Warming a URL results in a positive response from the API', function() {
    $warmer = new GlobalPingWarmer();
    $result = $warmer->warmUrl('https://putyourlightson.com/');

    expect($result)
        ->toBeTrue();
});

test('Fetching the rate limit from the API returns a valid result', function() {
    $warmer = new GlobalPingWarmer();
    $rateLimitDescription = $warmer->getRateLimitDescription();

    expect($rateLimitDescription)
        ->toBeString();
});

