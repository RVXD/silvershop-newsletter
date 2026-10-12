<?php

declare(strict_types=1);

namespace SilverShop\Newsletter\Tests\Provider;

use SilverShop\Newsletter\Model\NewsletterSubscription;
use SilverShop\Newsletter\Provider\NewsletterProvider;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Injector\Injectable;

/**
 * In-memory stand-in for a remote ESP provider — records calls and returns canned webhook events, so the
 * registry, sync job and webhook controller can be tested without live HTTP. Mirrors meilisearch's
 * FakeMeiliClient.
 */
class FakeRemoteProvider implements NewsletterProvider
{
    use Injectable;

    /** @var array<int, array{action: string, email: string}> */
    public array $calls = [];

    public bool $configured = true;

    public bool $verify = true;

    /** @var array<int, array{email: string, status: string}> */
    public array $webhookEvents = [];

    public function getKey(): string
    {
        return 'fake';
    }

    public function getName(): string
    {
        return 'Fake ESP';
    }

    public function isConfigured(): bool
    {
        return $this->configured;
    }

    public function isRemote(): bool
    {
        return true;
    }

    public function supportsDoubleOptIn(): bool
    {
        return false;
    }

    public function supportsEcommerce(): bool
    {
        return false;
    }

    public function supportsMergeFields(): bool
    {
        return true;
    }

    public function supportsTags(): bool
    {
        return true;
    }

    public function supportsSegments(): bool
    {
        return false;
    }

    public function supportsWebhooks(): bool
    {
        return true;
    }

    public function subscribe(NewsletterSubscription $subscription): bool
    {
        $this->calls[] = ['action' => 'subscribe', 'email' => (string) $subscription->Email];

        return true;
    }

    public function update(NewsletterSubscription $subscription): bool
    {
        $this->calls[] = ['action' => 'update', 'email' => (string) $subscription->Email];

        return true;
    }

    public function unsubscribe(NewsletterSubscription $subscription): bool
    {
        $this->calls[] = ['action' => 'unsubscribe', 'email' => (string) $subscription->Email];

        return true;
    }

    public function syncAll(): int
    {
        return count($this->calls);
    }

    public function verifyWebhook(HTTPRequest $request): bool
    {
        return $this->verify;
    }

    public function parseWebhook(HTTPRequest $request): array
    {
        return $this->webhookEvents;
    }
}
