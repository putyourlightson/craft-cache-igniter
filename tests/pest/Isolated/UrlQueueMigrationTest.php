<?php

use craft\helpers\Db;
use putyourlightson\cacheigniter\CacheIgniter;
use putyourlightson\cacheigniter\migrations\m261123_120000_update_url_queue;
use putyourlightson\cacheigniter\records\UrlRecord;

test('URL queue migration preserves pending URLs and replaces URL uniqueness with hashes', function(?string $charset, int $maxUrlLength) {
    $db = Craft::$app->getDb();
    if ($charset !== null && !$db->getIsMysql()) {
        $this->markTestSkipped('MySQL character set test.');
    }
    $connection = Craft::createObject([
        'class' => $db::class,
        'dsn' => $db->dsn,
        'username' => $db->username,
        'password' => $db->password,
        'charset' => $db->charset,
        'schemaMap' => $db->schemaMap,
        'commandMap' => $db->commandMap,
        'tablePrefix' => 'igniter_' . bin2hex(random_bytes(4)) . '_',
    ]);
    $originalMaxUrlLength = CacheIgniter::$plugin->settings->maxUrlLength;
    CacheIgniter::$plugin->settings->maxUrlLength = $maxUrlLength;
    Craft::$app->set('db', $connection);
    $migration = new m261123_120000_update_url_queue(['db' => $connection, 'compact' => true]);
    $table = UrlRecord::tableName();

    try {
        $migration->createTable($table, [
            'url' => $migration->string(500)->notNull(),
            'warmDate' => $migration->dateTime(),
            'PRIMARY KEY ([[url]])',
        ]);
        if ($charset !== null) {
            $migration->alterColumn($table, 'url', 'varchar(500) CHARACTER SET ' . $charset . ' NOT NULL');
        }
        $url = 'https://example.com/Übergröße?value=A';
        $connection->createCommand()->insert($table, ['url' => $url, 'warmDate' => '2026-12-01 00:00:00'])->execute();

        expect($migration->safeUp())->toBeTrue();
        $row = UrlRecord::find()->asArray()->one($connection);
        expect($row['url'])->toBe($url)
            ->and($row['urlHash'])->toBe(md5($url))
            ->and($row['warmDate'])->toBe('2026-12-01 00:00:00')
            ->and((int)$row['priority'])->toBe(CacheIgniter::$plugin->settings->warmJobPriority)
            ->and((int)$row['id'])->toBeGreaterThan(0)
            ->and(strlen($row['revision']))->toBe(36)
            ->and($connection->getTableSchema($table, true)->primaryKey)->toBe(['id'])
            ->and(Db::findIndex($table, ['urlHash'], true, $connection))->not->toBeNull()
            ->and($connection->getTableSchema($table, true)->columns['url']->size)->toBe($maxUrlLength);

        if ($connection->getIsMysql()) {
            expect(array_keys($connection->getTableSchema($table, true)->columns))
                ->toBe(['id', 'urlHash', 'url', 'warmDate', 'priority', 'revision']);
        }

        foreach (['https://example.com/page', 'https://example.com/Page', 'https://example.com/' . str_repeat('a', $maxUrlLength - strlen('https://example.com/'))] as $newUrl) {
            $connection->createCommand()->insert($table, [
                'url' => $newUrl,
                'urlHash' => md5($newUrl),
                'warmDate' => $row['warmDate'],
                'priority' => 110,
                'revision' => $row['revision'],
            ])->execute();
        }
        expect($migration->safeUp())->toBeTrue()
            ->and(UrlRecord::find()->count('*', $connection))->toEqual(4);
    } finally {
        try {
            $migration->dropTableIfExists($table);
        } finally {
            CacheIgniter::$plugin->settings->maxUrlLength = $originalMaxUrlLength;
            Craft::$app->set('db', $db);
            $connection->close();
        }
    }
})->with([
    'default charset' => [null, 2048],
    'legacy MySQL charset' => ['latin1', 2048],
    'configured maximum' => [null, 500],
]);
