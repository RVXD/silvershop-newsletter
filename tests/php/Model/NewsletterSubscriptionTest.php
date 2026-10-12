<?php

declare(strict_types=1);

namespace SilverShop\Newsletter\Tests\Model;

use SilverShop\Newsletter\Model\NewsletterSubscription;
use SilverStripe\Dev\SapphireTest;

class NewsletterSubscriptionTest extends SapphireTest
{
    protected $usesDatabase = true;

    public function testTokenGeneratedOnWrite(): void
    {
        $subscription = NewsletterSubscription::create();
        $subscription->Email = 'a@example.com';
        $subscription->write();

        $this->assertNotEmpty($subscription->Token);
        $this->assertSame(32, strlen($subscription->Token), 'Token is 16 random bytes as hex (32 chars)');
    }

    public function testTokenIsStableAcrossWrites(): void
    {
        $subscription = NewsletterSubscription::create();
        $subscription->Email = 'b@example.com';
        $subscription->write();
        $token = $subscription->Token;

        $subscription->FirstName = 'Changed';
        $subscription->write();

        $this->assertSame($token, $subscription->Token);
    }

    public function testConfirmStampsConsentAndStatus(): void
    {
        $subscription = NewsletterSubscription::create();
        $subscription->Email = 'c@example.com';
        $subscription->confirm();
        $subscription->write();

        $this->assertSame(NewsletterSubscription::STATUS_SUBSCRIBED, $subscription->Status);
        $this->assertTrue((bool) $subscription->Consent);
        $this->assertNotEmpty($subscription->ConsentAt);
        $this->assertNotEmpty($subscription->ConfirmedAt);
        $this->assertTrue($subscription->isSubscribed());
    }

    public function testMarkUnsubscribed(): void
    {
        $subscription = NewsletterSubscription::create();
        $subscription->Email = 'd@example.com';
        $subscription->confirm();
        $subscription->write();

        $subscription->markUnsubscribed();
        $subscription->write();

        $this->assertSame(NewsletterSubscription::STATUS_UNSUBSCRIBED, $subscription->Status);
        $this->assertNotEmpty($subscription->UnsubscribedAt);
        $this->assertFalse($subscription->isSubscribed());
    }

    public function testMergeFieldsOmitsEmptyValues(): void
    {
        $subscription = NewsletterSubscription::create();
        $subscription->Email = 'e@example.com';
        $subscription->FirstName = 'Ada';
        $subscription->Locale = 'en_US';
        $fields = $subscription->getMergeFields();

        $this->assertSame('Ada', $fields['FirstName']);
        $this->assertSame('en_US', $fields['Locale']);
        $this->assertArrayNotHasKey('Surname', $fields, 'Empty merge fields are dropped');
    }

    public function testLinksContainToken(): void
    {
        $subscription = NewsletterSubscription::create();
        $subscription->Email = 'f@example.com';
        $subscription->write();

        $token = $subscription->Token;
        $this->assertStringContainsString('newsletter/confirm/' . $token, $subscription->ConfirmLink());
        $this->assertStringContainsString('newsletter/unsubscribe/' . $token, $subscription->UnsubscribeLink());
    }
}
