<?php

declare(strict_types=1);

namespace SilverShop\Newsletter\Extension;

use SilverShop\Newsletter\Model\NewsletterSubscription;
use SilverShop\Newsletter\Service\NewsletterConsent;
use SilverStripe\Core\Extension;
use SilverStripe\Security\Member;

/**
 * Links a shop member to their newsletter subscription(s). The {@link NewsletterSubscription} remains the single
 * source of truth for consent — this extension only adds the back-relation and convenience accessors so the
 * account "Email preferences" section and other code can ask "is this member subscribed?" without duplicating
 * the consent state onto the Member.
 *
 * @extends Extension<Member>
 */
class NewsletterMemberExtension extends Extension
{
    private static array $has_many = [
        'NewsletterSubscriptions' => NewsletterSubscription::class . '.Member',
    ];

    /**
     * Whether the member's email currently has marketing consent + an active subscription.
     */
    public function getNewsletterSubscribed(): bool
    {
        $email = (string) $this->getOwner()->Email;

        return $email !== '' && NewsletterConsent::has($email);
    }

    /**
     * The member's active subscription (by email), or null.
     */
    public function NewsletterSubscription(): ?NewsletterSubscription
    {
        $email = (string) $this->getOwner()->Email;

        return $email !== '' ? NewsletterConsent::subscription($email) : null;
    }
}
