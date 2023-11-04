<?php

/**
 * Tests warming URLs.
 */

use craft\helpers\UrlHelper;
use putyourlightson\cacheigniter\CacheIgniter;
use putyourlightson\cacheigniter\drivers\warmers\GlobalPingWarmer;
use putyourlightson\cacheigniter\records\UrlRecord;

beforeEach(function() {
    CacheIgniter::$plugin->set('warmer', new GlobalPingWarmer());
});

afterEach(function() {
    UrlRecord::deleteAll();
    Craft::$app->queue->releaseAll();
});

test('Warming URLs without a progress handler creates a record and a queue job', function() {
    $url = UrlHelper::url('test');
    CacheIgniter::$plugin->warm->warmUrls([$url]);

    expect($url)
        ->toHaveOneRecordAndQueueJob();
});
