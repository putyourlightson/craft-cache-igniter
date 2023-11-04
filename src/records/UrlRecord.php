<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\cacheigniter\records;

use craft\db\ActiveRecord;
use DateTime;

/**
 * @property string $url
 * @property DateTime|null $warmDate
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
