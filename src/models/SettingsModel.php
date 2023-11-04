<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\cacheigniter\models;

use Craft;
use craft\base\Model;
use putyourlightson\cacheigniter\drivers\warmers\GlobalPingWarmer;

/**
 * @property-read array $regionOptions
 */
class SettingsModel extends Model
{
    /**
     * The warmer type to use.
     */
    public string $warmerType = GlobalPingWarmer::class;

    /**
     * The warmer settings.
     */
    public array $warmerSettings = [];

    /**
     * The warmer type classes to add to the plugin’s default warmer types.
     *
     * @var string[]
     */
    public array $warmerTypes = [];

    /**
     * Site URI patterns to warm whenever they are refreshed or generated by Blitz.
     *
     * @var array<int, array{
     *          siteId: ?int,
     *          uriPattern: string,
     *      }>
     */
    public array $refreshSiteUriPatterns = [];

    /**
     * The number of seconds to wait before warming refreshed site URIs.
     */
    public int $refreshDelay = 30;

    /**
     * URLs that should appear in the utility by default.
     *
     * @var array<int, array<int, string>>
     */
    public array $utilityUrls = [];

    /**
     * The priority to give the warm job (the lower the number, the higher the priority).
     */
    public int $warmJobPriority = 101;

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        $labels = parent::attributeLabels();
        $labels['refreshSiteUris'] = Craft::t('cache-igniter', 'Refresh Site URIs');
        $labels['utilityUrls'] = Craft::t('cache-igniter', 'Utility URLs');

        return $labels;
    }
}
