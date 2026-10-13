<?php

declare(strict_types=1);

namespace SilverShop\Newsletter\Extension;

use SilverShop\Newsletter\Service\NewsletterService;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Extension;

/**
 * Reads the checkout opt-in checkbox back when the order is placed and upserts a subscription for the order's
 * email. Uses the order's `onPlaceOrder` extend hook (core's OrderProcessor fires it mid-placement, while the
 * submitting request — carrying the checkbox POST value — is still the current controller request).
 *
 * Only attached when silvershop/core is installed (guarded in the module's _config.php). Guests go through the
 * normal double-opt-in; a logged-in member is auto-confirmed per NewsletterService config.
 *
 * Slice 3 ecommerce hook point: when a remote provider that supportsEcommerce() is active, this is where an
 * order/customer push would be enqueued. No shipped ESP adapter needs it yet, so it is left as the seam.
 */
class NewsletterOrderExtension extends Extension
{
    public function onPlaceOrder(): void
    {
        $controller = Controller::curr();
        if ($controller === null) {
            return;
        }
        $request = $controller->getRequest();
        if (!$request || !$request->postVar('SubscribeNewsletter')) {
            return;
        }

        $order = $this->getOwner();
        $email = (string) $order->getLatestEmail();
        if ($email === '') {
            return;
        }

        $member = $order->Member();
        NewsletterService::create()->subscribe($email, [
            'Source' => 'checkout',
            'FirstName' => (string) $order->FirstName,
            'Surname' => (string) $order->Surname,
            'Member' => ($member && $member->exists()) ? $member : null,
        ]);
    }
}
