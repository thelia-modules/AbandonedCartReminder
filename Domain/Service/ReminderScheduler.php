<?php

declare(strict_types=1);

namespace AbandonedCartReminder\Domain\Service;

use AbandonedCartReminder\Model\AbandonedCart;

final readonly class ReminderScheduler
{
    public function __construct(
        private ReminderConfiguration $configuration,
    ) {
    }

    public function dueReminderNumber(AbandonedCart $trackedCart, \DateTimeInterface $now): ?int
    {
        if (!\in_array($trackedCart->getStatus(), [AbandonedCart::STATUS_PENDING, AbandonedCart::STATUS_REMINDED], true)) {
            return null;
        }

        $alreadySent = (int) $trackedCart->getRemindersSent();

        if ($alreadySent >= AbandonedCart::MAXIMUM_REMINDERS) {
            return null;
        }

        $reminderNumber = $alreadySent + 1;
        $delayInHours = $this->configuration->delayInHours($reminderNumber);

        if (null === $delayInHours) {
            return null;
        }

        $countFrom = $this->countFrom($trackedCart);

        if (null === $countFrom) {
            return null;
        }

        $dueAt = \DateTimeImmutable::createFromInterface($countFrom)
            ->add(new \DateInterval('PT'.$delayInHours.'H'));

        return $dueAt > $now ? null : $reminderNumber;
    }

    private function countFrom(AbandonedCart $trackedCart): ?\DateTimeInterface
    {
        $lastReminder = $trackedCart->getLastReminderAt();

        if ($lastReminder instanceof \DateTimeInterface) {
            return $lastReminder;
        }

        $cartLastTouched = $trackedCart->getCart()?->getUpdatedAt();

        return $cartLastTouched instanceof \DateTimeInterface ? $cartLastTouched : null;
    }
}
