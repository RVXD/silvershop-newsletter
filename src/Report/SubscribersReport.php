<?php

declare(strict_types=1);

namespace SilverShop\Newsletter\Report;

use SilverShop\Newsletter\Model\NewsletterSubscription;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Reports\Report;

/**
 * Newsletter subscribers, filterable by status and source. Gives shop operators the Magento-style subscribers
 * grid alongside the ModelAdmin, including consent timestamps for GDPR record-keeping.
 */
class SubscribersReport extends Report
{
    public function title()
    {
        return _t(__CLASS__ . '.TITLE', 'Newsletter subscribers');
    }

    public function description()
    {
        return _t(
            __CLASS__ . '.DESC',
            'All newsletter subscribers with their status, source and marketing-consent timestamp.'
        );
    }

    public function group()
    {
        return _t('SilverShop\\Reports.GROUP', 'Shop');
    }

    public function sort()
    {
        return 320;
    }

    public function parameterFields()
    {
        return FieldList::create(
            DropdownField::create('Status', _t(__CLASS__ . '.Status', 'Status'), [
                '' => _t(__CLASS__ . '.AnyStatus', '(any)'),
                NewsletterSubscription::STATUS_PENDING => _t(__CLASS__ . '.Pending', 'Pending'),
                NewsletterSubscription::STATUS_SUBSCRIBED => _t(__CLASS__ . '.Subscribed', 'Subscribed'),
                NewsletterSubscription::STATUS_UNSUBSCRIBED => _t(__CLASS__ . '.Unsubscribed', 'Unsubscribed'),
                NewsletterSubscription::STATUS_BOUNCED => _t(__CLASS__ . '.Bounced', 'Bounced'),
                NewsletterSubscription::STATUS_CLEANED => _t(__CLASS__ . '.Cleaned', 'Cleaned'),
            ]),
            DropdownField::create('Source', _t(__CLASS__ . '.Source', 'Source'), [
                '' => _t(__CLASS__ . '.AnySource', '(any)'),
                'footer' => _t(__CLASS__ . '.SourceFooter', 'Footer / sidebar form'),
                'checkout' => _t(__CLASS__ . '.SourceCheckout', 'Checkout'),
                'account' => _t(__CLASS__ . '.SourceAccount', 'Account'),
                'import' => _t(__CLASS__ . '.SourceImport', 'Import'),
                'api' => _t(__CLASS__ . '.SourceApi', 'API'),
            ])
        );
    }

    /**
     * @param array<string, mixed>|null $params
     */
    public function sourceRecords($params = null)
    {
        $list = NewsletterSubscription::get();
        if (!empty($params['Status'])) {
            $list = $list->filter('Status', $params['Status']);
        }
        if (!empty($params['Source'])) {
            $list = $list->filter('Source', $params['Source']);
        }

        return $list->sort('Created', 'DESC');
    }

    public function columns()
    {
        return [
            'Email' => _t(__CLASS__ . '.ColEmail', 'Email'),
            'Status' => _t(__CLASS__ . '.ColStatus', 'Status'),
            'Source' => _t(__CLASS__ . '.ColSource', 'Source'),
            'Locale' => _t(__CLASS__ . '.ColLocale', 'Locale'),
            'ConsentAt.Nice' => _t(__CLASS__ . '.ColConsent', 'Consent given'),
            'Created.Nice' => _t(__CLASS__ . '.ColCreated', 'Created'),
        ];
    }
}
