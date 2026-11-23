<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\cacheigniter\records;

use craft\db\ActiveRecord;
use DateTime;

/**
 * @property int $id
 * @property string $urlHash
 * @property string $url
 * @property DateTime|null $warmDate
 * @property int $priority
 * @property string $revision
 */
class UrlRecord extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName(): string
    {
        return '{{%cacheigniter_urls}}';
    }
}
