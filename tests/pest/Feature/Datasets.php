<?php

use putyourlightson\cacheigniter\CacheIgniter;
use putyourlightson\cacheigniter\drivers\warmers\DummyWarmer;
use putyourlightson\cacheigniter\drivers\warmers\GlobalPingWarmer;
use putyourlightson\cacheigniter\drivers\warmers\HttpWarmer;

dataset('warmerDrivers', [
    'GlobalPingWarmer' => function() {
        CacheIgniter::$plugin->set('warmer', new GlobalPingWarmer());

        return GlobalPingWarmer::class;
    },
    'HttpWarmer' => function() {
        CacheIgniter::$plugin->set('warmer', new HttpWarmer());

        return HttpWarmer::class;
    },
    'DummyWarmer' => function() {
        CacheIgniter::$plugin->set('warmer', new DummyWarmer());

        return DummyWarmer::class;
    },
]);
