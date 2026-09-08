<?php

declare(strict_types=1);

namespace AbandonedCartReminder\Domain\Service;

use Symfony\Component\HttpFoundation\RequestStack;

final readonly class RestrictedSession
{
    private const RESTRICTED_UNTIL = 'abandoned_cart_reminder.restricted_until';
    private const RESTRICTED_CUSTOMER = 'abandoned_cart_reminder.restricted_customer';

    public function __construct(
        private RequestStack $requestStack,
    ) {
    }

    public function open(int $lifetimeInSeconds, int $customerId): void
    {
        $session = $this->requestStack->getMainRequest()?->getSession();

        $session?->set(self::RESTRICTED_UNTIL, time() + max(0, $lifetimeInSeconds));
        $session?->set(self::RESTRICTED_CUSTOMER, $customerId);
    }

    public function isRestricted(): bool
    {
        $restrictedUntil = $this->requestStack->getMainRequest()?->getSession()?->get(self::RESTRICTED_UNTIL);

        if (!\is_int($restrictedUntil)) {
            return false;
        }

        return $restrictedUntil > time();
    }

    public function restrictsCustomer(int $customerId): bool
    {
        if (!$this->isRestricted()) {
            return false;
        }

        return $customerId === $this->requestStack->getMainRequest()?->getSession()?->get(self::RESTRICTED_CUSTOMER);
    }

    public function release(): void
    {
        $session = $this->requestStack->getMainRequest()?->getSession();

        $session?->remove(self::RESTRICTED_UNTIL);
        $session?->remove(self::RESTRICTED_CUSTOMER);
    }
}
