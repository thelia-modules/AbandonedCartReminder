<?php

declare(strict_types=1);

namespace AbandonedCartReminder\Loop;

use Thelia\Core\Template\Loop\Argument\Argument;
use Thelia\Core\Template\Loop\Argument\ArgumentCollection;
use Thelia\Core\Template\Loop\Cart;
use Thelia\Model\CartItemQuery;

/**
 * @method int getCartId()
 */
class AbandonedCartItem extends Cart
{
    protected function getArgDefinitions(): ArgumentCollection
    {
        return new ArgumentCollection(
            Argument::createIntTypeArgument('cart_id', null, true)
        );
    }

    public function buildArray(): array
    {
        return iterator_to_array(
            CartItemQuery::create()->findByCartId($this->getCartId())
        );
    }
}
