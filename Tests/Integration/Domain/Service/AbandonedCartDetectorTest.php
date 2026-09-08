<?php

declare(strict_types=1);

namespace AbandonedCartReminder\Tests\Integration\Domain\Service;

use AbandonedCartReminder\Domain\Service\AbandonedCartDetector;
use Thelia\Model\Cart;
use Thelia\Model\CartQuery;
use Thelia\Model\Customer;
use Thelia\Model\Product;
use Thelia\Test\FixtureFactory;
use Thelia\Test\IntegrationTestCase;

final class AbandonedCartDetectorTest extends IntegrationTestCase
{
    private AbandonedCartDetector $detector;
    private FixtureFactory $fixtures;
    private Customer $customer;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->detector = new AbandonedCartDetector();
        $this->fixtures = $this->createFixtureFactory();
        $this->customer = $this->fixtures->customer($this->fixtures->customerTitle());
        $this->product = $this->fixtures->product(
            $this->fixtures->category(),
            $this->fixtures->taxRule(),
            $this->fixtures->currency(),
        );
    }

    public function testACartLeftBehindByACustomerIsEligible(): void
    {
        $cart = $this->abandonedCart($this->customer, '-5 hours');

        self::assertContains($cart->getId(), $this->eligibleCartIds());
    }

    public function testAnEmptyCartIsNotEligible(): void
    {
        $cart = $this->fixtures->cart($this->customer);
        $this->backdate($cart, '-5 hours');

        self::assertNotContains($cart->getId(), $this->eligibleCartIds());
    }

    public function testACartWithoutACustomerIsNotEligible(): void
    {
        $cart = $this->abandonedCart(null, '-5 hours');

        self::assertNotContains($cart->getId(), $this->eligibleCartIds());
    }

    public function testACartThatBecameAnOrderIsNotEligible(): void
    {
        $order = $this->fixtures->order($this->customer);
        $cart = CartQuery::create()->findPk($order->getCartId());

        self::assertInstanceOf(Cart::class, $cart);

        $this->fixtures->cartItem($cart, $this->product);
        $this->backdate($cart, '-5 hours');

        self::assertNotContains($cart->getId(), $this->eligibleCartIds());
    }

    public function testACartTouchedRecentlyIsNotEligible(): void
    {
        $cart = $this->abandonedCart($this->customer, '-10 minutes');

        self::assertNotContains($cart->getId(), $this->eligibleCartIds());
    }

    public function testTheCountMatchesWhatIsListed(): void
    {
        $this->abandonedCart($this->customer, '-5 hours');

        self::assertSame(
            $this->detector->countEligibleCarts($this->inactiveSince()),
            \count($this->eligibleCartIds()),
        );
    }

    private function abandonedCart(?Customer $customer, string $lastTouched): Cart
    {
        $cart = $this->fixtures->cart($customer);
        $this->fixtures->cartItem($cart, $this->product);

        return $this->backdate($cart, $lastTouched);
    }

    private function backdate(Cart $cart, string $lastTouched): Cart
    {
        $cart->setUpdatedAt(new \DateTime($lastTouched));
        $cart->save($this->getPropelConnection());

        return $cart;
    }

    private function inactiveSince(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('-1 hour');
    }

    /**
     * @return list<int>
     */
    private function eligibleCartIds(): array
    {
        return array_map(
            static fn (Cart $cart): int => $cart->getId(),
            $this->detector->eligibleCarts($this->inactiveSince(), 500),
        );
    }
}
