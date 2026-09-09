<?php

declare(strict_types=1);

namespace AbandonedCartReminder\Domain\Service;

use Propel\Runtime\ActiveQuery\Criteria;
use Thelia\Model\Cart;
use Thelia\Model\CartItemQuery;
use Thelia\Model\CartQuery;
use Thelia\Model\OrderQuery;

final readonly class AbandonedCartDetector
{
    /**
     * @return list<Cart>
     */
    public function eligibleCarts(\DateTimeInterface $inactiveSince, int $limit, int $offset = 0): array
    {
        return array_values(
            $this->eligibleCartQuery($inactiveSince)
                ->orderById()
                ->offset($offset)
                ->limit($limit)
                ->find()
                ->getData()
        );
    }

    public function countEligibleCarts(\DateTimeInterface $inactiveSince): int
    {
        return $this->eligibleCartQuery($inactiveSince)->count();
    }

    public function isStillEligible(Cart $cart): bool
    {
        if (0 === $cart->countCartItems()) {
            return false;
        }

        return 0 === OrderQuery::create()->filterByCartId($cart->getId())->count();
    }

    private function eligibleCartQuery(\DateTimeInterface $inactiveSince): CartQuery
    {
        return CartQuery::create()
            ->filterByUpdatedAt($inactiveSince, Criteria::LESS_THAN)
            ->filterByCustomerId(null, Criteria::ISNOTNULL)
            ->filterById($this->cartIdsHoldingAtLeastOneItem(), Criteria::IN)
            ->filterById($this->cartIdsAlreadyOrdered(), Criteria::NOT_IN);
    }

    /**
     * @return list<int>
     */
    private function cartIdsHoldingAtLeastOneItem(): array
    {
        return array_values(
            array_map(
                static fn ($cartId): int => (int) $cartId,
                CartItemQuery::create()->select('CartId')->distinct()->find()->toArray()
            )
        );
    }

    /**
     * @return list<int>
     */
    private function cartIdsAlreadyOrdered(): array
    {
        return array_values(
            array_map(
                static fn ($cartId): int => (int) $cartId,
                OrderQuery::create()->select('CartId')->distinct()->find()->toArray()
            )
        );
    }
}
