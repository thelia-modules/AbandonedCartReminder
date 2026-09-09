<?php

declare(strict_types=1);

namespace AbandonedCartReminder\Tests\Integration\Domain\Service;

use AbandonedCartReminder\Domain\Service\AbandonedCartTracker;
use AbandonedCartReminder\Domain\Service\CartRecoveryLink;
use AbandonedCartReminder\Domain\Service\ReminderConfiguration;
use AbandonedCartReminder\Domain\Service\ReminderConsent;
use AbandonedCartReminder\Model\AbandonedCart;
use Thelia\Model\Cart;
use Thelia\Model\Customer;
use Thelia\Test\FixtureFactory;
use Thelia\Test\IntegrationTestCase;

final class CartRecoveryLinkTest extends IntegrationTestCase
{
    private CartRecoveryLink $recoveryLink;
    private FixtureFactory $fixtures;
    private Customer $customer;
    private Cart $cart;
    private AbandonedCart $trackedCart;

    protected function setUp(): void
    {
        parent::setUp();

        $this->recoveryLink = new CartRecoveryLink('test-secret', new ReminderConfiguration());
        $this->fixtures = $this->createFixtureFactory();
        $this->customer = $this->fixtures->customer($this->fixtures->customerTitle());

        $this->cart = $this->fixtures->cart($this->customer);
        $this->fixtures->cartItem($this->cart, $this->fixtures->product(
            $this->fixtures->category(),
            $this->fixtures->taxRule(),
            $this->fixtures->currency(),
        ));

        $trackedCart = (new AbandonedCartTracker(new ReminderConsent()))->track($this->cart);

        self::assertInstanceOf(AbandonedCart::class, $trackedCart);
        $this->trackedCart = $trackedCart;
    }

    public function testAFreshLinkNamesItsCart(): void
    {
        $token = $this->recoveryLink->issue($this->trackedCart);

        $found = $this->recoveryLink->findTrackedCartForToken($token);

        self::assertInstanceOf(AbandonedCart::class, $found);
        self::assertSame($this->trackedCart->getId(), $found->getId());
    }

    public function testAnExpiredLinkIsRefused(): void
    {
        $token = $this->recoveryLink->issue($this->trackedCart, -1);

        self::assertNull($this->recoveryLink->findTrackedCartForToken($token));
    }

    public function testATamperedLinkIsRefused(): void
    {
        $token = $this->recoveryLink->issue($this->trackedCart);
        [$id, $expiresAt, $signature] = explode('.', $token);

        self::assertNull($this->recoveryLink->findTrackedCartForToken($id.'.'.($expiresAt + 3600).'.'.$signature));
        self::assertNull($this->recoveryLink->findTrackedCartForToken($id.'.'.$expiresAt.'.'.strrev($signature)));
        self::assertNull($this->recoveryLink->findTrackedCartForToken('not-a-token'));
    }

    public function testAConsumedLinkIsRefused(): void
    {
        $token = $this->recoveryLink->issue($this->trackedCart);

        $this->recoveryLink->consume($this->trackedCart);

        self::assertNull($this->recoveryLink->findTrackedCartForToken($token));
    }

    public function testIssuingANewLinkRetiresThePreviousOne(): void
    {
        $firstToken = $this->recoveryLink->issue($this->trackedCart, 3600);
        $secondToken = $this->recoveryLink->issue($this->trackedCart, 7200);

        self::assertNotSame($firstToken, $secondToken);

        self::assertNull($this->recoveryLink->findTrackedCartForToken($firstToken));
        self::assertInstanceOf(AbandonedCart::class, $this->recoveryLink->findTrackedCartForToken($secondToken));
    }

    public function testALinkDiesWithTheCustomerPassword(): void
    {
        $token = $this->recoveryLink->issue($this->trackedCart);

        $this->customer->setPassword('a-brand-new-password')->save();

        self::assertNull($this->recoveryLink->findTrackedCartForToken($token));
    }

    public function testACartOfADifferentShopSecretIsRefused(): void
    {
        $token = $this->recoveryLink->issue($this->trackedCart);

        $otherShop = new CartRecoveryLink('another-secret', new ReminderConfiguration());

        self::assertNull($otherShop->findTrackedCartForToken($token));
    }

    public function testTrackingRemembersTheCustomerItself(): void
    {
        self::assertSame($this->customer->getId(), $this->trackedCart->getCustomerId());
    }

    public function testALinkSurvivesTheCartLosingItsCustomer(): void
    {
        $token = $this->recoveryLink->issue($this->trackedCart);

        $this->cart->setCustomerId(null)->save();

        $found = $this->recoveryLink->findTrackedCartForToken($token);

        self::assertInstanceOf(AbandonedCart::class, $found);
        self::assertSame($this->trackedCart->getId(), $found->getId());
        self::assertSame($this->customer->getId(), $found->getCustomer()?->getId());
    }
}
