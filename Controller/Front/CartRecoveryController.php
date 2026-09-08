<?php

declare(strict_types=1);

namespace AbandonedCartReminder\Controller\Front;

use AbandonedCartReminder\Domain\Service\CartRecoveryLink;
use AbandonedCartReminder\Domain\Service\RecoveryLinkLimiter;
use AbandonedCartReminder\Domain\Service\RestrictedSession;
use AbandonedCartReminder\Model\AbandonedCart;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Thelia\Core\Event\Customer\CustomerLoginEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Core\HttpFoundation\Session\Session;
use Thelia\Model\Cart;
use Thelia\Model\Customer;
use Thelia\Tools\URL;

final class CartRecoveryController
{
    private const RESTRICTED_SESSION_LIFETIME_IN_SECONDS = 1800;
    private const CART_ROUTE = 'checkout_cart';

    public function __construct(
        private readonly CartRecoveryLink $recoveryLink,
        private readonly RecoveryLinkLimiter $limiter,
        private readonly RestrictedSession $restrictedSession,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[Route('/back-to-cart/{token}', name: 'abandoned_cart_reminder_recover', methods: ['GET'])]
    public function recover(
        string $token,
        Request $request,
        EventDispatcherInterface $dispatcher,
    ): RedirectResponse {
        if (!$this->limiter->allows($token)) {
            return $this->refuse();
        }

        $trackedCart = $this->recoveryLink->findTrackedCartForToken($token);

        if (!$trackedCart instanceof AbandonedCart) {
            return $this->refuse();
        }

        $cart = $trackedCart->getCart();

        if (!$cart instanceof Cart) {
            return $this->refuse();
        }

        $session = $request->getSession();

        if (!$session instanceof Session) {
            return $this->refuse();
        }

        $customer = $trackedCart->getCustomer();

        if ($customer instanceof Customer && 0 === $customer->getIsGuest()) {
            $dispatcher->dispatch(new CustomerLoginEvent($customer), TheliaEvents::CUSTOMER_LOGIN);
            $this->restrictedSession->open(self::RESTRICTED_SESSION_LIFETIME_IN_SECONDS, (int) $customer->getId());
            $cart->setCustomerId($customer->getId())->save();
        }

        $session->setSessionCart($cart);
        $this->recoveryLink->consume($trackedCart);

        return new RedirectResponse($this->cartUrl());
    }

    private function refuse(): RedirectResponse
    {
        return new RedirectResponse(URL::getInstance()->getBaseUrl());
    }

    private function cartUrl(): string
    {
        try {
            return $this->urlGenerator->generate(self::CART_ROUTE, [], UrlGeneratorInterface::ABSOLUTE_URL);
        } catch (RouteNotFoundException) {
            return URL::getInstance()->getBaseUrl();
        }
    }
}
