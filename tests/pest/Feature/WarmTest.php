<?php

/**
 * Tests warming URLs.
 */

use craft\helpers\StringHelper;
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

test('Warming URLs with a progress handler creates records', function() {
    $url = UrlHelper::url('test');
    CacheIgniter::$plugin->warm->warmUrls([$url]);

    expect($url)
        ->toHaveOneRecordAndQueueJob();
});

test('Warming a URL longer than the max URL length does not create a record', function() {
    $path = StringHelper::randomString(CacheIgniter::$plugin->settings->maxUrlLength + 1);
    $url = UrlHelper::url($path);
    CacheIgniter::$plugin->warm->warmUrls([$url]);

    expect(UrlRecord::find()->count())
        ->toBe(0);
});

test('Warming URLs without a progress handler creates a record and a queue job', function() {
    $url = UrlHelper::url('test');
    CacheIgniter::$plugin->warm->warmUrls([$url]);

    expect($url)
        ->toHaveOneRecordAndQueueJob();
});
