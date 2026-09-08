<?php

declare(strict_types=1);

namespace AbandonedCartReminder\Command;

use AbandonedCartReminder\Domain\Service\ReminderPrerequisites;
use AbandonedCartReminder\Domain\Service\ReminderRun;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Thelia\Command\ContainerAwareCommand;

#[AsCommand(
    name: 'remind:abandoned-carts',
    description: 'Mails a reminder to the customers who left a cart behind.',
)]
final class RemindAbandonedCartsCommand extends ContainerAwareCommand
{
    public function __construct(
        private readonly ReminderRun $reminderRun,
        private readonly ReminderPrerequisites $prerequisites,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Report what would be sent without sending or recording anything.')
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'How many reminders this run may send at most.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initRequest();

        $style = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');
        $limit = $input->getOption('limit');

        foreach ($this->prerequisites->warnings() as $warning) {
            $style->warning($warning);
        }

        $blockingReason = $this->prerequisites->blockSending();

        if (null !== $blockingReason && !$dryRun) {
            $style->error($blockingReason);

            return self::FAILURE;
        }

        if (null !== $blockingReason) {
            $style->warning($blockingReason);
        }

        $report = $this->reminderRun->execute(
            $dryRun,
            null === $limit ? null : max(1, (int) $limit),
        );

        $style->definitionList(
            ['Mode' => $report->dryRun ? 'dry run, nothing sent or recorded' : 'live'],
            ['Carts newly followed' => (string) $report->cartsTracked],
            [$report->dryRun ? 'Reminders that would be sent' : 'Reminders sent' => (string) $report->remindersSent],
            ['Carts done being reminded' => (string) $report->cartsStopped],
            ['Carts no longer eligible' => (string) $report->cartsNoLongerEligible],
        );

        return self::SUCCESS;
    }
}
