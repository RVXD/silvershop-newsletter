<?php

declare(strict_types=1);

namespace SilverShop\Newsletter\Provider;

use SilverShop\Newsletter\Model\NewsletterSubscription;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Injector\Injectable;

/**
 * The default provider: a no-op that keeps the subscriber list local only. Every shop has a working newsletter
 * list (signup, double-opt-in, unsubscribe, consent, the subscribers admin + report) with zero ESP configured;
 * installing an ESP adapter and making it the active provider is purely additive.
 *
 * Because it is not {@see isRemote()}, the core never enqueues a sync job or exposes a webhook for it.
 */
class LocalProvider implements NewsletterProvider
{
    use Injectable;

    public function getKey(): string
    {
        return 'local';
    }

    public function getName(): string
    {
        return _t(self::class . '.NAME', 'Local list only');
    }

    public function isConfigured(): bool
    {
        return true;
    }

    public function isRemote(): bool
    {
        return false;
    }

    public function supportsDoubleOptIn(): bool
    {
        return true;
    }

    public function supportsEcommerce(): bool
    {
        return false;
    }

    public function supportsMergeFields(): bool
    {
        return false;
    }

    public function supportsTags(): bool
    {
        return false;
    }

    public function supportsSegments(): bool
    {
        return false;
    }

    public function supportsWebhooks(): bool
    {
        return false;
    }

    public function subscribe(NewsletterSubscription $subscription): bool
    {
        return true;
    }

    public function update(NewsletterSubscription $subscription): bool
    {
        return true;
    }

    public function unsubscribe(NewsletterSubscription $subscription): bool
    {
        return true;
    }

    public function syncAll(): int
    {
        return 0;
    }

    public function verifyWebhook(HTTPRequest $request): bool
    {
        return false;
    }

    public function parseWebhook(HTTPRequest $request): array
    {
        return [];
    }
}
