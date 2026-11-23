<?php

use craft\queue\Queue;
use putyourlightson\cacheigniter\CacheIgniter;
use putyourlightson\cacheigniter\records\UrlRecord;
use putyourlightson\cacheigniter\tests\TestCase as IsolatedTestCase;

require_once __DIR__ . '/TestCase.php';

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

uses(IsolatedTestCase::class)
    ->in('Architecture');

uses(IsolatedTestCase::class)
    ->beforeEach(function() {
        $this->transaction = Craft::$app->getDb()->beginTransaction();
        $this->originalAppQueue = Craft::$app->getQueue();
        $this->originalQueue = CacheIgniter::$plugin->queue;
        $this->originalWarmer = CacheIgniter::$plugin->get('warmer');
        $this->settings = CacheIgniter::$plugin->settings->getAttributes();
        $queue = new Queue(['channel' => 'igniter-feature-' . bin2hex(random_bytes(6))]);
        Craft::$app->set('queue', $queue);
        CacheIgniter::$plugin->queue = $queue;
        UrlRecord::deleteAll();
    })
    ->afterEach(function() {
        $this->transaction->rollBack();
        Craft::$app->set('queue', $this->originalAppQueue);
        CacheIgniter::$plugin->queue = $this->originalQueue;
        CacheIgniter::$plugin->set('warmer', $this->originalWarmer);
        CacheIgniter::$plugin->settings->setAttributes($this->settings, false);
    })
    ->in('Feature');

uses(IsolatedTestCase::class)
    ->in('Isolated');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toHaveOneRecordAndQueueJob', function() {
    expect(UrlRecord::find()->count())
        ->toEqual(1)
        ->and(UrlRecord::find()->one()->url)
        ->toBe($this->value)
        ->and(Craft::$app->queue->getJobInfo())
        ->toHaveCount(1);

    return $this;
});

expect()->extend('toHaveNoRecordNorQueueJob', function() {
    expect(UrlRecord::find()->count())
        ->toEqual(0)
        ->and(Craft::$app->queue->getJobInfo())
        ->toHaveCount(0);

    return $this;
});

/*
|--------------------------------------------------------------------------
| Constants
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/
