<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\cacheigniter\services;

use craft\base\Component;
use putyourlightson\blitz\models\SiteUriModel;
use putyourlightson\blitz\services\CacheRequestService;
use putyourlightson\cacheigniter\CacheIgniter;

class RefreshService extends Component
{
    /**
     * @param SiteUriModel[] $siteUris
     */
    public function refreshSiteUris(array $siteUris): void
    {
        $urls = [];

        foreach ($siteUris as $siteUri) {
            if ($this->_getIsWarmableSiteUri($siteUri)) {
                $urls[] = $siteUri->getUrl();
            }
        }

        if (!empty($urls)) {
            CacheIgniter::$plugin->warm->warmUrls($urls, null, false);
        }
    }

    /**
     * Returns whether the site URI is warmable.
     */
    private function _getIsWarmableSiteUri(SiteUriModel $siteUri): bool
    {
        // Excluded URI patterns take priority
        if ($this->_matchesUriPatterns($siteUri, CacheIgniter::$plugin->settings->excludedRefreshUriPatterns)) {
            return false;
        }

        return $this->_matchesUriPatterns($siteUri, CacheIgniter::$plugin->settings->includedRefreshUriPatterns);
    }

    /**
     * Returns true if the URI matches a set of patterns.
     *
     * @see CacheRequestService::matchesUriPatterns()
     */
    private function _matchesUriPatterns(SiteUriModel $siteUri, array $siteUriPatterns): bool
    {
        foreach ($siteUriPatterns as $siteUriPattern) {
            if (empty($siteUriPattern['siteId']) || $siteUriPattern['siteId'] == $siteUri->siteId) {
                $uriPattern = $siteUriPattern['uriPattern'];

                // Replace a blank string with the homepage with query strings allowed
                if ($uriPattern == '') {
                    $uriPattern = '^(\?.*)?$';
                }

                // Replace "*" with 0 or more characters as otherwise it'll throw an error
                if ($uriPattern == '*') {
                    $uriPattern = '.*';
                }

                // Trim slashes
                $uriPattern = trim($uriPattern, '/');

                // Escape delimiters, removing already escaped delimiters first
                // https://github.com/putyourlightson/craft-blitz/issues/261
                $uriPattern = str_replace(['\/', '/'], ['/', '\/'], $uriPattern);

                if (preg_match('/' . $uriPattern . '/', trim($siteUri->uri, '/'))) {
                    return true;
                }
            }
        }

        return false;
    }
}
