<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

/**
 * Cache Igniter config.php
 *
 * This file exists only as a template for the Cache Igniter settings.
 * It does nothing on its own.
 *
 * Don't edit this file, instead copy it to 'craft/config' as 'cache-igniter.php'
 * and make your changes there to override default settings.
 *
 * Once copied to 'craft/config', this file will be multi-environment aware as
 * well, so you can have different settings groups for each environment, just as
 * you do for 'general.php'
 */

return [
    '*' => [
        // The warmer type to use.
        //'warmerType' => 'putyourlightson\cacheigniter\drivers\warmers\GlobalPingWarmer',

        // The warmer settings.
        //'warmerSettings' => [
        //    'locationRequestLimit' => 5,
        //    'locations' => [
        //        ['US West'],
        //        ['US East'],
        //        ['Brazil'],
        //        ['Germany'],
        //        ['Australia'],
        //    ],
        //],

        // The warmer type classes to add to the plugin’s default warmer types.
        //'warmerTypes' => [],

        // Site URI patterns to warm whenever they are refreshed or generated by Blitz.
        //'refreshSiteUriPatterns' => [
        //    [
        //        'siteId' => 1,
        //        'uriPattern' => '',
        //    ],
        //    [
        //        'siteId' => 2,
        //        'uriPattern' => 'articles/.*',
        //    ],
        //],

        // URLs that should appear in the utility by default.
        //'utilityUrls' => [],

        // The priority to give the warm job (the lower the number, the higher the priority).
        //'warmJobPriority' => 101,
    ],
];
