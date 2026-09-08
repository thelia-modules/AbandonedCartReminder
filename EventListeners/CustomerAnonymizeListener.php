<?php

declare(strict_types=1);

namespace AbandonedCartReminder\EventListeners;

use AbandonedCartReminder\Domain\Service\ReminderConsent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Thelia\Core\Event\Customer\CustomerAnonymizeEvent;
use Thelia\Core\Event\TheliaEvents;

final readonly class CustomerAnonymizeListener implements EventSubscriberInterface
{
    public function __construct(
        private ReminderConsent $consent,
    ) {
    }

    public function forgetTheCustomer(CustomerAnonymizeEvent $event): void
    {
        $email = $event->getCustomer()->getEmail();

        if (null === $email || '' === $email) {
            return;
        }

        $this->consent->forget($email);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            TheliaEvents::CUSTOMER_ANONYMIZE => ['forgetTheCustomer', 256],
        ];
    }
}
