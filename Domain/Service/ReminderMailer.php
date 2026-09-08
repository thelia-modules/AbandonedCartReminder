<?php

declare(strict_types=1);

namespace AbandonedCartReminder\Domain\Service;

use AbandonedCartReminder\Model\AbandonedCart;
use Thelia\Mailer\MailerFactory;
use Thelia\Model\ConfigQuery;
use Thelia\Model\Customer;

final readonly class ReminderMailer
{
    public function __construct(
        private MailerFactory $mailer,
        private ReminderConfiguration $configuration,
        private CartRecoveryLink $recoveryLink,
        private ReminderConsent $consent,
    ) {
    }

    public function send(AbandonedCart $trackedCart, int $reminderNumber): bool
    {
        $email = (string) $trackedCart->getEmail();

        if ('' === $email) {
            return false;
        }

        $messageCode = $this->configuration->messageCode($reminderNumber);
        $customer = $trackedCart->getCustomer();

        $parameters = [
            'cart_id' => $trackedCart->getCartId(),
            'currency_id' => $trackedCart->getCart()?->getCurrencyId(),
            'recovery_token' => $this->recoveryLink->issue($trackedCart),
            'unsubscribe_token' => $this->consent->unsubscribeToken($email),
            'reminder_number' => $reminderNumber,
            'reminder_locale' => $customer?->getCustomerLang()?->getLocale() ?? $trackedCart->getLocale(),
        ];

        if ($customer instanceof Customer) {
            $this->mailer->sendEmailToCustomer($messageCode, $customer, $parameters);

            return true;
        }

        $this->mailer->sendEmailMessage(
            $messageCode,
            [ConfigQuery::getStoreEmail() => ConfigQuery::getStoreName()],
            [$email => $email],
            $parameters,
            $trackedCart->getLocale(),
        );

        return true;
    }
}
