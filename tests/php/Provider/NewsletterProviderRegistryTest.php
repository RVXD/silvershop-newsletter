<?php

declare(strict_types=1);

namespace SilverShop\Newsletter\Tests\Provider;

use SilverShop\Newsletter\Provider\LocalProvider;
use SilverShop\Newsletter\Provider\NewsletterProviderRegistry;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;

class NewsletterProviderRegistryTest extends SapphireTest
{
    protected $usesDatabase = false;

    public function testDefaultActiveIsLocalAndNotRemote(): void
    {
        NewsletterProviderRegistry::config()->set('active', 'local');
        NewsletterProviderRegistry::config()->set('providers', ['local' => LocalProvider::class]);

        $registry = NewsletterProviderRegistry::create();
        $this->assertInstanceOf(LocalProvider::class, $registry->active());
        $this->assertNull($registry->activeRemote(), 'The local list is not a remote provider');
    }

    public function testActiveRemoteProviderSelected(): void
    {
        NewsletterProviderRegistry::config()->set('providers', [
            'local' => LocalProvider::class,
            'fake' => FakeRemoteProvider::class,
        ]);
        NewsletterProviderRegistry::config()->set('active', 'fake');

        $registry = NewsletterProviderRegistry::create();
        $this->assertSame('fake', $registry->active()->getKey());
        $this->assertNotNull($registry->activeRemote());
        $this->assertSame('fake', $registry->activeRemote()->getKey());
    }

    public function testUnconfiguredProviderFallsBackToLocal(): void
    {
        /** @var FakeRemoteProvider $fake */
        $fake = Injector::inst()->get(FakeRemoteProvider::class);
        $fake->configured = false;

        NewsletterProviderRegistry::config()->set('providers', [
            'local' => LocalProvider::class,
            'fake' => FakeRemoteProvider::class,
        ]);
        NewsletterProviderRegistry::config()->set('active', 'fake');

        $registry = NewsletterProviderRegistry::create();
        $this->assertInstanceOf(LocalProvider::class, $registry->active());
        $this->assertNull($registry->activeRemote());

        $fake->configured = true;
    }
}
