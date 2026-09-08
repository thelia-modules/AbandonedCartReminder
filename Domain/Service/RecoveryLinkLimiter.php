<?php

declare(strict_types=1);

namespace AbandonedCartReminder\Domain\Service;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\CacheStorage;

final readonly class RecoveryLinkLimiter
{
    private RateLimiterFactory $perTrackedCartLimiter;
    private RateLimiterFactory $perClientLimiter;

    public function __construct(
        #[Autowire(service: 'cache.app')]
        CacheItemPoolInterface $cache,
        private RequestStack $requestStack,
    ) {
        $storage = new CacheStorage($cache);

        $this->perTrackedCartLimiter = new RateLimiterFactory([
            'id' => 'abandoned_cart_recovery_per_cart',
            'policy' => 'sliding_window',
            'limit' => 10,
            'interval' => '15 minutes',
        ], $storage);

        $this->perClientLimiter = new RateLimiterFactory([
            'id' => 'abandoned_cart_recovery_per_client',
            'policy' => 'sliding_window',
            'limit' => 30,
            'interval' => '15 minutes',
        ], $storage);
    }

    public function allows(string $token): bool
    {
        $clientIp = $this->requestStack->getMainRequest()?->getClientIp();

        if (null !== $clientIp && !$this->perClientLimiter->create($clientIp)->consume()->isAccepted()) {
            return false;
        }

        return $this->perTrackedCartLimiter->create($this->trackedCartKey($token))->consume()->isAccepted();
    }

    private function trackedCartKey(string $token): string
    {
        $trackedCartId = explode('.', $token)[0];

        return ctype_digit($trackedCartId) ? 'tracked-cart-'.$trackedCartId : 'malformed';
    }
}
