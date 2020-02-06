<?php
/*************************************************************************************/
/*                                                                                   */
/*      This file is not free software                                               */
/*                                                                                   */
/*      Copyright (c) Franck Allimant, CQFDev                                        */
/*      email : thelia@cqfdev.fr                                                     */
/*      web : http://www.cqfdev.fr                                                   */
/*                                                                                   */
/*************************************************************************************/

/**
 * Created by Franck Allimant, CQFDev <franck@cqfdev.fr>
 * Date: 20/12/2015 20:25
 */

namespace AbandonedCartReminder\EventListeners;

use AbandonedCartReminder\Events\AbandonedCartEvent;
use AbandonedCartReminder\Model\AbandonedCart;
use AbandonedCartReminder\Model\AbandonedCartQuery;
use AbandonedCartReminder\AbandonedCartReminder;
use Propel\Runtime\ActiveQuery\Criteria;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelEvents;
use Thelia\Core\Event\ActionEvent;
use Thelia\Core\Event\Cart\CartDuplicationEvent;
use Thelia\Core\Event\Cart\CartEvent;
use Thelia\Core\Event\Cart\CartPersistEvent;
use Thelia\Core\Event\Cart\CartRestoreEvent;
use Thelia\Core\Event\Order\OrderEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Core\HttpFoundation\Session\Session;
use Thelia\Core\Security\SecurityContext;
use Thelia\Log\Tlog;
use Thelia\Mailer\MailerFactory;
use Thelia\Model\Cart;
use Thelia\Model\ConfigQuery;
use Thelia\Model\Customer;

class ListenerManager implements EventSubscriberInterface
{
    /** @var  SecurityContext */
    protected $securityContext;

    /** @var  RequestStack */
    protected $requestStack;

    /** @var  MailerFactory */
    protected $mailer;

    /**
     * ListenerManager constructor.
     * @param SecurityContext $securityContext
     * @param RequestStack $requestStack
     * @param MailerFactory $mailer
     */
    public function __construct(SecurityContext $securityContext, RequestStack $requestStack, MailerFactory $mailer)
    {
        $this->securityContext = $securityContext;
        $this->requestStack = $requestStack;
        $this->mailer = $mailer;
    }

    public function detectCustomerEmailFromRequest()
    {
        $request = $this->requestStack->getCurrentRequest();

        $source = $request->get('utm_source');

        if ($source == 'mail' && null !== $email = $request->get('mail')) {
            $request->getSession()->set('utm_source_email', $email);;
        }
    }

    /**
     * @return array
     */
    protected function getCustomerEmailAndLocale()
    {
        /** @var Customer $customer */
        if (null !== $customer = $this->securityContext->getCustomerUser()) {
            return [ 'email' => $customer->getEmail(), 'locale' => $customer->getCustomerLang()->getLocale() ];
        }

        /** @var Session $session */
        $session = $this->requestStack->getCurrentRequest()->getSession();
        $email = $session->get('utm_source_email');

        if (null !== $email) {
            return [ 'email' => $email, 'locale' => $session->getLang()->getLocale() ];
        }

        return null;
    }

    /**
     * @param Cart $cart
     * @throws \Exception
     * @throws \Propel\Runtime\Exception\PropelException
     */
    protected function storeCart(Cart $cart)
    {
        // If the cart isn't too old, we store it
        if ($this->isStorable($cart)) {
            if (null !== $data = $this->getCustomerEmailAndLocale()) {
                // Delete all carts linked to this customer
                AbandonedCartQuery::create()
                    ->filterByEmailClient($data['email'], Criteria::LIKE)
                    ->delete();

                // Store the new cart
                (new AbandonedCart())
                    ->setCartId($cart->getId())
                    ->setEmailClient($data['email'])
                    ->setLocale($data['locale'])
                    ->setLoginToken(uniqid())
                    ->setLastUpdate(new \DateTime())
                    ->save();
            }
        }
    }

    /**
     * @param CartRestoreEvent $event
     * @throws \Exception
     */
    public function restoreCart(CartRestoreEvent $event)
    {
        $this->storeCart($event->getCart());
    }

    /**
     * @param CartPersistEvent $event
     * @throws \Exception
     */
    public function persistCart(CartPersistEvent $event)
    {
        $this->storeCart($event->getCart());
    }

    /**
     * @param CartDuplicationEvent $event
     * @throws \Exception
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function duplicateCart(CartDuplicationEvent $event)
    {
        $originalCart = $event->getOriginalCart();

        // Delete the old
        if (null !== $pa = AbandonedCartQuery::create()->findOneByCartId($originalCart->getId())) {
            $pa->delete();
        }

        // Do not store an old cart which could be duplicated
        if ($this->isStorable($originalCart)) {
            $this->storeCart($event->getDuplicatedCart());
        }
    }

    /**
     * @param Cart $cart
     * @return bool
     * @throws \Exception
     * @throws \Propel\Runtime\Exception\PropelException
     */
    protected function isStorable(Cart $cart)
    {
        if (! empty($cart) && $cart->getId() > 0 && $cart->countCartItems() > 0) {
            $timeSecondReminder = new \DateTime();

            $timeSecondReminder
                ->add(
                    new \DateInterval(
                        'PT' . AbandonedCartReminder::getConfigValue(AbandonedCartReminder::REMINDER_TIME_2) . 'M'
                    )
                );

            // The cart is deprecated if it exists since longer than the time to send the second reminder.
            return $cart->getCreatedAt() < $timeSecondReminder;
        }

        return false;
    }

    /**
     * @param CartEvent $event
     * @throws \Exception
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function updateCart(CartEvent $event)
    {
        // Update UpdatedAt
        if ($this->isStorable($event->getCart())) {
            if (null !== $pa = AbandonedCartQuery::create()->findOneByCartId($event->getCart()->getId()))
                $pa->setLastUpdate(new \DateTime())->save();
            else
                $this->storeCart($event->getCart());
        }
    }

    /**
     * @param OrderEvent $event
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function orderStatusUpdate(OrderEvent $event)
    {
        // If the order is paid, delete the linked cart.
        $order = $event->getOrder();

        if ($order->isPaid()) {
            if (null !== $pa = AbandonedCartQuery::create()->findOneByCartId($order->getCartId())) {
                $pa->delete();
            }
        }
    }

    /**
     * @param ActionEvent $event
     * @throws \Exception
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function cron(ActionEvent $event)
    {
        Tlog::getInstance()->notice("Examen des paniers abandonnes");

        $this->sendReminder(
            AbandonedCartReminder::REMINDER_TIME_1,
            AbandonedCart::RAPPEL_PAS_ENVOYE,
            AbandonedCartReminder::REMINDER_MESSAGE_1,
            AbandonedCart::RAPPEL_1_ENVOYE
        );

        $this->sendReminder(
            AbandonedCartReminder::REMINDER_TIME_2,
            AbandonedCart::RAPPEL_1_ENVOYE,
            AbandonedCartReminder::REMINDER_MESSAGE_2,
            AbandonedCart::RAPPEL_2_ENVOYE
        );

        // Delete everyone who already got the second reminder
        AbandonedCartQuery::create()
            ->filterBystatus(AbandonedCart::RAPPEL_2_ENVOYE)
            ->delete()
        ;
    }

    /**
     * @param $varDelai
     * @param $filtreStatus
     * @param $codeMessage
     * @param $nouvelEtat
     * @throws \Exception
     * @throws \Propel\Runtime\Exception\PropelException
     */
    protected function sendReminder($varDelai, $filtreStatus, $codeMessage, $nouvelEtat)
    {
        $delai = new \DateTime();
        $delai = $delai->sub(new \DateInterval('PT' . AbandonedCartReminder::getConfigValue($varDelai) . 'M'));

        $abandonedCarts = AbandonedCartQuery::create()
            ->filterByStatus($filtreStatus)
            ->filterByLastUpdate($delai, Criteria::LESS_THAN)
            ->find();

        /** @var AbandonedCart $abandonedCart */
        foreach ($abandonedCarts as $abandonedCart) {
            // Vérifier que le cart n'est pas vide.
            if ($abandonedCart->getCart()->countCartItems() > 0) {
                try {
                    $this->mailer->sendEmailMessage(
                        $codeMessage,
                        [ConfigQuery::getStoreEmail() => ConfigQuery::getStoreName()],
                        [$abandonedCart->getEmailClient() => $abandonedCart->getEmailClient()],
                        [
                            'cart_id' => $abandonedCart->getCartId(),
                            'login_token' => $abandonedCart->getLoginToken(),
                            'code_promo' => AbandonedCartReminder::getConfigValue(AbandonedCartReminder::PROMO_CODE_REMINDER)
                        ],
                        $abandonedCart->getLocale()
                    );
                    Tlog::getInstance()->notice("Sending reminder number. " . $nouvelEtat . " to customer " . $abandonedCart->getEmailClient());
                } catch (\Exception $ex) {
                    Tlog::getInstance()->error("Failed to send reminder number. " . $nouvelEtat . " to customer " . $abandonedCart->getEmailClient() . ". Reason:".$ex->getMessage());
                }

                $abandonedCart->clearAllReferences();

                $abandonedCart
                    ->setstatus($nouvelEtat)
                    ->save();
            } else {
                // Delete this deprecated cart
                $abandonedCart->delete();
            }
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => [ 'detectCustomerEmailFromRequest', 100 ],

            TheliaEvents::CART_PERSIST => ['persistCart', 100],
            TheliaEvents::CART_RESTORE_CURRENT => ['restoreCart', 100],
            TheliaEvents::CART_DUPLICATE  => ['duplicateCart', 100],

            TheliaEvents::CART_ADDITEM => [ 'updateCart', 100 ],
            TheliaEvents::CART_DELETEITEM => [ 'updateCart', 100 ],
            TheliaEvents::CART_UPDATEITEM => [ 'updateCart', 100 ],

            TheliaEvents::ORDER_UPDATE_STATUS => [ 'orderStatusUpdate', 100 ],

            AbandonedCartEvent::EXAMINE_CARTS_EVENT => [ 'cron', 100 ]
        ];
    }
}
