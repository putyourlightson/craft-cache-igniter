<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\cacheigniter\migrations;

use craft\db\Migration;
use putyourlightson\cacheigniter\CacheIgniter;
use putyourlightson\cacheigniter\records\UrlRecord;

class Install extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->createTables();

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $this->dropTableIfExists(UrlRecord::tableName());

        return true;
    }

    protected function createTables(): bool
    {
        if (!$this->db->tableExists(UrlRecord::tableName())) {
            $this->createTable(UrlRecord::tableName(), [
                'url' => $this->string(CacheIgniter::$plugin->settings->maxUrlLength)->notNull(),
                'warmDate' => $this->dateTime(),
                'PRIMARY KEY([[url]])',
            ]);
        }

        return true;
    }
}
