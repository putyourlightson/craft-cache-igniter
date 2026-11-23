<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\cacheigniter\migrations;

use craft\db\Migration;
use craft\helpers\StringHelper;
use putyourlightson\cacheigniter\CacheIgniter;
use putyourlightson\cacheigniter\records\UrlRecord;
use yii\db\Expression;

class m261123_120000_update_url_queue extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $table = UrlRecord::tableName();
        if (!$this->db->columnExists($table, 'id')) {
            $primaryKey = $this->db->getSchema()->getTablePrimaryKey($table);
            if ($this->db->getIsMysql() && $primaryKey !== null) {
                // Replace the primary key atomically to support generated invisible primary keys.
                $columnType = $this->db->getQueryBuilder()->getColumnType($this->primaryKey()->first());
                $this->execute('ALTER TABLE ' . $this->db->quoteTableName($table) . ' DROP PRIMARY KEY, ADD ' . $this->db->quoteColumnName('id') . ' ' . $columnType);
            } else {
                if ($primaryKey !== null) {
                    $this->dropPrimaryKey($primaryKey->name, $table);
                }
                $this->addColumn($table, 'id', $this->primaryKey()->first());
            }
        }
        if (!$this->db->columnExists($table, 'urlHash')) {
            $this->addColumn($table, 'urlHash', $this->char(32)->after('id'));
        }
        if (!$this->db->columnExists($table, 'priority')) {
            $this->addColumn($table, 'priority', $this->integer()->notNull()->defaultValue(CacheIgniter::$plugin->settings->warmJobPriority)->after('warmDate'));
        }
        if (!$this->db->columnExists($table, 'revision')) {
            $this->addColumn($table, 'revision', $this->char(36)->after('priority'));
        }

        // Preserve pending URLs while replacing their collation-dependent identity.
        $urlColumn = $this->db->getIsMysql() ? 'CONVERT([[url]] USING utf8mb4)' : '[[url]]';
        $this->update($table, [
            'urlHash' => new Expression("MD5($urlColumn)"),
            'revision' => StringHelper::UUID(),
        ]);
        $this->alterColumn($table, 'urlHash', $this->char(32)->notNull());
        $this->alterColumn($table, 'revision', $this->char(36)->notNull());
        $this->alterColumn($table, 'priority', $this->integer()->notNull());
        $this->createIndexIfMissing($table, 'urlHash', true);

        $this->alterColumn($table, 'url', $this->string(CacheIgniter::$plugin->settings->maxUrlLength)->notNull());
        $this->createIndexIfMissing($table, ['warmDate', 'priority', 'id']);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo self::class . " cannot be reverted.\n";

        return false;
    }
}
