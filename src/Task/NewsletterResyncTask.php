<?php

declare(strict_types=1);

namespace SilverShop\Newsletter\Task;

use SilverShop\Newsletter\Model\NewsletterSubscription;
use SilverShop\Newsletter\Provider\NewsletterProviderRegistry;
use SilverStripe\Dev\BuildTask;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * CLI full-resync of the local subscriber list to the active ESP provider, over the same provider the queued
 * {@see \SilverShop\Newsletter\Job\NewsletterSyncJob} uses (the task-over-service split). Dry-run by default —
 * it reports what it would push; pass --run to actually sync.
 */
class NewsletterResyncTask extends BuildTask
{
    protected static string $commandName = 'NewsletterResync';

    protected string $title = 'Newsletter: resync subscribers to the ESP';

    protected static string $description =
        'Pushes every locally-subscribed record to the active ESP provider. Dry-run unless --run is passed.';

    public function getOptions(): array
    {
        return [
            new InputOption('run', null, InputOption::VALUE_NONE, 'Actually perform the sync (otherwise dry-run).'),
        ];
    }

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        $provider = NewsletterProviderRegistry::create()->activeRemote();

        if (!$provider) {
            $output->writeln('No remote ESP provider is active — nothing to sync (the local list is in effect).');

            return Command::SUCCESS;
        }

        $subscribed = NewsletterSubscription::get()
            ->filter([
                'Status' => NewsletterSubscription::STATUS_SUBSCRIBED,
                'Consent' => true,
            ])
            ->count();

        if (!$input->getOption('run')) {
            $output->writeln(sprintf(
                'DRY RUN: would resync %d subscribed record(s) to "%s". Re-run with --run to perform the sync.',
                $subscribed,
                $provider->getName()
            ));

            return Command::SUCCESS;
        }

        $synced = $provider->syncAll();
        $output->writeln(sprintf('Resynced %d subscriber(s) to "%s".', $synced, $provider->getName()));

        return Command::SUCCESS;
    }
}
