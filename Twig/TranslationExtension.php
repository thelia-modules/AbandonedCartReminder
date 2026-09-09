<?php

declare(strict_types=1);

namespace AbandonedCartReminder\Twig;

use AbandonedCartReminder\AbandonedCartReminder;
use Thelia\Core\Translation\Translator;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class TranslationExtension extends AbstractExtension
{
    private const LOCALE_KEYS = ['reminder_locale', 'locale'];

    public function __construct(
        private readonly Translator $translator,
    ) {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter(
                'abandoned_cart_reminder_trans',
                $this->translate(...),
                ['needs_context' => true],
            ),
        ];
    }

    /**
     * @param array<string, mixed> $context
     * @param array<string, mixed> $parameters
     */
    public function translate(array $context, ?string $id, array $parameters = []): string
    {
        return $this->translator->trans(
            (string) $id,
            $parameters,
            AbandonedCartReminder::DOMAIN_NAME,
            $this->localeOf($context),
        );
    }

    /**
     * @param array<string, mixed> $context
     */
    private function localeOf(array $context): ?string
    {
        foreach (self::LOCALE_KEYS as $key) {
            $locale = $context[$key] ?? null;

            if (\is_string($locale) && '' !== $locale) {
                return $locale;
            }
        }

        return null;
    }
}
