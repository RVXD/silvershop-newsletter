<?php

declare(strict_types=1);

namespace SilverShop\Newsletter\Job;

use SilverShop\Newsletter\Model\NewsletterSubscription;
use SilverShop\Newsletter\Provider\NewsletterProviderRegistry;
use SilverStripe\ORM\FieldType\DBDatetime;
use Symbiote\QueuedJobs\Services\AbstractQueuedJob;
use Symbiote\QueuedJobs\Services\QueuedJob;
use Symbiote\QueuedJobs\Services\QueuedJobService;

/**
 * Pushes one subscriber change to the active (remote) ESP provider, as a retryable background job that is
 * visible and auditable in QueuedJobAdmin — the reason symbiote/silverstripe-queuedjobs is a hard requirement
 * of this module (the 2026-10-11 suite decision). One job per change; {@see NewsletterResyncTask} is the
 * CLI full-resync runner over the same provider.
 *
 * @property int $SubscriptionID
 * @property string $SyncAction
 */
class NewsletterSyncJob extends AbstractQueuedJob
{
    public const ACTION_SUBSCRIBE = 'subscribe';

    public const ACTION_UPDATE = 'update';

    public const ACTION_UNSUBSCRIBE = 'unsubscribe';

    // The retry policy (retry_max_attempts / retry_initial_delay / retry_falloff_multiplier — inherited config
    // properties read by the queuedjobs service) is set in _config/newsletter.yml so a stuck/failed sync is
    // retried a few times with a growing delay for transient ESP/network errors.

    public function __construct(int $subscriptionID = 0, string $action = self::ACTION_SUBSCRIBE)
    {
        if ($subscriptionID) {
            $this->SubscriptionID = $subscriptionID;
            $this->SyncAction = $action;
        }
    }

    public function getTitle(): string
    {
        return _t(
            self::class . '.TITLE',
            'Sync newsletter subscriber #{id} ({action}) to the ESP',
            ['id' => (int) $this->SubscriptionID, 'action' => (string) $this->SyncAction]
        );
    }

    public function getJobType(): string
    {
        return QueuedJob::QUEUED;
    }

    public function process(): void
    {
        $this->currentStep++;

        $subscription = NewsletterSubscription::get()->byID((int) $this->SubscriptionID);
        $provider = NewsletterProviderRegistry::create()->activeRemote();

        // Nothing to do if the record is gone or no remote provider is active — treat as a successful no-op.
        if (!$subscription || !$provider) {
            $this->isComplete = true;
            return;
        }

        $ok = match ($this->SyncAction) {
            self::ACTION_UNSUBSCRIBE => $provider->unsubscribe($subscription),
            self::ACTION_UPDATE => $provider->update($subscription),
            default => $provider->subscribe($subscription),
        };

        if (!$ok) {
            // Let queuedjobs mark the job broken + retry per the retry_* config.
            throw new \RuntimeException(sprintf(
                'ESP sync (%s) failed for subscriber #%d',
                (string) $this->SyncAction,
                (int) $subscription->ID
            ));
        }

        $subscription->SyncedAt = DBDatetime::now()->getValue();
        $subscription->write();

        $this->addMessage(sprintf('Synced subscriber #%d (%s)', (int) $subscription->ID, (string) $this->SyncAction));
        $this->isComplete = true;
    }

    /**
     * Queue a sync for a subscription change, but only when a remote ESP provider is active. With the local
     * list in effect this is a no-op, so the queue stays clean on ESP-less installs.
     */
    public static function enqueue(NewsletterSubscription $subscription, string $action = self::ACTION_SUBSCRIBE): void
    {
        if (!$subscription->isInDB()) {
            return;
        }
        if (!NewsletterProviderRegistry::create()->activeRemote()) {
            return;
        }

        QueuedJobService::singleton()->queueJob(
            new NewsletterSyncJob((int) $subscription->ID, $action)
        );
    }
}
