<?php

declare(strict_types=1);

namespace AbandonedCartReminder\Tests\Integration\Domain\Service;

use AbandonedCartReminder\Domain\Service\AbandonedCartTracker;
use AbandonedCartReminder\Domain\Service\ReminderConsent;
use AbandonedCartReminder\Model\AbandonedCart;
use AbandonedCartReminder\Model\AbandonedCartQuery;
use Thelia\Model\Cart;
use Thelia\Model\Customer;
use Thelia\Test\FixtureFactory;
use Thelia\Test\IntegrationTestCase;

final class ReminderConsentTest extends IntegrationTestCase
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

    public function testARefusalStopsTheCartsAlreadyFollowed(): void
    {
        $trackedCart = $this->followedCart();

        $this->consent->refuse((string) $this->customer->getEmail());

        $trackedCart->reload();

        self::assertSame(AbandonedCart::STATUS_STOPPED, $trackedCart->getStatus());
        self::assertTrue($this->consent->isRefusedFor((string) $this->customer->getEmail()));
    }

    public function testARefusalHoldsForAFutureCart(): void
    {
        $this->consent->refuse((string) $this->customer->getEmail());

        $cart = $this->fixtures->cart($this->customer);
        $this->fixtures->cartItem($cart, $this->product());

        $tracker = new AbandonedCartTracker($this->consent);

        self::assertNull($tracker->track($cart));
        self::assertNull(AbandonedCartQuery::create()->findOneByCartId($cart->getId()));
    }

    public function testARefusalIsCaseAndSpaceInsensitive(): void
    {
        $this->consent->refuse('  '.strtoupper((string) $this->customer->getEmail()).' ');

        self::assertTrue($this->consent->isRefusedFor((string) $this->customer->getEmail()));
    }

    public function testRefusingTwiceIsHarmless(): void
    {
        $email = (string) $this->customer->getEmail();

        $this->consent->refuse($email);
        $this->consent->refuse($email);

        self::assertTrue($this->consent->isRefusedFor($email));
    }

    public function testTheUnsubscribeTokenNamesTheAddressBack(): void
    {
        $email = (string) $this->customer->getEmail();

        $token = $this->consent->unsubscribeToken($email);

        self::assertSame($email, $this->consent->emailForToken($token));
    }

    public function testATamperedUnsubscribeTokenNamesNobody(): void
    {
        $token = $this->consent->unsubscribeToken((string) $this->customer->getEmail());
        [$encoded, $signature] = explode('.', $token);

        self::assertNull($this->consent->emailForToken($encoded.'.'.strrev($signature)));
        self::assertNull($this->consent->emailForToken(rtrim(strtr(base64_encode('victim@example.com'), '+/', '-_'), '=').'.'.$signature));
        self::assertNull($this->consent->emailForToken('nonsense'));
    }

    public function testAnotherShopSecretCannotUnsubscribeHere(): void
    {
        $token = (new ReminderConsent('another-secret'))->unsubscribeToken((string) $this->customer->getEmail());

        self::assertNull($this->consent->emailForToken($token));
    }

    public function testForgettingACustomerClearsTheRefusalAndTheHistory(): void
    {
        $email = (string) $this->customer->getEmail();
        $this->followedCart();
        $this->consent->refuse($email);

        $this->consent->forget($email);

        self::assertFalse($this->consent->isRefusedFor($email));
        self::assertSame(0, AbandonedCartQuery::create()->filterByEmail($email)->count());
    }

    private function followedCart(): AbandonedCart
    {
        $cart = $this->fixtures->cart($this->customer);
        $this->fixtures->cartItem($cart, $this->product());

        $trackedCart = (new AbandonedCartTracker($this->consent))->track($cart);

        self::assertInstanceOf(AbandonedCart::class, $trackedCart);

        return $trackedCart;
    }

    private function product(): \Thelia\Model\Product
    {
        return $this->fixtures->product(
            $this->fixtures->category(),
            $this->fixtures->taxRule(),
            $this->fixtures->currency(),
        );
    }
}
