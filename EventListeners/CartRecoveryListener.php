<?php

declare(strict_types=1);

namespace AbandonedCartReminder\EventListeners;

use AbandonedCartReminder\Model\AbandonedCart;
use AbandonedCartReminder\Model\AbandonedCartQuery;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Thelia\Core\Event\Order\OrderEvent;
use Thelia\Core\Event\TheliaEvents;

final readonly class CartRecoveryListener implements EventSubscriberInterface
{
    public function recordTheRecovery(OrderEvent $event): void
    {
        $placedOrder = $event->getPlacedOrder();
        $cartId = $placedOrder->getCartId();

        if (null === $cartId) {
            return;
        }

        $trackedCart = AbandonedCartQuery::create()->findOneByCartId($cartId);

        if (!$trackedCart instanceof AbandonedCart) {
            return;
        }

        $trackedCart
            ->setStatus(AbandonedCart::STATUS_RECOVERED)
            ->setRecoveredOrderId($placedOrder->getId())
            ->save();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            TheliaEvents::ORDER_PAY => ['recordTheRecovery', 0],
        ];
    }
}
