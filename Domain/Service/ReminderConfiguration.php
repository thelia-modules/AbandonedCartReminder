<?php

declare(strict_types=1);

namespace AbandonedCartReminder\Domain\Service;

use AbandonedCartReminder\AbandonedCartReminder;
use AbandonedCartReminder\Model\AbandonedCart;

final readonly class ReminderConfiguration
{
    private const DELAY_KEYS = [
        1 => AbandonedCartReminder::FIRST_REMINDER_DELAY_IN_HOURS,
        2 => AbandonedCartReminder::SECOND_REMINDER_DELAY_IN_HOURS,
        3 => AbandonedCartReminder::THIRD_REMINDER_DELAY_IN_HOURS,
    ];

    private const MESSAGE_CODES = [
        1 => AbandonedCartReminder::FIRST_REMINDER_MESSAGE,
        2 => AbandonedCartReminder::SECOND_REMINDER_MESSAGE,
        3 => AbandonedCartReminder::THIRD_REMINDER_MESSAGE,
    ];

    public function delayInHours(int $reminderNumber): ?int
    {
        $value = AbandonedCartReminder::getConfigValue($this->delayKey($reminderNumber));

        if (null === $value || '' === trim((string) $value)) {
            return null;
        }

        return (int) $value;
    }

    public function isEnabled(int $reminderNumber): bool
    {
        return null !== $this->delayInHours($reminderNumber);
    }

    public function messageCode(int $reminderNumber): string
    {
        return self::MESSAGE_CODES[$reminderNumber]
            ?? throw new \InvalidArgumentException("There is no reminder number $reminderNumber.");
    }

    public function remindersPerRun(): int
    {
        return max(1, (int) AbandonedCartReminder::getConfigValue(AbandonedCartReminder::REMINDERS_PER_RUN, '200'));
    }

    public function recoveryLinkLifetimeInSeconds(): int
    {
        return max(
            60,
            (int) AbandonedCartReminder::getConfigValue(AbandonedCartReminder::RECOVERY_LINK_LIFETIME_IN_SECONDS, '604800')
        );
    }

    public function inactivityThresholdInHours(): int
    {
        return $this->delayInHours(1) ?? 0;
    }

    public function longestReminderChainInHours(): int
    {
        $total = 0;

        for ($reminderNumber = 1; $reminderNumber <= AbandonedCart::MAXIMUM_REMINDERS; ++$reminderNumber) {
            $delay = $this->delayInHours($reminderNumber);

            if (null === $delay) {
                return $total;
            }

            $total += $delay;
        }

        return $total;
    }

    private function delayKey(int $reminderNumber): string
    {
        return self::DELAY_KEYS[$reminderNumber]
            ?? throw new \InvalidArgumentException("There is no reminder number $reminderNumber.");
    }
}
