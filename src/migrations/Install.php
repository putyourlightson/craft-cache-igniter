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
        $this->createIndexes();

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
                'id' => $this->primaryKey(),
                'urlHash' => $this->char(32)->notNull(),
                'url' => $this->string(CacheIgniter::$plugin->settings->maxUrlLength)->notNull(),
                'warmDate' => $this->dateTime(),
                'priority' => $this->integer()->notNull(),
                'revision' => $this->char(36)->notNull(),
            ]);
        }

        return true;
    }

    protected function createIndexes(): void
    {
        $this->createIndex(null, UrlRecord::tableName(), 'urlHash', true);
        $this->createIndex(null, UrlRecord::tableName(), ['warmDate', 'priority', 'id']);
    }
}
