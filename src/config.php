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
        //    'locations' => [
        //        'US West',
        //        'US East',
        //        'Brazil',
        //        'Germany',
        //        'Australia',
        //    ],
        //],

        // The warmer type classes to add to the plugin’s default warmer types.
        //'warmerTypes' => [],

        // URLs that should appear in the utility by default.
        //'utilityUrls' => [],

        //The number of seconds to wait before warming refreshed site URIs.
        //'refreshDelay' => 30,

        // Site URI patterns to include in warming whenever they are refreshed or generated by Blitz. Set `siteId` to a blank string to indicate all sites.
        //'includedRefreshUriPatterns' => [
        //    [
        //        'siteId' => 1,
        //        'uriPattern' => '',
        //    ],
        //    [
        //        'siteId' => 2,
        //        'uriPattern' => 'articles/.*',
        //    ],
        //],

        // Site URI patterns to exclude from warming whenever they are refreshed or generated by Blitz(overrides any matching patterns to include). Set `siteId` to a blank string to indicate all sites.
        //'excludedRefreshUriPatterns' => [
        //    [
        //        'siteId' => '',
        //        'uriPattern' => 'dynamic',
        //    ],
        //],

        // The priority to give the warm job (the lower the number, the higher the priority).
        //'warmJobPriority' => 101,

        // The maximum length of URLs that may be warmed. Increasing this value requires manually updating the limit in the `url` column of the `cacheigniter_urls` database table. Note that the prefix length limit is 3072 bytes for InnoDB tables that use the DYNAMIC or COMPRESSED row format. Assuming a `utf8mb4` character set and a maximum of 4 bytes for each character, this is 768 characters.
        // https://dev.mysql.com/doc/refman/8.0/en/column-indexes.html#column-indexes-prefi
        //'maxUrlLength' => 500,
    ],
];
