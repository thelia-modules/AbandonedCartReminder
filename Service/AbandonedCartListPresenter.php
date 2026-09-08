<?php

declare(strict_types=1);

namespace AbandonedCartReminder\Service;

use AbandonedCartReminder\Model\AbandonedCart;
use AbandonedCartReminder\Model\AbandonedCartQuery;
use Propel\Runtime\ActiveQuery\Criteria;
use Thelia\Model\CustomerQuery;
use Thelia\Model\Order;

final readonly class AbandonedCartListPresenter
{
    public const PAGE_SIZE = 20;

    public function __construct(
        private int $pageSize = self::PAGE_SIZE,
    ) {
    }

    /**
     * @return array{rows: list<array<string, mixed>>, currentPage: int, lastPage: int, recoveredTotal: string, followed: int, matching: int}
     */
    public function page(int $page, ?AbandonedCartListFilters $filters = null): array
    {
        $filters ??= new AbandonedCartListFilters();

        $total = $this->filteredQuery($filters)->count();
        $lastPage = max(1, (int) ceil($total / $this->pageSize));
        $currentPage = max(1, min($page, $lastPage));

        $trackedCarts = $this->filteredQuery($filters)
            ->orderBy($filters->propelColumn(), $filters->isDescending() ? Criteria::DESC : Criteria::ASC)
            ->offset(($currentPage - 1) * $this->pageSize)
            ->limit($this->pageSize)
            ->find();

        $rows = [];

        foreach ($trackedCarts as $trackedCart) {
            $rows[] = $this->row($trackedCart);
        }

        return [
            'rows' => $rows,
            'currentPage' => $currentPage,
            'lastPage' => $lastPage,
            'recoveredTotal' => $this->recoveredTotal(),
            'matching' => $total,
            'followed' => AbandonedCartQuery::create()
                ->filterByStatus([AbandonedCart::STATUS_PENDING, AbandonedCart::STATUS_REMINDED], Criteria::IN)
                ->count(),
        ];
    }

    private function filteredQuery(AbandonedCartListFilters $filters): AbandonedCartQuery
    {
        $query = AbandonedCartQuery::create();

        if ('' === $filters->search) {
            return $query;
        }

        $term = '%'.$filters->search.'%';

        $emailsOfMatchingCustomers = CustomerQuery::create()
            ->filterByFirstname($term, Criteria::LIKE)
            ->_or()
            ->filterByLastname($term, Criteria::LIKE)
            ->select('Email')
            ->find()
            ->toArray();

        return $query
            ->filterByEmail($term, Criteria::LIKE)
            ->_or()
            ->filterByEmail($emailsOfMatchingCustomers, Criteria::IN);
    }

    public function recoveredTotal(): string
    {
        $total = 0.0;

        $recovered = AbandonedCartQuery::create()
            ->filterByStatus(AbandonedCart::STATUS_RECOVERED)
            ->find();

        foreach ($recovered as $trackedCart) {
            $order = $trackedCart->getOrder();

            if ($order instanceof Order) {
                $total += $order->getTotalAmount();
            }
        }

        return number_format($total, 2, '.', '');
    }

    /**
     * @return array<string, mixed>
     */
    private function row(AbandonedCart $trackedCart): array
    {
        $order = $trackedCart->getOrder();

        return [
            'id' => $trackedCart->getId(),
            'email' => $trackedCart->getEmail(),
            'cart_id' => $trackedCart->getCartId(),
            'status' => (int) $trackedCart->getStatus(),
            'reminders_sent' => (int) $trackedCart->getRemindersSent(),
            'last_reminder_at' => $trackedCart->getLastReminderAt(),
            'created_at' => $trackedCart->getCreatedAt(),
            'order_id' => $order?->getId(),
            'order_ref' => $order?->getRef(),
            'recovered_amount' => $order instanceof Order ? $order->getTotalAmount() : null,
            'currency_id' => $trackedCart->getCart()?->getCurrencyId(),
        ];
    }
}
