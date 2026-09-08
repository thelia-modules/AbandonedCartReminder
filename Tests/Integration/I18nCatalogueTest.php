<?php

declare(strict_types=1);

namespace AbandonedCartReminder\Tests\Integration;

use AbandonedCartReminder\AbandonedCartReminder;
use Thelia\Core\Translation\Translator;
use Thelia\Test\IntegrationTestCase;

final class I18nCatalogueTest extends IntegrationTestCase
{
    private const BACK_OFFICE_DOMAIN = AbandonedCartReminder::DOMAIN_NAME.'.bo.default-twig';

    public function testTheFrontCatalogueAnswersInFrench(): void
    {
        self::assertSame(
            'Me le rappeler',
            $this->translate('Remind me', AbandonedCartReminder::DOMAIN_NAME),
        );
    }

    public function testTheBackOfficeCatalogueAnswersInFrench(): void
    {
        self::assertSame('Panier', $this->translate('Cart', self::BACK_OFFICE_DOMAIN));
        self::assertSame('Client', $this->translate('Customer', self::BACK_OFFICE_DOMAIN));
    }

    public function testTheFrontAndEmailTemplatesGoThroughTheModuleFilter(): void
    {
        $templates = array_merge(
            glob(__DIR__.'/../../templates/email/default/*.twig') ?: [],
            glob(__DIR__.'/../../templates/frontOffice/default/*.twig') ?: [],
            glob(__DIR__.'/../../templates/theme-hook/*.twig') ?: [],
        );

        self::assertNotEmpty($templates);

        foreach ($templates as $template) {
            self::assertStringNotContainsString(
                "'".AbandonedCartReminder::DOMAIN_NAME."'",
                (string) file_get_contents($template),
                \sprintf(
                    '%s must translate through |abandoned_cart_reminder_trans: Twig\'s |trans does not '
                    .'carry module catalogues outside /admin.',
                    basename($template),
                ),
            );
        }
    }

    public function testTheBackOfficeTemplateAsksForTheDomainThatIsRegistered(): void
    {
        self::assertStringContainsString(
            "{% set domain = '".self::BACK_OFFICE_DOMAIN."' %}",
            (string) file_get_contents(
                __DIR__.'/../../templates/backOffice/default-twig/AbandonedCartReminder/module_configuration.html.twig'
            ),
        );
    }

    private function translate(string $id, string $domain): string
    {
        return Translator::getInstance()->trans($id, [], $domain, 'fr_FR');
    }
}
