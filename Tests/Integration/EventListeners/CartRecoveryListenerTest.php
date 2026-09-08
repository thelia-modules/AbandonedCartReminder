<?php

declare(strict_types=1);

namespace AbandonedCartReminder\Tests\Integration\EventListeners;

use AbandonedCartReminder\Domain\Service\AbandonedCartTracker;
use AbandonedCartReminder\Domain\Service\ReminderConsent;
use AbandonedCartReminder\EventListeners\CartRecoveryListener;
use AbandonedCartReminder\Model\AbandonedCart;
use AbandonedCartReminder\Model\AbandonedCartQuery;
use Thelia\Core\Event\Order\OrderEvent;
use Thelia\Model\Cart;
use Thelia\Model\CartQuery;
use Thelia\Model\Customer;
use Thelia\Model\Order;
use Thelia\Test\FixtureFactory;
use Thelia\Test\IntegrationTestCase;

final class CartRecoveryListenerTest extends IntegrationTestCase
{
    private CartRecoveryListener $listener;
    private FixtureFactory $fixtures;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->listener = new CartRecoveryListener();
        $this->fixtures = $this->createFixtureFactory();
        $this->customer = $this->fixtures->customer($this->fixtures->customerTitle());
    }

    public function testAnOrderPlacedFromAFollowedCartMarksItRecovered(): void
    {
        $order = $this->fixtures->order($this->customer);
        $cart = CartQuery::create()->findPk($order->getCartId());

        self::assertInstanceOf(Cart::class, $cart);

        $trackedCart = $this->track($cart);

        $this->listener->recordTheRecovery($this->orderPaid($order));

        $trackedCart->reload();

        self::assertSame(AbandonedCart::STATUS_RECOVERED, $trackedCart->getStatus());
        self::assertSame($order->getId(), $trackedCart->getRecoveredOrderId());
    }

    public function testTheRecoveredAmountIsReadFromTheOrder(): void
    {
        $order = $this->fixtures->order($this->customer, ['postage' => '7.50']);
        $cart = CartQuery::create()->findPk($order->getCartId());

        self::assertInstanceOf(Cart::class, $cart);

        $trackedCart = $this->track($cart);

        $this->listener->recordTheRecovery($this->orderPaid($order));

        $trackedCart->reload();
        $recoveredOrder = $trackedCart->getOrder();

        self::assertInstanceOf(Order::class, $recoveredOrder);
        self::assertSame($order->getTotalAmount(), $recoveredOrder->getTotalAmount());
    }

    public function testAnOrderFromACartNobodyFollowsChangesNothing(): void
    {
        $order = $this->fixtures->order($this->customer);

        $this->listener->recordTheRecovery($this->orderPaid($order));

        self::assertNull(AbandonedCartQuery::create()->findOneByCartId($order->getCartId()));
    }

    private function track(Cart $cart): AbandonedCart
    {
        $this->fixtures->cartItem($cart, $this->fixtures->product(
            $this->fixtures->category(),
            $this->fixtures->taxRule(),
            $this->fixtures->currency(),
        ));

        $trackedCart = (new AbandonedCartTracker(new ReminderConsent()))->track($cart);

        self::assertInstanceOf(AbandonedCart::class, $trackedCart);

        return $trackedCart;
    }

    private function orderPaid(Order $order): OrderEvent
    {
        $event = new OrderEvent($order);
        $event->setPlacedOrder($order);

        return $event;
    }
}
