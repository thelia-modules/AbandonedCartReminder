<?php

declare(strict_types=1);

namespace AbandonedCartReminder\Controller\Front;

use AbandonedCartReminder\Domain\Service\ReminderConsent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Thelia\Controller\Front\BaseFrontController;

class ReminderUnsubscribeController extends BaseFrontController
{
    #[Route('/cart-reminder/unsubscribe/{token}', name: 'abandoned_cart_reminder_unsubscribe', methods: ['GET'])]
    public function unsubscribe(string $token, ReminderConsent $consent): Response
    {
        $email = $consent->emailForToken($token);

        if (null !== $email) {
            $consent->refuse($email);
        }

        return $this->render(
            'reminder-unsubscribe',
            ['unsubscribed' => null !== $email],
            null !== $email ? Response::HTTP_OK : Response::HTTP_GONE,
        );
    }
}
