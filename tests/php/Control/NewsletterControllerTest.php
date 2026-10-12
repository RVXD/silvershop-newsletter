<?php

declare(strict_types=1);

namespace SilverShop\Newsletter\Tests\Control;

use SilverShop\Newsletter\Model\NewsletterSubscription;
use SilverShop\Newsletter\Provider\LocalProvider;
use SilverShop\Newsletter\Provider\NewsletterProviderRegistry;
use SilverShop\Newsletter\Service\NewsletterService;
use SilverShop\Newsletter\Tests\Provider\FakeRemoteProvider;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\FunctionalTest;

class NewsletterControllerTest extends FunctionalTest
{
    protected $usesDatabase = true;

    public function testIndexRenders(): void
    {
        $response = $this->get('newsletter');
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testConfirmByTokenLanding(): void
    {
        NewsletterService::config()->set('double_opt_in', true);
        $subscription = NewsletterService::create()->subscribe('land@example.com', ['Locale' => 'en_US']);
        $this->assertSame(NewsletterSubscription::STATUS_PENDING, $subscription->Status);

        $response = $this->get('newsletter/confirm/' . $subscription->Token);
        $this->assertSame(200, $response->getStatusCode());

        $subscription = NewsletterSubscription::get()->byID($subscription->ID);
        $this->assertSame(NewsletterSubscription::STATUS_SUBSCRIBED, $subscription->Status);
    }

    public function testUnsubscribeByTokenLanding(): void
    {
        NewsletterService::config()->set('double_opt_in', false);
        $subscription = NewsletterService::create()->subscribe('leave@example.com', ['Locale' => 'en_US']);

        $response = $this->get('newsletter/unsubscribe/' . $subscription->Token);
        $this->assertSame(200, $response->getStatusCode());

        $subscription = NewsletterSubscription::get()->byID($subscription->ID);
        $this->assertSame(NewsletterSubscription::STATUS_UNSUBSCRIBED, $subscription->Status);
    }

    public function testInvalidTokenStillReturns200(): void
    {
        $response = $this->get('newsletter/confirm/not-a-real-token');
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testWebhookAppliesProviderEvents(): void
    {
        NewsletterService::config()->set('double_opt_in', false);
        NewsletterService::create()->subscribe('hooked@example.com', ['Locale' => 'en_US']);

        NewsletterProviderRegistry::config()->set('providers', [
            'local' => LocalProvider::class,
            'fake' => FakeRemoteProvider::class,
        ]);
        NewsletterProviderRegistry::config()->set('active', 'fake');

        /** @var FakeRemoteProvider $fake */
        $fake = Injector::inst()->get(FakeRemoteProvider::class);
        $fake->verify = true;
        $fake->webhookEvents = [
            ['email' => 'hooked@example.com', 'status' => NewsletterSubscription::STATUS_UNSUBSCRIBED],
        ];

        $response = $this->post('newsletter/webhook/fake', ['payload' => '1']);
        $this->assertSame(200, $response->getStatusCode());

        $subscription = NewsletterSubscription::get()->filter('Email', 'hooked@example.com')->first();
        $this->assertSame(NewsletterSubscription::STATUS_UNSUBSCRIBED, $subscription->Status);
    }

    public function testWebhookUnknownProvider404(): void
    {
        $response = $this->post('newsletter/webhook/nope', ['payload' => '1']);
        $this->assertSame(404, $response->getStatusCode());
    }
}
