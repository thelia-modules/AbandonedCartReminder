<?php
/*************************************************************************************/
/*      This file is part of the Thelia package.                                     */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*      email : dev@thelia.net                                                       */
/*      web : http://www.thelia.net                                                  */
/*                                                                                   */
/*      For the full copyright and license information, please view the LICENSE.txt  */
/*      file that was distributed with this source code.                             */
/*************************************************************************************/

namespace AbandonedCartReminder\Loop;

use Thelia\Core\Template\Loop\Argument\Argument;
use Thelia\Core\Template\Loop\Argument\ArgumentCollection;
use Thelia\Model\CartItemQuery;

/**
 * @method int getCartId()
 */
class Cart extends \Thelia\Core\Template\Loop\Cart
{
    /**
     *
     * @return \Thelia\Core\Template\Loop\Argument\ArgumentCollection
     */
    protected function getArgDefinitions()
    {
        return new ArgumentCollection(
            Argument::createIntTypeArgument('cart_id', null, true)
        );
    }

    public function buildArray()
    {
        $items = CartItemQuery::create()->findByCartId($this->getCartId());

        return iterator_to_array($items);
    }
}
