<?php

/**
 * Tests refreshing site URIs.
 */

use craft\helpers\UrlHelper;
use putyourlightson\blitz\models\SiteUriModel;
use putyourlightson\cacheigniter\CacheIgniter;
use putyourlightson\cacheigniter\drivers\warmers\GlobalPingWarmer;
use putyourlightson\cacheigniter\records\UrlRecord;

beforeEach(function() {
    CacheIgniter::$plugin->set('warmer', new GlobalPingWarmer());
    CacheIgniter::$plugin->settings->includedRefreshUriPatterns = [
        [
            'siteId' => 1,
            'uriPattern' => 'test',
        ],
    ];
    CacheIgniter::$plugin->settings->excludedRefreshUriPatterns = [
        [
            'siteId' => 1,
            'uriPattern' => 'dynamic',
        ],
    ];
});

afterEach(function() {
    UrlRecord::deleteAll();
    Craft::$app->queue->releaseAll();
});

test('Refreshing a site URI with a matching included refresh URI pattern creates a record and a queue job', function() {
    $uri = 'test';
    CacheIgniter::$plugin->refresh->refreshSiteUris([
        new SiteUriModel([
            'siteId' => 1,
            'uri' => $uri,
        ]),
        new SiteUriModel([
            'siteId' => 2,
            'uri' => $uri,
        ]),
    ]);

    expect(UrlHelper::url($uri))
        ->toHaveOneRecordAndQueueJob();
});

test('Refreshing a site URI with a matching excluded refresh URI pattern does not create a record nor a queue job', function() {
    $uri = 'test';
    CacheIgniter::$plugin->refresh->refreshSiteUris([
        new SiteUriModel([
            'siteId' => 1,
            'uri' => 'test-dynamic',
        ]),
    ]);

    expect(UrlHelper::url($uri))
        ->toHaveNoRecordNorQueueJob();
});
