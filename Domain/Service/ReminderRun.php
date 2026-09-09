<?php

declare(strict_types=1);

namespace AbandonedCartReminder\Domain\Service;

use AbandonedCartReminder\Domain\Report\ReminderRunReport;
use AbandonedCartReminder\Model\AbandonedCart;
use AbandonedCartReminder\Model\AbandonedCartQuery;
use Propel\Runtime\ActiveQuery\Criteria;
use Thelia\Model\Cart;

final readonly class ReminderRun
{
    private const DETECTION_PAGE_SIZE = 200;

    public function __construct(
        private AbandonedCartDetector $detector,
        private AbandonedCartTracker $tracker,
        private ReminderScheduler $scheduler,
        private ReminderMailer $mailer,
        private ReminderConfiguration $configuration,
    ) {
    }

    public function execute(bool $dryRun = false, ?int $limit = null, ?\DateTimeInterface $now = null): ReminderRunReport
    {
        $now ??= new \DateTimeImmutable();
        $limit ??= $this->configuration->remindersPerRun();
        $tracked = $this->trackEligibleCarts($now, $dryRun);
        $reminders = $this->sendDueReminders($now, $limit, $dryRun);

        return new ReminderRunReport(
            $dryRun,
            $tracked,
            $dryRun ? $reminders['sent'] + $tracked : $reminders['sent'],
            $reminders['stopped'],
            $reminders['noLongerEligible'],
        );
    }

    private function trackEligibleCarts(\DateTimeInterface $now, bool $dryRun): int
    {
        if (!$this->configuration->isEnabled(1)) {
            return 0;
        }

        $inactiveSince = \DateTimeImmutable::createFromInterface($now)
            ->sub(new \DateInterval('PT'.$this->configuration->inactivityThresholdInHours().'H'));

        $tracked = 0;
        $offset = 0;

        while ([] !== $carts = $this->detector->eligibleCarts($inactiveSince, self::DETECTION_PAGE_SIZE, $offset)) {
            foreach ($carts as $cart) {
                if (null !== AbandonedCartQuery::create()->findOneByCartId($cart->getId())) {
                    continue;
                }

                if (!$this->tracker->canTrack($cart)) {
                    continue;
                }

                if (!$dryRun) {
                    $this->tracker->track($cart);
                }

                ++$tracked;
            }

            $offset += self::DETECTION_PAGE_SIZE;
        }

        return $tracked;
    }

    /**
     * @return array{sent: int, stopped: int, noLongerEligible: int}
     */
    private function sendDueReminders(\DateTimeInterface $now, int $limit, bool $dryRun): array
    {
        $sent = 0;
        $stopped = 0;
        $noLongerEligible = 0;

        $lastSeenId = 0;

        while ($sent < $limit) {
            $candidates = AbandonedCartQuery::create()
                ->filterByStatus([AbandonedCart::STATUS_PENDING, AbandonedCart::STATUS_REMINDED], Criteria::IN)
                ->filterById($lastSeenId, Criteria::GREATER_THAN)
                ->orderById(Criteria::ASC)
                ->limit(self::DETECTION_PAGE_SIZE)
                ->find();

            if (0 === $candidates->count()) {
                break;
            }

            foreach ($candidates as $trackedCart) {
                if ($sent >= $limit) {
                    break;
                }

                $lastSeenId = (int) $trackedCart->getId();
                $reminderNumber = $this->scheduler->dueReminderNumber($trackedCart, $now);

                if (null === $reminderNumber) {
                    continue;
                }

                $cart = $trackedCart->getCart();

                if (!$cart instanceof Cart || !$this->detector->isStillEligible($cart)) {
                    ++$noLongerEligible;

                    if (!$dryRun) {
                        $trackedCart->setStatus(AbandonedCart::STATUS_STOPPED)->save();
                    }

                    continue;
                }

                if ($dryRun) {
                    ++$sent;

                    continue;
                }

                if (!$this->mailer->send($trackedCart, $reminderNumber)) {
                    continue;
                }

                $trackedCart
                    ->setRemindersSent($reminderNumber)
                    ->setLastReminderAt($now)
                    ->setStatus(
                        $reminderNumber >= AbandonedCart::MAXIMUM_REMINDERS
                            ? AbandonedCart::STATUS_STOPPED
                            : AbandonedCart::STATUS_REMINDED
                    )
                    ->save();

                ++$sent;

                if ($reminderNumber >= AbandonedCart::MAXIMUM_REMINDERS) {
                    ++$stopped;
                }
            }
        }

        return ['sent' => $sent, 'stopped' => $stopped, 'noLongerEligible' => $noLongerEligible];
    }
}
