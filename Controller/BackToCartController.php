<?php
/*************************************************************************************/
/*      Copyright (c) Franck Allimant, CQFDev                                        */
/*      email : thelia@cqfdev.fr                                                     */
/*      web : http://www.cqfdev.fr                                                   */
/*                                                                                   */
/*      For the full copyright and license information, please view the LICENSE      */
/*      file that was distributed with this source code.                             */
/*************************************************************************************/

/**
 * Created by Franck Allimant, CQFDev <franck@cqfdev.fr>
 * Date: 14/05/2017 11:31
 */

namespace AbandonedCartReminder\Controller;

use AbandonedCartReminder\Model\AbandonedCartQuery;
use Thelia\Controller\Front\BaseFrontController;
use Thelia\Core\Event\Customer\CustomerLoginEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Model\CustomerQuery;
use Thelia\Tools\URL;

class BackToCartController extends BaseFrontController
{
    public function loadCart($token)
    {
        if (null !== $abandonedCart = AbandonedCartQuery::create()->findOneByLoginToken($token)) {
            // If there's a customer, connect it.
            if (null !== $customer = CustomerQuery::create()->findOneByEmail($abandonedCart->getEmailClient())) {
                $this->dispatch(TheliaEvents::CUSTOMER_LOGIN, new CustomerLoginEvent($customer));
            }

            // Restores the cart.
            $this->getSession()->setSessionCart($abandonedCart->getCart());

            // Send the customer to his cart page.
            return $this->generateRedirect(URL::getInstance()->absoluteUrl('/cart'));
        }

        return $this->generateRedirect(URL::getInstance()->getBaseUrl());
    }
}
