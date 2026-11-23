<?php

use craft\helpers\Db;
use craft\queue\Queue;
use putyourlightson\cacheigniter\CacheIgniter;
use putyourlightson\cacheigniter\drivers\warmers\BaseWarmer;
use putyourlightson\cacheigniter\jobs\WarmJob;
use putyourlightson\cacheigniter\records\UrlRecord;

beforeEach(function() {
    $this->transaction = Craft::$app->getDb()->beginTransaction();
    $this->settings = CacheIgniter::$plugin->settings->getAttributes();
    $this->originalQueue = CacheIgniter::$plugin->queue;
    $this->originalWarmer = CacheIgniter::$plugin->get('warmer');
    $this->queue = new Queue(['channel' => 'igniter-test-' . bin2hex(random_bytes(6))]);
    CacheIgniter::$plugin->queue = $this->queue;
    CacheIgniter::$plugin->settings->warmJobBatchSize = 2;
    CacheIgniter::$plugin->settings->warmJobPriority = 110;
    $this->warmer = new class() extends BaseWarmer {
        public array $batches = [];
        public ?Closure $onWarm = null;
        public bool $fail = false;

        public function warmUrlsWithProgress(array $urls, ?callable $setProgressHandler = null): void
        {
            $this->batches[] = $urls;
            if ($this->onWarm !== null) {
                ($this->onWarm)($urls);
            }
            if ($this->fail) {
                throw new RuntimeException('Warming failed.');
            }
            if ($setProgressHandler !== null) {
                $setProgressHandler(count($urls), count($urls), 'Warmed URLs');
            }
        }
    };
    CacheIgniter::$plugin->set('warmer', $this->warmer);
    UrlRecord::deleteAll();
    $this->urls = ['https://example.com/a', 'https://example.com/b', 'https://example.com/c'];
    $this->drain = function() {
        $errors = [];
        $this->queue->on(Queue::EVENT_AFTER_ERROR, function($event) use (&$errors) {
            $errors[] = $event->error->getMessage();
        });
        for ($i = 0; $i < 30 && $this->queue->executeJob(); $i++) {
            expect($errors)->toBe([]);
        }
        expect($i)->toBeLessThan(30);
    };
});

afterEach(function() {
    $this->transaction->rollBack();
    CacheIgniter::$plugin->set('warmer', $this->originalWarmer);
    CacheIgniter::$plugin->queue = $this->originalQueue;
    CacheIgniter::$plugin->settings->setAttributes($this->settings, false);
});

test('Overlapping warm requests process every pending URL once across batches', function() {
    [$a, $b, $c] = $this->urls;
    CacheIgniter::$plugin->warm->warmUrls([$a, $b]);
    CacheIgniter::$plugin->warm->warmUrls([$b, $c, $a]);
    expect(CacheIgniter::$plugin->warm->getWarmableUrlCount())->toBe(3);

    ($this->drain)();

    expect($this->warmer->batches)->toBe([[$a, $b], [$c]])
        ->and(CacheIgniter::$plugin->warm->getWarmableUrlCount())->toBe(0);
});

test('Completing a batch leaves URLs outside its limit pending', function() {
    CacheIgniter::$plugin->warm->warmUrls($this->urls);
    expect(CacheIgniter::$plugin->warm->warmPendingUrls(limit: 2))->toBe(2)
        ->and(UrlRecord::find()->select('url')->column())->toBe([$this->urls[2]]);
});

test('URL hashes distinguish case, accents and long suffixes', function() {
    $baseUrl = 'https://example.com/';
    $prefix = $baseUrl . str_repeat('a', CacheIgniter::$plugin->settings->maxUrlLength - strlen($baseUrl) - 1);
    $urls = ['https://example.com/page', 'https://example.com/Page', 'https://example.com/páge', $prefix . 'a', $prefix . 'b'];
    CacheIgniter::$plugin->warm->warmUrls(array_merge($urls, $urls));
    expect(UrlRecord::find()->count())->toEqual(5)
        ->and(UrlRecord::find()->select('urlHash')->orderBy('id')->column())->toBe(array_map('md5', $urls));
    CacheIgniter::$plugin->warm->warmPendingUrls();
    expect($this->warmer->batches)->toBe([$urls]);
});

test('New requests made during warming survive completion of the older revision', function() {
    $url = $this->urls[0];
    CacheIgniter::$plugin->warm->warmUrls([$url]);
    $this->warmer->onWarm = function() use ($url) {
        expect(UrlRecord::find()->count())->toEqual(1);
        CacheIgniter::$plugin->warm->warmUrls([$url]);
        $this->warmer->onWarm = null;
    };
    CacheIgniter::$plugin->warm->warmPendingUrls();
    expect(UrlRecord::find()->count())->toEqual(1);
    CacheIgniter::$plugin->warm->warmPendingUrls();
    expect($this->warmer->batches)->toBe([[$url], [$url]])
        ->and(UrlRecord::find()->count())->toEqual(0);
});

test('An exception leaves the batch pending for retry', function() {
    CacheIgniter::$plugin->warm->warmUrls($this->urls);
    $this->warmer->fail = true;
    expect(fn() => CacheIgniter::$plugin->warm->warmPendingUrls(limit: 2))->toThrow(RuntimeException::class)
        ->and(UrlRecord::find()->count())->toEqual(3);
    $this->warmer->fail = false;
    expect(CacheIgniter::$plugin->warm->warmPendingUrls())->toBe(3)
        ->and(UrlRecord::find()->count())->toEqual(0);
});

test('Repeated requests promote priority without demoting existing work', function() {
    [$a, $b, $c] = $this->urls;
    CacheIgniter::$plugin->settings->warmJobPriority = 100;
    CacheIgniter::$plugin->warm->warmUrls([$a, $b]);
    CacheIgniter::$plugin->settings->warmJobPriority = 10;
    CacheIgniter::$plugin->warm->warmUrls([$c, $a]);
    CacheIgniter::$plugin->settings->warmJobPriority = 200;
    CacheIgniter::$plugin->warm->warmUrls([$a]);
    expect(CacheIgniter::$plugin->warm->getWarmableUrlPriority())->toBe(10);
    CacheIgniter::$plugin->warm->warmPendingUrls();
    expect($this->warmer->batches)->toBe([[$a, $c, $b]]);
});

test('Delayed requests remain pending until due and preserve the refresh delay during warming', function() {
    $url = $this->urls[0];
    CacheIgniter::$plugin->settings->refreshDelay = 30;
    CacheIgniter::$plugin->warm->warmUrls([$url], immediate: false);
    expect(CacheIgniter::$plugin->warm->warmPendingUrls())->toBe(0)
        ->and(UrlRecord::find()->count())->toEqual(1);
    Craft::$app->getDb()->createCommand()->update(UrlRecord::tableName(), ['warmDate' => Db::prepareDateForDb('-1 second')])->execute();
    $this->warmer->onWarm = function() use ($url) {
        CacheIgniter::$plugin->warm->warmUrls([$url], immediate: false);
    };
    CacheIgniter::$plugin->warm->warmPendingUrls();
    expect(UrlRecord::find()->count())->toEqual(1)
        ->and(CacheIgniter::$plugin->warm->getWarmableUrlCount())->toBe(0);
});

test('Warm jobs report current batch totals and prioritise continuation jobs', function() {
    CacheIgniter::$plugin->warm->warmUrls($this->urls);
    expect((new WarmJob())->getDescription())->toBe('Warming cached URLs (batch 1 of 2)');
    $this->queue->executeJob();
    $jobs = $this->queue->getJobInfo();
    expect($jobs)->toHaveCount(1)
        ->and((int)$this->queue->getJobDetails($jobs[0]['id'])['Priority'])->toBe(109)
        ->and((new WarmJob(['batchIndex' => 1]))->getDescription())->toBe('Warming cached URLs (batch 2 of 2)');
});

test('Immediate warming deduplicates its input and leaves pending work intact', function() {
    $url = $this->urls[0];
    CacheIgniter::$plugin->warm->warmUrls([$url]);
    CacheIgniter::$plugin->warm->warmUrls([$url, $url], fn() => null);
    expect($this->warmer->batches)->toBe([[$url]])
        ->and(UrlRecord::find()->count())->toEqual(1);
});

test('Empty or oversized requests do not create empty jobs', function() {
    CacheIgniter::$plugin->warm->warmUrls([]);
    CacheIgniter::$plugin->warm->warmUrls(['https://example.com/' . str_repeat('a', CacheIgniter::$plugin->settings->maxUrlLength)]);
    expect(UrlRecord::find()->count())->toEqual(0)
        ->and($this->queue->getJobInfo())->toBe([]);
});

test('Large requests enqueue in bounded chunks without losing URLs', function() {
    $urls = array_map(fn($id) => 'https://example.com/' . $id, range(1, 1001));
    CacheIgniter::$plugin->warm->warmUrls($urls);
    expect(UrlRecord::find()->count())->toEqual(1001);
    CacheIgniter::$plugin->warm->warmPendingUrls();
    expect($this->warmer->batches)->toBe([$urls])
        ->and(UrlRecord::find()->count())->toEqual(0);
});

test('Warm job retries respect the configured attempt limit', function() {
    CacheIgniter::$plugin->settings->maxRetryAttempts = 3;
    $job = new WarmJob();
    expect($job->canRetry(1, new RuntimeException()))->toBeTrue()
        ->and($job->canRetry(3, new RuntimeException()))->toBeFalse();
});
