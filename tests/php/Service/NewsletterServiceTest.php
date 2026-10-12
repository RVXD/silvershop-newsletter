<?php

declare(strict_types=1);

namespace SilverShop\Newsletter\Tests\Service;

use SilverShop\Newsletter\Model\NewsletterSubscription;
use SilverShop\Newsletter\Service\NewsletterService;
use SilverStripe\Dev\SapphireTest;

class NewsletterServiceTest extends SapphireTest
{
    protected $usesDatabase = true;

    public function testGuestSubscribeIsPendingAndEmailsConfirmation(): void
    {
        NewsletterService::config()->set('double_opt_in', true);
        $subscription = NewsletterService::create()->subscribe('guest@example.com', ['Locale' => 'en_US']);

        $this->assertInstanceOf(NewsletterSubscription::class, $subscription);
        $this->assertSame(NewsletterSubscription::STATUS_PENDING, $subscription->Status);
        $this->assertFalse((bool) $subscription->Consent);
        $this->assertEmailSent('guest@example.com');
    }

    public function testDoubleOptInOffAutoConfirms(): void
    {
        NewsletterService::config()->set('double_opt_in', false);
        $subscription = NewsletterService::create()->subscribe('eager@example.com', ['Locale' => 'en_US']);

        $this->assertSame(NewsletterSubscription::STATUS_SUBSCRIBED, $subscription->Status);
        $this->assertTrue((bool) $subscription->Consent);
    }

    public function testInvalidEmailReturnsNull(): void
    {
        $this->assertNull(NewsletterService::create()->subscribe('not-an-email'));
        $this->assertNull(NewsletterService::create()->subscribe(''));
    }

    public function testEmailIsNormalisedAndDeduped(): void
    {
        $service = NewsletterService::create();
        $first = $service->subscribe('Mixed@Example.com', ['Locale' => 'en_US']);
        $second = $service->subscribe('mixed@example.com', ['Locale' => 'en_US']);

        $this->assertSame($first->ID, $second->ID, 'Same email + locale upserts one record');
        $this->assertSame('mixed@example.com', $first->Email, 'Email is lower-cased');
    }

    public function testConfirmByToken(): void
    {
        NewsletterService::config()->set('double_opt_in', true);
        $service = NewsletterService::create();
        $subscription = $service->subscribe('confirm@example.com', ['Locale' => 'en_US']);
        $this->assertSame(NewsletterSubscription::STATUS_PENDING, $subscription->Status);

        $confirmed = $service->confirmByToken((string) $subscription->Token);
        $this->assertSame(NewsletterSubscription::STATUS_SUBSCRIBED, $confirmed->Status);
        $this->assertTrue($confirmed->isSubscribed());

        $this->assertNull($service->confirmByToken('nonexistent-token'));
    }

    public function testUnsubscribeByToken(): void
    {
        $service = NewsletterService::create();
        NewsletterService::config()->set('double_opt_in', false);
        $subscription = $service->subscribe('bye@example.com', ['Locale' => 'en_US']);

        $out = $service->unsubscribeByToken((string) $subscription->Token);
        $this->assertSame(NewsletterSubscription::STATUS_UNSUBSCRIBED, $out->Status);
        $this->assertNotEmpty($out->UnsubscribedAt);
    }

    public function testApplyWebhookEvent(): void
    {
        $service = NewsletterService::create();
        NewsletterService::config()->set('double_opt_in', false);
        $service->subscribe('bounce@example.com', ['Locale' => 'en_US']);

        $out = $service->applyWebhookEvent('bounce@example.com', NewsletterSubscription::STATUS_BOUNCED);
        $this->assertSame(NewsletterSubscription::STATUS_BOUNCED, $out->Status);

        $this->assertNull($service->applyWebhookEvent('unknown@example.com', NewsletterSubscription::STATUS_BOUNCED));
        $this->assertNull($service->applyWebhookEvent('bounce@example.com', 'NotAStatus'));
    }
}
