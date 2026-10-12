<?php

declare(strict_types=1);

namespace SilverShop\Newsletter\Extension;

use SilverShop\Newsletter\Model\NewsletterSubscription;
use SilverShop\Newsletter\Service\NewsletterConsent;
use SilverShop\Newsletter\Service\NewsletterService;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Security\Security;

/**
 * Adds an "Email preferences" section to the shop account area: an `account/newsletter` page with a single
 * subscribe/unsubscribe toggle bound to the member's current consent state. Reuses the module's landing
 * template, so it needs no theme template of its own.
 *
 * Only attached when silvershop/core is installed (guarded in the module's _config.php).
 */
class NewsletterAccountExtension extends Extension
{
    private static array $allowed_actions = [
        'newsletter',
        'NewsletterPreferencesForm',
    ];

    public function newsletter(): HTTPResponse
    {
        $owner = $this->getOwner();
        $html = $owner->customise([
            'Title' => _t(self::class . '.Title', 'Email preferences'),
            'ShopName' => NewsletterService::create()->senderName(),
            'State' => 'preferences',
            'IsPreferences' => true,
            'SignupForm' => null,
            'PreferencesForm' => $this->NewsletterPreferencesForm(),
        ])->renderWith('SilverShop\\Newsletter\\NewsletterPage');

        return HTTPResponse::create((string) $html);
    }

    public function NewsletterPreferencesForm(): Form
    {
        $member = Security::getCurrentUser();
        $subscribed = $member && $member->exists() && NewsletterConsent::has((string) $member->Email);

        $fields = FieldList::create(
            CheckboxField::create(
                'Subscribed',
                _t(self::class . '.Subscribe', 'Subscribe me to the newsletter'),
                $subscribed
            )
        );
        $actions = FieldList::create(
            FormAction::create('saveNewsletterPreferences', _t(self::class . '.Save', 'Save preferences'))
        );

        return Form::create($this->getOwner(), 'NewsletterPreferencesForm', $fields, $actions);
    }

    public function saveNewsletterPreferences(array $data, Form $form): HTTPResponse
    {
        $member = Security::getCurrentUser();
        if ($member && $member->exists()) {
            $email = (string) $member->Email;
            if (!empty($data['Subscribed'])) {
                NewsletterService::create()->subscribe($email, [
                    'Source' => 'account',
                    'Member' => $member,
                    'FirstName' => (string) $member->FirstName,
                    'Surname' => (string) $member->Surname,
                ]);
            } else {
                $subscription = NewsletterConsent::subscription($email)
                    ?? NewsletterSubscription::get()->filter('Email', $email)->first();
                if ($subscription) {
                    NewsletterService::create()->unsubscribeByToken((string) $subscription->Token);
                }
            }
            $form->sessionMessage(_t(self::class . '.Saved', 'Your email preferences have been saved.'), 'good');
        }

        return $this->getOwner()->redirectBack();
    }
}
