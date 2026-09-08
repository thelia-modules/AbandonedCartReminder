<?php

declare(strict_types=1);

namespace AbandonedCartReminder\Tests\Integration\EventListeners;

use AbandonedCartReminder\Domain\Service\AbandonedCartTracker;
use AbandonedCartReminder\Domain\Service\ReminderConsent;
use AbandonedCartReminder\EventListeners\CustomerAnonymizeListener;
use AbandonedCartReminder\Model\AbandonedCart;
use AbandonedCartReminder\Model\AbandonedCartQuery;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Thelia\Core\Event\Customer\CustomerAnonymizeEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Model\Customer;
use Thelia\Test\FixtureFactory;
use Thelia\Test\IntegrationTestCase;

final class CustomerAnonymizeListenerTest extends IntegrationTestCase
{
    private ReminderConsent $consent;
    private FixtureFactory $fixtures;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->consent = new ReminderConsent('test-secret');
        $this->fixtures = $this->createFixtureFactory();
        $this->customer = $this->fixtures->customer($this->fixtures->customerTitle());
    }

    public function testAnonymizingACustomerErasesTheirRefusalAndTheirFollowedCarts(): void
    {
        $email = (string) $this->customer->getEmail();
        $this->followACart();
        $this->consent->refuse($email);

        (new CustomerAnonymizeListener($this->consent))
            ->forgetTheCustomer(new CustomerAnonymizeEvent($this->customer));

        self::assertFalse($this->consent->isRefusedFor($email));
        self::assertSame(0, AbandonedCartQuery::create()->filterByEmail($email)->count());
    }

    /**
     * The listener is wired to the event the core actually dispatches, with the shape the
     * core actually passes: a wrong type hint here takes down every core anonymization test.
     */
    public function testTheListenerAnswersTheEventTheCoreDispatches(): void
    {
        $email = (string) $this->customer->getEmail();
        $this->followACart();

        $this->getService(EventDispatcherInterface::class)->dispatch(
            new CustomerAnonymizeEvent($this->customer),
            TheliaEvents::CUSTOMER_ANONYMIZE,
        );

        self::assertSame(0, AbandonedCartQuery::create()->filterByEmail($email)->count());
    }

    private function followACart(): void
    {
        $cart = $this->fixtures->cart($this->customer);
        $this->fixtures->cartItem($cart, $this->fixtures->product(
            $this->fixtures->category(),
            $this->fixtures->taxRule(),
            $this->fixtures->currency(),
        ));

        self::assertInstanceOf(
            AbandonedCart::class,
            (new AbandonedCartTracker($this->consent))->track($cart),
        );
    }
}
