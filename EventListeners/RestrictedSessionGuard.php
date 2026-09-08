<?php

declare(strict_types=1);

namespace AbandonedCartReminder\EventListeners;

use AbandonedCartReminder\Domain\Exception\RestrictedSessionException;
use AbandonedCartReminder\Domain\Service\RestrictedSession;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Thelia\Core\Event\Customer\CustomerCreateOrUpdateEvent;
use Thelia\Core\Event\Customer\CustomerEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Core\Security\SecurityContext;

final readonly class RestrictedSessionGuard implements EventSubscriberInterface
{
    public function __construct(
        private RestrictedSession $restrictedSession,
        private SecurityContext $securityContext,
    ) {
    }

    public function refuseAPasswordChange(CustomerCreateOrUpdateEvent $event): void
    {
        if (null === $event->getPassword() || '' === $event->getPassword()) {
            return;
        }

        if ($this->targetsTheRestrictedCustomer($event)) {
            throw new RestrictedSessionException('A password cannot be changed from a session opened by a cart recovery link.');
        }
    }

    public function refuseAnAccountDeletion(CustomerEvent $event): void
    {
        if ($this->targetsTheRestrictedCustomer($event)) {
            throw new RestrictedSessionException('An account cannot be deleted from a session opened by a cart recovery link.');
        }
    }

    private function targetsTheRestrictedCustomer(CustomerEvent $event): bool
    {
        if ($this->securityContext->hasAdminUser()) {
            return false;
        }

        if (!$event->hasCustomer()) {
            return false;
        }

        return $this->restrictedSession->restrictsCustomer((int) $event->getCustomer()->getId());
    }

    public static function getSubscribedEvents(): array
    {
        return [
            TheliaEvents::CUSTOMER_UPDATEPROFILE => ['refuseAPasswordChange', 256],
            TheliaEvents::CUSTOMER_DELETEACCOUNT => ['refuseAnAccountDeletion', 256],
        ];
    }
}
