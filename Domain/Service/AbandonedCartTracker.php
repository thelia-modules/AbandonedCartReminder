<?php

declare(strict_types=1);

namespace AbandonedCartReminder\Domain\Service;

use AbandonedCartReminder\Model\AbandonedCart;
use AbandonedCartReminder\Model\AbandonedCartQuery;
use Thelia\Model\Cart;
use Thelia\Model\Customer;

final readonly class AbandonedCartTracker
{
    public function __construct(
        private ReminderConsent $consent,
    ) {
    }

    public function canTrack(Cart $cart): bool
    {
        $customer = $cart->getCustomer();

        if (!$customer instanceof Customer) {
            return false;
        }

        $email = (string) $customer->getEmail();

        return '' !== $email && !$this->consent->isRefusedFor($email);
    }

    public function trackVisitorEmail(Cart $cart, string $email, ?string $locale = null): ?AbandonedCart
    {
        $email = mb_strtolower(trim($email));

        if ('' === $email || false === filter_var($email, \FILTER_VALIDATE_EMAIL) || $this->consent->isRefusedFor($email)) {
            return null;
        }

        $trackedCart = AbandonedCartQuery::create()->findOneByCartId($cart->getId()) ?? new AbandonedCart();

        if ($trackedCart->isNew()) {
            $trackedCart
                ->setCartId($cart->getId())
                ->setCustomerId($cart->getCustomerId())
                ->setStatus(AbandonedCart::STATUS_PENDING)
                ->setRemindersSent(0);
        }

        $trackedCart
            ->setEmail($email)
            ->setEmailSource(AbandonedCart::EMAIL_SOURCE_VISITOR_CAPTURE)
            ->setLocale($locale)
            ->save();

        return $trackedCart;
    }

    public function track(Cart $cart): ?AbandonedCart
    {
        if (!$this->canTrack($cart)) {
            return null;
        }

        $customer = $cart->getCustomer();
        $email = (string) $customer->getEmail();

        $trackedCart = AbandonedCartQuery::create()->findOneByCartId($cart->getId());

        if ($trackedCart instanceof AbandonedCart) {
            return $trackedCart;
        }

        $trackedCart = new AbandonedCart();
        $trackedCart
            ->setCartId($cart->getId())
            ->setCustomerId($customer->getId())
            ->setEmail($email)
            ->setEmailSource(AbandonedCart::EMAIL_SOURCE_CUSTOMER_ACCOUNT)
            ->setLocale($customer->getCustomerLang()?->getLocale())
            ->setStatus(AbandonedCart::STATUS_PENDING)
            ->setRemindersSent(0)
            ->save();

        return $trackedCart;
    }
}
