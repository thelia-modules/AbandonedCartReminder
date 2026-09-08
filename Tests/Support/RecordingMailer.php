<?php

declare(strict_types=1);

namespace AbandonedCartReminder\Tests\Support;

use Thelia\Mailer\MailerFactory;
use Thelia\Model\Customer;

final class RecordingMailer extends MailerFactory
{
    /** @var list<array{code: string, customer: ?Customer, to?: ?string, parameters: array<string, mixed>}> */
    public array $customerMessages = [];

    public function __construct()
    {
    }

    public function sendEmailToCustomer(string $messageCode, Customer $customer, array $messageParameters = []): void
    {
        $this->customerMessages[] = [
            'code' => $messageCode,
            'customer' => $customer,
            'parameters' => $messageParameters,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function sendEmailMessage(
        string $messageCode,
        array $from,
        array $to,
        array $messageParameters = [],
        ?string $locale = null,
        array $cc = [],
        array $bcc = [],
        array $replyTo = [],
    ): void {
        $this->customerMessages[] = [
            'code' => $messageCode,
            'customer' => null,
            'to' => array_key_first($to),
            'parameters' => $messageParameters,
        ];
    }

    public function recipientsOf(string $messageCode): array
    {
        return array_values(
            array_map(
                static fn (array $message): ?string => $message['to'] ?? $message['customer']?->getEmail(),
                array_filter(
                    $this->customerMessages,
                    static fn (array $message): bool => $message['code'] === $messageCode,
                ),
            ),
        );
    }

    public function parametersOfMessagesSent(string $messageCode): array
    {
        return array_values(
            array_map(
                static fn (array $message): array => $message['parameters'],
                array_filter(
                    $this->customerMessages,
                    static fn (array $message): bool => $message['code'] === $messageCode,
                ),
            ),
        );
    }
}
