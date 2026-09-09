<?php

declare(strict_types=1);

namespace AbandonedCartReminder\Domain\Service;

use AbandonedCartReminder\Model\AbandonedCart;
use AbandonedCartReminder\Model\AbandonedCartQuery;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class CartRecoveryLink
{
    private const SIGNATURE_ALGORITHM = 'sha256';
    private const SIGNATURE_PURPOSE = 'thelia.abandoned_cart_reminder.recovery_link';

    public function __construct(
        #[Autowire(param: 'kernel.secret')]
        private string $applicationSecret,
        private ReminderConfiguration $configuration,
    ) {
    }

    public function issue(AbandonedCart $trackedCart, ?int $lifetimeInSeconds = null): string
    {
        $token = $this->createToken($trackedCart, $lifetimeInSeconds);

        $trackedCart
            ->setRecoveryLinkFingerprint($this->fingerprint($token))
            ->setRecoveryLinkUsedAt(null)
            ->save();

        return $token;
    }

    public function findTrackedCartForToken(string $token): ?AbandonedCart
    {
        $parts = explode('.', $token);

        if (3 !== \count($parts)) {
            return null;
        }

        [$rawTrackedCartId, $rawExpiresAt, $signature] = $parts;

        if (!ctype_digit($rawTrackedCartId) || !ctype_digit($rawExpiresAt)) {
            return null;
        }

        $trackedCartId = (int) $rawTrackedCartId;
        $expiresAt = (int) $rawExpiresAt;
        $trackedCart = AbandonedCartQuery::create()->findPk($trackedCartId);

        $expectedSignature = $this->sign(
            $trackedCartId,
            $expiresAt,
            (string) $trackedCart?->getEmail(),
            $this->passwordHashBehind($trackedCart),
        );

        if (!hash_equals($expectedSignature, $signature) || !$trackedCart instanceof AbandonedCart) {
            return null;
        }

        if ($expiresAt <= time()) {
            return null;
        }

        if (null !== $trackedCart->getRecoveryLinkUsedAt()) {
            return null;
        }

        if (!hash_equals((string) $trackedCart->getRecoveryLinkFingerprint(), $this->fingerprint($token))) {
            return null;
        }

        return $trackedCart;
    }

    public function consume(AbandonedCart $trackedCart): void
    {
        $trackedCart->setRecoveryLinkUsedAt(new \DateTime())->save();
    }

    public function fingerprint(string $token): string
    {
        return hash(self::SIGNATURE_ALGORITHM, $token);
    }

    private function createToken(AbandonedCart $trackedCart, ?int $lifetimeInSeconds): string
    {
        $trackedCartId = (int) $trackedCart->getId();
        $expiresAt = time() + ($lifetimeInSeconds ?? $this->configuration->recoveryLinkLifetimeInSeconds());

        return \sprintf(
            '%d.%d.%s',
            $trackedCartId,
            $expiresAt,
            $this->sign(
                $trackedCartId,
                $expiresAt,
                (string) $trackedCart->getEmail(),
                $this->passwordHashBehind($trackedCart),
            ),
        );
    }

    private function sign(int $trackedCartId, int $expiresAt, string $email, string $passwordHash): string
    {
        $key = hash_hmac(
            self::SIGNATURE_ALGORITHM,
            self::SIGNATURE_PURPOSE,
            $this->applicationSecret,
            true,
        );

        return hash_hmac(
            self::SIGNATURE_ALGORITHM,
            implode("\0", [$trackedCartId, $expiresAt, $email, $passwordHash]),
            $key,
        );
    }

    private function passwordHashBehind(?AbandonedCart $trackedCart): string
    {
        return (string) $trackedCart?->getCustomer()?->getPassword();
    }
}
