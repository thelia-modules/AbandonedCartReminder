<?php

declare(strict_types=1);

namespace AbandonedCartReminder\EventListeners;

use AbandonedCartReminder\Domain\Service\RestrictedSession;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Thelia\Core\Event\TheliaEvents;

final readonly class RestrictedSessionReleaseListener implements EventSubscriberInterface
{
    public function __construct(
        private RestrictedSession $restrictedSession,
    ) {
    }

    public function releaseTheRestriction(): void
    {
        $this->restrictedSession->release();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            TheliaEvents::CUSTOMER_LOGIN => ['releaseTheRestriction', 256],
            TheliaEvents::CUSTOMER_LOGOUT => ['releaseTheRestriction', 256],
        ];
    }
}
