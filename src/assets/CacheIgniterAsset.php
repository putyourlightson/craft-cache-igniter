<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\cacheigniter\assets;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class CacheIgniterAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init(): void
    {
        $this->sourcePath = '@putyourlightson/cacheigniter/resources';

        $this->depends = [
            CpAsset::class,
        ];

        $this->css = [
            'css/cp.css',
        ];

        parent::init();
    }
}
