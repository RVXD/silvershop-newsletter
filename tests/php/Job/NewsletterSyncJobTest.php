<?php

declare(strict_types=1);

namespace SilverShop\Newsletter\Tests\Job;

use SilverShop\Newsletter\Job\NewsletterSyncJob;
use SilverShop\Newsletter\Model\NewsletterSubscription;
use SilverShop\Newsletter\Provider\LocalProvider;
use SilverShop\Newsletter\Provider\NewsletterProviderRegistry;
use SilverShop\Newsletter\Tests\Provider\FakeRemoteProvider;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use Symbiote\QueuedJobs\DataObjects\QueuedJobDescriptor;

class NewsletterSyncJobTest extends SapphireTest
{
    protected $usesDatabase = true;

    protected function useFakeRemote(): FakeRemoteProvider
    {
        NewsletterProviderRegistry::config()->set('providers', [
            'local' => LocalProvider::class,
            'fake' => FakeRemoteProvider::class,
        ]);
        NewsletterProviderRegistry::config()->set('active', 'fake');

        /** @var FakeRemoteProvider $fake */
        $fake = Injector::inst()->get(FakeRemoteProvider::class);
        $fake->calls = [];
        $fake->configured = true;

        return $fake;
    }

    public function testProcessSyncsToRemoteProvider(): void
    {
        $fake = $this->useFakeRemote();

        $subscription = NewsletterSubscription::create();
        $subscription->Email = 'sync@example.com';
        $subscription->confirm();
        $subscription->write();

        $job = new NewsletterSyncJob((int) $subscription->ID, NewsletterSyncJob::ACTION_SUBSCRIBE);
        $job->process();

        $this->assertTrue($job->jobFinished());
        $this->assertCount(1, $fake->calls);
        $this->assertSame('subscribe', $fake->calls[0]['action']);
        $this->assertSame('sync@example.com', $fake->calls[0]['email']);

        $subscription = NewsletterSubscription::get()->byID($subscription->ID);
        $this->assertNotEmpty($subscription->SyncedAt, 'SyncedAt is stamped after a successful sync');
    }

    public function testProcessNoOpsWithoutRemoteProvider(): void
    {
        NewsletterProviderRegistry::config()->set('active', 'local');

        $subscription = NewsletterSubscription::create();
        $subscription->Email = 'local@example.com';
        $subscription->confirm();
        $subscription->write();

        $job = new NewsletterSyncJob((int) $subscription->ID, NewsletterSyncJob::ACTION_SUBSCRIBE);
        $job->process();

        $this->assertTrue($job->jobFinished(), 'No active remote → successful no-op');
    }

    public function testEnqueueSkippedWhenLocalActive(): void
    {
        NewsletterProviderRegistry::config()->set('active', 'local');

        $subscription = NewsletterSubscription::create();
        $subscription->Email = 'noqueue@example.com';
        $subscription->confirm();
        $subscription->write();

        NewsletterSyncJob::enqueue($subscription, NewsletterSyncJob::ACTION_SUBSCRIBE);

        $this->assertSame(
            0,
            QueuedJobDescriptor::get()->filter('Implementation', NewsletterSyncJob::class)->count(),
            'No sync job is queued while the local list is in effect'
        );
    }

    public function testEnqueueQueuesJobWhenRemoteActive(): void
    {
        $this->useFakeRemote();

        $subscription = NewsletterSubscription::create();
        $subscription->Email = 'queue@example.com';
        $subscription->confirm();
        $subscription->write();

        NewsletterSyncJob::enqueue($subscription, NewsletterSyncJob::ACTION_SUBSCRIBE);

        $this->assertSame(
            1,
            QueuedJobDescriptor::get()->filter('Implementation', NewsletterSyncJob::class)->count(),
            'A sync job is queued when a remote provider is active'
        );
    }
}
