<?php

declare(strict_types=1);

namespace SilverShop\Newsletter\Tests\Service;

use SilverShop\Newsletter\Model\NewsletterSubscription;
use SilverShop\Newsletter\Service\NewsletterConsent;
use SilverStripe\Dev\SapphireTest;

class NewsletterConsentTest extends SapphireTest
{
    protected $usesDatabase = true;

    public function testHasReturnsTrueForConsentingSubscriber(): void
    {
        $subscription = NewsletterSubscription::create();
        $subscription->Email = 'yes@example.com';
        $subscription->confirm();
        $subscription->write();

        $this->assertTrue(NewsletterConsent::has('yes@example.com'));
        $this->assertTrue(NewsletterConsent::has('YES@EXAMPLE.COM'), 'Lookup is case-insensitive');
        $this->assertInstanceOf(NewsletterSubscription::class, NewsletterConsent::subscription('yes@example.com'));
    }

    public function testHasReturnsFalseForNonConsenting(): void
    {
        $pending = NewsletterSubscription::create();
        $pending->Email = 'pending@example.com';
        $pending->Status = NewsletterSubscription::STATUS_PENDING;
        $pending->write();

        $unsub = NewsletterSubscription::create();
        $unsub->Email = 'left@example.com';
        $unsub->confirm();
        $unsub->markUnsubscribed();
        $unsub->write();

        $this->assertFalse(NewsletterConsent::has('pending@example.com'));
        $this->assertFalse(NewsletterConsent::has('left@example.com'));
        $this->assertFalse(NewsletterConsent::has('unknown@example.com'));
        $this->assertFalse(NewsletterConsent::has(''));
    }
}
