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

use Thelia\Core\Template\Element\BaseLoop;
use Thelia\Core\Template\Element\LoopResult;
use Thelia\Core\Template\Element\LoopResultRow;
use Thelia\Core\Template\Element\PropelSearchLoopInterface;
use Thelia\Core\Template\Loop\Argument\Argument;
use Thelia\Core\Template\Loop\Argument\ArgumentCollection;
use Thelia\Model\Cart as CartModel;
use Thelia\Model\CartQuery;
use Thelia\TaxEngine\TaxEngine;

/**
 * @method int getCartId()
 */
class CartInfo extends BaseLoop implements PropelSearchLoopInterface
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

    public function buildModelCriteria()
    {
        return CartQuery::create()->filterById($this->getCartId());
    }

    /**
     * @param LoopResult $loopResult
     *
     * @return LoopResult
     */
    public function parseResults(LoopResult $loopResult)
    {
        /** @var TaxEngine */
        $taxEngine = $this->container->get('thelia.taxEngine');

        $taxCountry = $taxEngine->getDeliveryCountry();

        /** @var CartModel $cart */
        foreach ($loopResult->getResultDataCollection() as $cart) {
            $loopResultRow = new LoopResultRow($cart);

            $loopResultRow->set("TOTAL_TAXED_PRICE", $cart->getTaxedAmount($taxCountry));

            $loopResult->addRow($loopResultRow);
        }

        return $loopResult;
    }
}
