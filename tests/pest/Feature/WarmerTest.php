<?php

/**
 * Tests warming URLs with each of the warmer drivers.
 */

use putyourlightson\cacheigniter\CacheIgniter;

test('Warming a URL results in a positive response from the API', function() {
    expect(CacheIgniter::$plugin->warmer->warmUrl('https://putyourlightson.com/'))
        ->toBeTrue();
})->with('warmerDrivers');

test('Fetching the rate limit from the API returns a valid result', function() {
    expect(CacheIgniter::$plugin->warmer->getRateLimitDescription())
        ->toBeString();
})->with('warmerDrivers');
