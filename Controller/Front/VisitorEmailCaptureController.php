<?php

declare(strict_types=1);

namespace AbandonedCartReminder\Controller\Front;

use AbandonedCartReminder\Domain\Service\AbandonedCartTracker;
use AbandonedCartReminder\Form\VisitorEmailForm;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Thelia\Controller\Front\BaseFrontController;
use Thelia\Core\HttpFoundation\Session\Session;
use Thelia\Model\Cart;
use Thelia\Tools\URL;

class VisitorEmailCaptureController extends BaseFrontController
{
    #[Route('/cart-reminder/remember-me', name: 'abandoned_cart_reminder_capture', methods: ['POST'])]
    public function remember(
        Request $request,
        EventDispatcherInterface $dispatcher,
        AbandonedCartTracker $tracker,
    ): RedirectResponse {
        $form = $this->createForm(VisitorEmailForm::getName());

        try {
            $email = (string) $this->validateForm($form)->get('email')->getData();
        } catch (\Throwable) {
            return new RedirectResponse(URL::getInstance()->getBaseUrl());
        }

        $session = $request->getSession();
        $cart = $session instanceof Session ? $session->getSessionCart($dispatcher) : null;

        if ($cart instanceof Cart && !$cart->isNew()) {
            $tracker->trackVisitorEmail($cart, $email, $session?->getLang()?->getLocale());
        }

        return new RedirectResponse($request->headers->get('referer') ?? URL::getInstance()->getBaseUrl());
    }
}
