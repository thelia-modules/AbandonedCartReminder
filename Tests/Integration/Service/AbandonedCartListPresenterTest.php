<?php

declare(strict_types=1);

namespace AbandonedCartReminder\Tests\Integration\Service;

use AbandonedCartReminder\Domain\Service\AbandonedCartTracker;
use AbandonedCartReminder\Domain\Service\ReminderConsent;
use AbandonedCartReminder\EventListeners\CartRecoveryListener;
use AbandonedCartReminder\Model\AbandonedCart;
use AbandonedCartReminder\Model\AbandonedCartQuery;
use AbandonedCartReminder\Service\AbandonedCartListFilters;
use AbandonedCartReminder\Service\AbandonedCartListPresenter;
use Thelia\Core\Event\Order\OrderEvent;
use Symfony\Component\HttpFoundation\Request;
use Thelia\Model\Cart;
use Thelia\Model\CartQuery;
use Thelia\Model\Customer;
use Thelia\Model\Product;
use Thelia\Test\FixtureFactory;
use Thelia\Test\IntegrationTestCase;

final class AbandonedCartListPresenterTest extends IntegrationTestCase
{
    private FixtureFactory $fixtures;
    private Customer $customer;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        AbandonedCartQuery::create()->deleteAll();

        $this->fixtures = $this->createFixtureFactory();
        $this->customer = $this->fixtures->customer($this->fixtures->customerTitle());
        $this->product = $this->fixtures->product(
            $this->fixtures->category(),
            $this->fixtures->taxRule(),
            $this->fixtures->currency(),
        );
    }

    public function testThePagesSplitTheFollowedCarts(): void
    {
        $this->followedCart();
        $this->followedCart();
        $this->followedCart();

        $presenter = new AbandonedCartListPresenter(2);

        $firstPage = $presenter->page(1);
        $secondPage = $presenter->page(2);

        self::assertSame(2, $firstPage['lastPage']);
        self::assertCount(2, $firstPage['rows']);
        self::assertCount(1, $secondPage['rows']);
        self::assertSame(3, $firstPage['followed']);
    }

    public function testAPageBeyondTheLastFallsBackToTheLastOne(): void
    {
        $this->followedCart();

        $presenter = new AbandonedCartListPresenter(2);

        self::assertSame(1, $presenter->page(99)['currentPage']);
    }

    public function testAnEmptyListStillHasOnePage(): void
    {
        $page = (new AbandonedCartListPresenter(2))->page(1);

        self::assertSame([], $page['rows']);
        self::assertSame(1, $page['lastPage']);
        self::assertSame('0.00', $page['recoveredTotal']);
    }

    public function testTheRecoveredTotalAddsUpTheOrdersBehindTheRecoveredCarts(): void
    {
        $firstOrder = $this->recoveredCart();
        $secondOrder = $this->recoveredCart();

        $expected = number_format($firstOrder + $secondOrder, 2, '.', '');

        self::assertSame($expected, (new AbandonedCartListPresenter())->recoveredTotal());
    }

    public function testTheSearchMatchesAnEmail(): void
    {
        $wanted = $this->followedCartOf('cible@example.com', 'Zoe', 'Martin');
        $this->followedCartOf('autre@example.com', 'Hugo', 'Bernard');

        $page = (new AbandonedCartListPresenter())->page(1, new AbandonedCartListFilters('cible@'));

        self::assertCount(1, $page['rows']);
        self::assertSame($wanted->getId(), $page['rows'][0]['id']);
    }

    public function testTheSearchMatchesACustomerName(): void
    {
        $wanted = $this->followedCartOf('someone@example.com', 'Zoe', 'Delaunay');
        $this->followedCartOf('other@example.com', 'Hugo', 'Bernard');

        $page = (new AbandonedCartListPresenter())->page(1, new AbandonedCartListFilters('Delaunay'));

        self::assertCount(1, $page['rows']);
        self::assertSame($wanted->getId(), $page['rows'][0]['id']);
    }

    public function testASearchMatchingNobodyEmptiesTheList(): void
    {
        $this->followedCartOf('someone@example.com', 'Zoe', 'Delaunay');

        $page = (new AbandonedCartListPresenter())->page(1, new AbandonedCartListFilters('introuvable'));

        self::assertSame([], $page['rows']);
        self::assertSame(0, $page['matching']);
    }

    public function testTheColumnsSortBothWays(): void
    {
        $this->followedCartOf('c@example.com', 'Zoe', 'Martin');
        $this->followedCartOf('a@example.com', 'Hugo', 'Bernard');
        $this->followedCartOf('b@example.com', 'Lea', 'Dupont');

        $presenter = new AbandonedCartListPresenter();

        $ascending = array_column($presenter->page(1, new AbandonedCartListFilters('', 'customer', 'asc'))['rows'], 'email');
        $descending = array_column($presenter->page(1, new AbandonedCartListFilters('', 'customer', 'desc'))['rows'], 'email');

        self::assertSame(['a@example.com', 'b@example.com', 'c@example.com'], $ascending);
        self::assertSame(array_reverse($ascending), $descending);
    }

    public function testAnUnknownSortColumnFallsBackInsteadOfBreaking(): void
    {
        $request = Request::create('/admin/module/AbandonedCartReminder', 'GET', [
            'order' => 'drop table',
            'direction' => 'sideways',
            'page' => '-3',
        ]);

        $filters = AbandonedCartListFilters::fromRequest($request);

        self::assertSame('followed_since', $filters->sortField);
        self::assertSame('desc', $filters->direction);
        self::assertSame(1, $filters->page);
    }

    private function followedCartOf(string $email, string $firstname, string $lastname): AbandonedCart
    {
        $customer = $this->fixtures->customer($this->fixtures->customerTitle(), [
            'email' => $email,
            'firstname' => $firstname,
            'lastname' => $lastname,
        ]);

        $cart = $this->fixtures->cart($customer);
        $this->fixtures->cartItem($cart, $this->product);

        $trackedCart = (new AbandonedCartTracker(new ReminderConsent()))->track($cart);

        self::assertInstanceOf(AbandonedCart::class, $trackedCart);

        return $trackedCart;
    }

    private function followedCart(): AbandonedCart
    {
        $cart = $this->fixtures->cart($this->customer);
        $this->fixtures->cartItem($cart, $this->product);

        $trackedCart = (new AbandonedCartTracker(new ReminderConsent()))->track($cart);

        self::assertInstanceOf(AbandonedCart::class, $trackedCart);

        return $trackedCart;
    }

    private function recoveredCart(): float
    {
        $order = $this->fixtures->order($this->customer, ['postage' => '5.00']);
        $cart = CartQuery::create()->findPk($order->getCartId());

        self::assertInstanceOf(Cart::class, $cart);

        $this->fixtures->cartItem($cart, $this->product);
        (new AbandonedCartTracker(new ReminderConsent()))->track($cart);
        (new CartRecoveryListener())->recordTheRecovery($this->orderPaid($order));

        return $order->getTotalAmount();
    }

    private function orderPaid(\Thelia\Model\Order $order): OrderEvent
    {
        $event = new OrderEvent($order);
        $event->setPlacedOrder($order);

        return $event;
    }
}
