<?php

declare(strict_types=1);

namespace AbandonedCartReminder\Domain\Service;

use Thelia\Model\ConfigQuery;
use Thelia\Model\LangQuery;

final readonly class ReminderPrerequisites
{
    private const CART_WITHOUT_ORDER_PURGE_KEY = 'purification_cart_no_order_days';
    private const SHOP_URL_KEY = 'url_site';
    private const ONE_DOMAIN_FOREACH_LANG_KEY = 'one_domain_foreach_lang';

    public function __construct(
        private ReminderConfiguration $configuration,
    ) {
    }

    public function blockSending(): ?string
    {
        if ('' !== $this->shopUrl()) {
            return null;
        }

        return \sprintf(
            'The shop has no URL, so every recovery link would point at http://localhost and no customer could follow one. Set "%s" in Configuration > Configuration parameters, then run this again.',
            self::SHOP_URL_KEY,
        );
    }

    /**
     * @return list<string>
     */
    public function warnings(): array
    {
        $warnings = [];

        if (!$this->configuration->isEnabled(1)) {
            $warnings[] = 'The first reminder has no delay, so no cart will ever be reminded.';
        }

        $purgeInHours = $this->cartWithoutOrderPurgeInDays() * 24;
        $chainInHours = $this->configuration->longestReminderChainInHours();

        if ($chainInHours > $purgeInHours) {
            $warnings[] = \sprintf(
                'The reminder chain spans %d hours but carts without an order are purged after %d hours, so the last reminders will never be sent.',
                $chainInHours,
                $purgeInHours,
            );
        }

        return $warnings;
    }

    private function shopUrl(): string
    {
        if (1 === (int) ConfigQuery::read(self::ONE_DOMAIN_FOREACH_LANG_KEY)) {
            return trim((string) LangQuery::create()->findOneByByDefault(1)?->getUrl());
        }

        return trim((string) ConfigQuery::read(self::SHOP_URL_KEY));
    }

    private function cartWithoutOrderPurgeInDays(): int
    {
        return (int) ConfigQuery::read(self::CART_WITHOUT_ORDER_PURGE_KEY, '60');
    }
}
