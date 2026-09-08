<?php

declare(strict_types=1);

namespace AbandonedCartReminder\Domain\Report;

final readonly class ReminderRunReport
{
    public function __construct(
        public bool $dryRun,
        public int $cartsTracked,
        public int $remindersSent,
        public int $cartsStopped,
        public int $cartsNoLongerEligible,
    ) {
    }
}
