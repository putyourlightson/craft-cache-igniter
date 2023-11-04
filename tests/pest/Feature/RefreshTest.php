<?php

/**
 * Tests refreshing site URIs.
 */

use craft\helpers\UrlHelper;
use putyourlightson\blitz\models\SiteUriModel;
use putyourlightson\cacheigniter\CacheIgniter;
use putyourlightson\cacheigniter\records\UrlRecord;
use putyourlightson\cacheigniter\warmers\GlobalPingWarmer;

beforeEach(function() {
    CacheIgniter::$plugin->set('warmer', new GlobalPingWarmer());
    CacheIgniter::$plugin->settings->refreshSiteUriPatterns = [
        [
            'siteId' => 1,
            'uriPattern' => 'test',
        ],
    ];
});

afterEach(function() {
    UrlRecord::deleteAll();
    Craft::$app->queue->releaseAll();
});

test('Refreshing a site URI with a matching a refresh site URI pattern creates a record and a queue job', function() {
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
