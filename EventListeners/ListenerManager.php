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
use Propel\Runtime\Exception\PropelException;
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
use Symfony\Component\HttpKernel\Event\RequestEvent;

class ListenerManager implements EventSubscriberInterface
{
    /** @var  SecurityContext */
    protected SecurityContext $securityContext;

    /** @var  RequestStack */
    protected RequestStack $requestStack;

    /** @var  MailerFactory */
    protected MailerFactory $mailer;

    /**
     * ListenerManager constructor.
     * @param SecurityContext $securityContext
     * @param RequestStack $requestStack
     * @param MailerFactory $mailer
     */
    public function __construct(
        SecurityContext $securityContext,
        RequestStack $requestStack,
        MailerFactory $mailer)
    {
        $this->securityContext = $securityContext;
        $this->requestStack = $requestStack;
        $this->mailer = $mailer;
    }



    /**
     * @return array|null
     */
    protected function getCustomerEmailAndLocale(): ?array
    {
        /** @var Customer $customer */
        if (null !== $customer = $this->securityContext->getCustomerUser()) {
            return [ 'email' => $customer->getEmail(), 'locale' => $customer->getCustomerLang()->getLocale() ];
        }

        /** @var Session $session */
        $session = $this->requestStack->getCurrentRequest()?->getSession();
        $email = $session->get('utm_source_email');

        if (null !== $email) {
            return [ 'email' => $email, 'locale' => $session->getLang()->getLocale() ];
        }

        return null;
    }

    /**
     * @param Cart $cart
     * @throws \Exception
     * @throws PropelException
     */
    protected function storeCart(Cart $cart): void
    {
        // If the cart isn't too old, we store it
        if ($this->isStorable($cart) && null !== $data = $this->getCustomerEmailAndLocale()) {
            // Delete all carts linked to this customer
            AbandonedCartQuery::create()
                ->filterByEmailClient($data['email'], Criteria::LIKE)
                ->delete();

            // Store the new cart
            (new AbandonedCart())
                ->setCartId($cart->getId())
                ->setEmailClient($data['email'])
                ->setLocale($data['locale'])
                ->setLoginToken(uniqid('', true))
                ->setLastUpdate(new \DateTime())
                ->save();
        }
    }

    /**
     * @param CartRestoreEvent $event
     * @throws \Exception
     */
    public function restoreCart(CartRestoreEvent $event): void
    {
        $this->storeCart($event->getCart());
    }

    /**
     * @param CartPersistEvent $event
     * @throws \Exception
     */
    public function persistCart(CartPersistEvent $event): void
    {
        $this->storeCart($event->getCart());
    }

    /**
     * @param CartDuplicationEvent $event
     * @throws \Exception
     * @throws PropelException
     */
    public function duplicateCart(CartDuplicationEvent $event): void
    {
        $originalCart = $event->getOriginalCart();

        // Delete the old cart
        if (null !== $abandonedCart = AbandonedCartQuery::create()->findOneByCartId($originalCart->getId())) {
            $abandonedCart->delete();
        }

        // Do not store the old cart and store the new duplicated one
        if ($this->isStorable($originalCart)) {
            $this->storeCart($event->getDuplicatedCart());
        }
    }

    /**
     * @param Cart $cart
     * @return bool
     * @throws \Exception
     * @throws PropelException
     */
    protected function isStorable(Cart $cart): bool
    {
        if ($cart->getId() > 0 && $cart->countCartItems() > 0) {
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
     * @throws PropelException
     */
    public function updateCart(CartEvent $event): void
    {
        // Only create the abandoned cart if it doesn't exist yet
        // Do NOT update lastUpdate when cart is modified - this would reset the timer!
        if ($this->isStorable($event->getCart())) {
            if (null === AbandonedCartQuery::create()->findOneByCartId($event->getCart()->getId())) {
                // Cart doesn't exist in abandoned_cart table yet, create it
                $this->storeCart($event->getCart());
            }
            // If cart already exists, do nothing - preserve the original lastUpdate timestamp
        }
    }

    /**
     * @param OrderEvent $event
     * @throws PropelException
     */
    public function orderStatusUpdate(OrderEvent $event): void
    {
        // If the order is paid, delete the linked cart.
        $order = $event->getOrder();

        if ($order->isPaid() && null !== $abandonedCart = AbandonedCartQuery::create()->findOneByCartId($order->getCartId())) {
            $abandonedCart->delete();
        }
    }

    /**
     * @param ActionEvent $event
     * @throws \Exception
     * @throws PropelException
     */
    public function cron(ActionEvent $event): void
    {
        Tlog::getInstance()->notice("=== Debut examen des paniers abandonnes ===");
        
        // Log configuration
        $delay1 = AbandonedCartReminder::getConfigValue(AbandonedCartReminder::REMINDER_TIME_1);
        $delay2 = AbandonedCartReminder::getConfigValue(AbandonedCartReminder::REMINDER_TIME_2);
        Tlog::getInstance()->notice("Configuration: 1er rappel = {$delay1} min, 2e rappel = {$delay2} min");

        // Count total abandoned carts
        $totalCarts = AbandonedCartQuery::create()->count();
        Tlog::getInstance()->notice("Total paniers abandonnes en base: {$totalCarts}");

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
        $deletedCount = AbandonedCartQuery::create()
            ->filterBystatus(AbandonedCart::RAPPEL_2_ENVOYE)
            ->count();
            
        AbandonedCartQuery::create()
            ->filterBystatus(AbandonedCart::RAPPEL_2_ENVOYE)
            ->delete();
            
        if ($deletedCount > 0) {
            Tlog::getInstance()->notice("Suppression de {$deletedCount} paniers avec 2e rappel deja envoye");
        }
        
        Tlog::getInstance()->notice("=== Fin examen des paniers abandonnes ===");
    }

    /**
     * @param $varDelai
     * @param $filtreStatus
     * @param $codeMessage
     * @param $nouvelEtat
     * @throws \Exception
     * @throws PropelException
     */
    protected function sendReminder($varDelai, $filtreStatus, $codeMessage, $nouvelEtat): void
    {
        $delaiMinutes = AbandonedCartReminder::getConfigValue($varDelai);
        $delai = new \DateTime();
        $delai = $delai->sub(new \DateInterval('PT' . $delaiMinutes . 'M'));

        Tlog::getInstance()->notice("--- Verification rappel (statut {$filtreStatus} -> {$nouvelEtat}) ---");
        Tlog::getInstance()->notice("Recherche paniers avec lastUpdate < " . $delai->format('Y-m-d H:i:s') . " (il y a {$delaiMinutes} min)");

        $abandonedCarts = AbandonedCartQuery::create()
            ->filterByStatus($filtreStatus)
            ->filterByLastUpdate($delai, Criteria::LESS_THAN)
            ->find();

        $foundCarts = count($abandonedCarts);
        Tlog::getInstance()->notice("Trouve {$foundCarts} paniers eligibles pour rappel");

        /** @var AbandonedCart $abandonedCart */
        foreach ($abandonedCarts as $abandonedCart) {
            $cartItems = $abandonedCart->getCart()->countCartItems();
            $minutesDepuis = (new \DateTime())->diff($abandonedCart->getLastUpdate())->i + 
                           ((new \DateTime())->diff($abandonedCart->getLastUpdate())->h * 60);
            
            Tlog::getInstance()->notice("Panier ID {$abandonedCart->getCartId()}: {$cartItems} articles, abandonne depuis {$minutesDepuis} min");
            
            // Ensure that the cart is not empty.
            if ($cartItems > 0) {
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
                    Tlog::getInstance()->notice("✓ Rappel {$nouvelEtat} envoye a " . $abandonedCart->getEmailClient());
                } catch (\Exception $ex) {
                    Tlog::getInstance()->error("✗ Echec envoi rappel {$nouvelEtat} a " . $abandonedCart->getEmailClient() . ": " . $ex->getMessage());
                }

                $abandonedCart->clearAllReferences();

                $abandonedCart
                    ->setstatus($nouvelEtat)
                    ->save();
            } else {
                Tlog::getInstance()->notice("✗ Panier vide supprime: ID " . $abandonedCart->getCartId());
                $abandonedCart->delete();
            }
        }
    }

    /**
     * @return array[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
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
