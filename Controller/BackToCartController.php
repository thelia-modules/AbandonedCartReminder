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
use Propel\Runtime\Exception\PropelException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Thelia\Controller\Front\BaseFrontController;
use Thelia\Core\Event\Customer\CustomerLoginEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Core\HttpFoundation\Request;
use Thelia\Model\CustomerQuery;
use Thelia\Tools\URL;

class BackToCartController extends BaseFrontController
{
    /**
     * @param $token
     * @param EventDispatcherInterface $dispatcher
     * @param Request $request
     * @return RedirectResponse|Response
     * @throws PropelException
     */
    #[Route('/back-to-cart/{token}', name: 'abandoned_cart_reminder_cart', methods: ['GET'])]
    public function loadCart($token, EventDispatcherInterface $dispatcher, Request $request): RedirectResponse|Response
    {
        if (null !== $abandonedCart = AbandonedCartQuery::create()->findOneByLoginToken($token)) {
            // If there's a customer, connect it.
            if (null !== $customer = CustomerQuery::create()->findOneByEmail($abandonedCart->getEmailClient())) {
                $dispatcher->dispatch(new CustomerLoginEvent($customer),TheliaEvents::CUSTOMER_LOGIN);
            }

            // Restores the cart.
            $request->getSession()?->setSessionCart($abandonedCart->getCart());

            // Send the customer to his cart page.
            return $this->generateRedirect(URL::getInstance()->absoluteUrl('/cart'));
        }

        return $this->generateRedirect(URL::getInstance()->getBaseUrl());
    }
}
