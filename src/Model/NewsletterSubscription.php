<?php

declare(strict_types=1);

namespace SilverShop\Newsletter\Model;

use SilverStripe\Control\Director;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;

/**
 * A single newsletter subscriber record. It is the canonical marketing-consent source of truth for the shop:
 * every subscriber carries an explicit {@link $Consent} flag + {@link $ConsentAt} timestamp, a {@link $Source}
 * (where they opted in) and a {@link $Status} that tracks the subscription lifecycle
 * (pending double-opt-in → subscribed → unsubscribed / bounced / cleaned).
 *
 * The {@link $Token} (a 128-bit hex string) backs the tokenised double-opt-in confirm and one-click unsubscribe
 * landings, so no login is needed. A nullable {@link Member} relation links the record to a shop account when one
 * is known. {@link getMergeFields()} exposes the first-name / surname / locale merge data an ESP adapter pushes.
 *
 * @property string $Email
 * @property string $Status
 * @property bool $Consent
 * @property ?string $ConsentAt
 * @property string $Source
 * @property string $Locale
 * @property string $FirstName
 * @property string $Surname
 * @property string $Token
 * @property ?string $ConfirmedAt
 * @property ?string $UnsubscribedAt
 * @property ?string $SyncedAt
 * @property string $ExternalID
 * @property int $MemberID
 * @method Member Member()
 */
class NewsletterSubscription extends DataObject
{
    public const STATUS_PENDING = 'Pending';

    public const STATUS_SUBSCRIBED = 'Subscribed';

    public const STATUS_UNSUBSCRIBED = 'Unsubscribed';

    public const STATUS_BOUNCED = 'Bounced';

    public const STATUS_CLEANED = 'Cleaned';

    private static string $table_name = 'SilverShop_NewsletterSubscription';

    private static string $singular_name = 'Newsletter subscriber';

    private static string $plural_name = 'Newsletter subscribers';

    private static array $db = [
        'Email' => 'Varchar(254)',
        'Status' => "Enum('Pending,Subscribed,Unsubscribed,Bounced,Cleaned','Pending')",
        'Consent' => 'Boolean',
        'ConsentAt' => 'Datetime',
        'Source' => "Enum('footer,checkout,account,import,api','footer')",
        'Locale' => 'Varchar(10)',
        'FirstName' => 'Varchar(100)',
        'Surname' => 'Varchar(100)',
        'Token' => 'Varchar(64)',
        'ConfirmedAt' => 'Datetime',
        'UnsubscribedAt' => 'Datetime',
        'SyncedAt' => 'Datetime',
        'ExternalID' => 'Varchar(128)',
    ];

    private static array $has_one = [
        'Member' => Member::class,
    ];

    private static array $indexes = [
        'Token' => ['type' => 'unique', 'columns' => ['Token']],
        'EmailLocale' => ['type' => 'index', 'columns' => ['Email', 'Locale']],
        'Status' => true,
    ];

    private static string $default_sort = 'Created DESC';

    private static array $summary_fields = [
        'Email' => 'Email',
        'Status' => 'Status',
        'Source' => 'Source',
        'Locale' => 'Locale',
        'ConsentAt.Nice' => 'Consent given',
        'Created.Nice' => 'Created',
    ];

    private static array $searchable_fields = [
        'Email',
        'Status',
        'Source',
    ];

    private static array $field_labels = [
        'Email' => 'Email address',
        'ConsentAt' => 'Consent given at',
        'ConfirmedAt' => 'Confirmed at',
        'UnsubscribedAt' => 'Unsubscribed at',
        'SyncedAt' => 'Last synced at',
        'ExternalID' => 'ESP contact ID',
    ];

    protected function onBeforeWrite(): void
    {
        parent::onBeforeWrite();

        if (!$this->Token) {
            $this->Token = bin2hex(random_bytes(16));
        }
    }

    /**
     * Whether this subscriber currently receives marketing (consented + actively subscribed).
     */
    public function isSubscribed(): bool
    {
        return (bool) $this->Consent && $this->Status === self::STATUS_SUBSCRIBED;
    }

    /**
     * Mark the record confirmed + subscribed, stamping the consent / confirm timestamps when first set.
     */
    public function confirm(): void
    {
        $now = DBDatetime::now()->getValue();
        $this->Status = self::STATUS_SUBSCRIBED;
        $this->Consent = true;
        if (!$this->ConsentAt) {
            $this->ConsentAt = $now;
        }
        if (!$this->ConfirmedAt) {
            $this->ConfirmedAt = $now;
        }
    }

    /**
     * Mark the record unsubscribed, stamping the timestamp.
     */
    public function markUnsubscribed(): void
    {
        $this->Status = self::STATUS_UNSUBSCRIBED;
        $this->UnsubscribedAt = DBDatetime::now()->getValue();
    }

    /**
     * Merge data an ESP adapter maps onto its contact fields (FNAME / LNAME / locale …).
     *
     * @return array<string, string>
     */
    public function getMergeFields(): array
    {
        $fields = [
            'FirstName' => (string) $this->FirstName,
            'Surname' => (string) $this->Surname,
            'Locale' => (string) $this->Locale,
        ];
        $this->extend('updateMergeFields', $fields);

        return array_filter($fields, static fn ($value): bool => $value !== '');
    }

    /**
     * Absolute URL that confirms (double-opt-in) this subscription.
     */
    public function ConfirmLink(): string
    {
        return Director::absoluteURL('newsletter/confirm/' . $this->Token);
    }

    /**
     * Absolute URL that unsubscribes this record.
     */
    public function UnsubscribeLink(): string
    {
        return Director::absoluteURL('newsletter/unsubscribe/' . $this->Token);
    }

    public function canView($member = null): bool
    {
        return $this->canModerate($member);
    }

    public function canEdit($member = null): bool
    {
        return $this->canModerate($member);
    }

    public function canCreate($member = null, $context = []): bool
    {
        return $this->canModerate($member);
    }

    public function canDelete($member = null): bool
    {
        return $this->canModerate($member);
    }

    public function canModerate($member = null): bool
    {
        return Permission::check('CMS_ACCESS_SilverShop\Newsletter\Admin\NewsletterAdmin', 'any', $member)
            || Permission::check('ADMIN', 'any', $member);
    }
}
