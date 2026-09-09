<?php

declare(strict_types=1);

namespace AbandonedCartReminder\Domain\Service;

use AbandonedCartReminder\Model\AbandonedCart;
use AbandonedCartReminder\Model\AbandonedCartQuery;
use AbandonedCartReminder\Model\AbandonedCartReminderOptOut;
use AbandonedCartReminder\Model\AbandonedCartReminderOptOutQuery;
use Propel\Runtime\ActiveQuery\Criteria;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ReminderConsent
{
    private const SIGNATURE_ALGORITHM = 'sha256';
    private const SIGNATURE_PURPOSE = 'thelia.abandoned_cart_reminder.unsubscribe';

    public function __construct(
        #[Autowire(param: 'kernel.secret')]
        private string $applicationSecret = '',
    ) {
    }

    public function isRefusedFor(string $email): bool
    {
        return null !== AbandonedCartReminderOptOutQuery::create()
            ->filterByEmail($this->normalize($email))
            ->findOne();
    }

    public function refuse(string $email): void
    {
        $normalized = $this->normalize($email);

        if ('' === $normalized) {
            return;
        }

        if (!$this->isRefusedFor($normalized)) {
            (new AbandonedCartReminderOptOut())->setEmail($normalized)->save();
        }

        AbandonedCartQuery::create()
            ->filterByEmail($normalized)
            ->filterByStatus([AbandonedCart::STATUS_PENDING, AbandonedCart::STATUS_REMINDED], Criteria::IN)
            ->update(['Status' => AbandonedCart::STATUS_STOPPED]);
    }

    public function forget(string $email): void
    {
        $normalized = $this->normalize($email);

        AbandonedCartReminderOptOutQuery::create()->filterByEmail($normalized)->delete();
        AbandonedCartQuery::create()->filterByEmail($normalized)->delete();
    }

    public function unsubscribeToken(string $email): string
    {
        $normalized = $this->normalize($email);

        return $this->encode($normalized).'.'.$this->sign($normalized);
    }

    public function emailForToken(string $token): ?string
    {
        $parts = explode('.', $token);

        if (2 !== \count($parts)) {
            return null;
        }

        [$encodedEmail, $signature] = $parts;
        $email = $this->decode($encodedEmail);

        if (!hash_equals($this->sign($email), $signature)) {
            return null;
        }

        return '' === $email ? null : $email;
    }

    private function sign(string $email): string
    {
        $key = hash_hmac(self::SIGNATURE_ALGORITHM, self::SIGNATURE_PURPOSE, $this->applicationSecret, true);

        return hash_hmac(self::SIGNATURE_ALGORITHM, $email, $key);
    }

    private function encode(string $email): string
    {
        return rtrim(strtr(base64_encode($email), '+/', '-_'), '=');
    }

    private function decode(string $encoded): string
    {
        return (string) base64_decode(strtr($encoded, '-_', '+/'), true);
    }

    private function normalize(string $email): string
    {
        return mb_strtolower(trim($email));
    }
}
