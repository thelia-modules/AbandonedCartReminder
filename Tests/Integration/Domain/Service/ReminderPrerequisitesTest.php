<?php

declare(strict_types=1);

namespace AbandonedCartReminder\Tests\Integration\Domain\Service;

use AbandonedCartReminder\AbandonedCartReminder;
use AbandonedCartReminder\Domain\Service\ReminderConfiguration;
use AbandonedCartReminder\Domain\Service\ReminderPrerequisites;
use Thelia\Model\ConfigQuery;
use Thelia\Model\Lang;
use Thelia\Model\LangQuery;
use Thelia\Test\IntegrationTestCase;

final class ReminderPrerequisitesTest extends IntegrationTestCase
{
    private ReminderPrerequisites $prerequisites;
    private ReminderConfiguration $configuration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configuration = new ReminderConfiguration();
        $this->prerequisites = new ReminderPrerequisites($this->configuration);

        ConfigQuery::write('one_domain_foreach_lang', '0');
        ConfigQuery::write('url_site', 'https://shop.test');
        ConfigQuery::write('purification_cart_no_order_days', '60');

        AbandonedCartReminder::setConfigValue(AbandonedCartReminder::FIRST_REMINDER_DELAY_IN_HOURS, '4');
        AbandonedCartReminder::setConfigValue(AbandonedCartReminder::SECOND_REMINDER_DELAY_IN_HOURS, '24');
        AbandonedCartReminder::setConfigValue(AbandonedCartReminder::THIRD_REMINDER_DELAY_IN_HOURS, '72');
    }

    public function testAShopThatIsSetUpHasNothingToSay(): void
    {
        self::assertSame([], $this->prerequisites->warnings());
    }

    public function testAShopWithoutAnUrlIsRefusedTheSending(): void
    {
        ConfigQuery::write('url_site', '');

        $reason = $this->prerequisites->blockSending();

        self::assertIsString($reason);
        self::assertStringContainsString('http://localhost', $reason);
        self::assertStringContainsString('url_site', $reason);
    }

    public function testAShopWithAnUrlIsNotBlocked(): void
    {
        self::assertNull($this->prerequisites->blockSending());
    }

    public function testTheUrlOfTheDefaultLanguageCountsWhenEachLanguageHasItsDomain(): void
    {
        ConfigQuery::write('one_domain_foreach_lang', '1');
        ConfigQuery::write('url_site', '');

        $defaultLanguage = LangQuery::create()->findOneByByDefault(1);

        self::assertInstanceOf(Lang::class, $defaultLanguage);

        $defaultLanguage->setUrl('https://shop.test')->save();

        self::assertNull($this->prerequisites->blockSending());
    }

    public function testAReminderChainOutlivingThePurgeIsWarnedAbout(): void
    {
        AbandonedCartReminder::setConfigValue(AbandonedCartReminder::THIRD_REMINDER_DELAY_IN_HOURS, '2000');

        self::assertStringContainsString('purged', $this->firstWarning());
    }

    public function testAFirstReminderWithoutADelayIsWarnedAbout(): void
    {
        AbandonedCartReminder::setConfigValue(AbandonedCartReminder::FIRST_REMINDER_DELAY_IN_HOURS, '');

        self::assertStringContainsString('no delay', $this->firstWarning());
    }

    public function testADelayOfZeroIsNotADelayLeftUnset(): void
    {
        AbandonedCartReminder::setConfigValue(AbandonedCartReminder::FIRST_REMINDER_DELAY_IN_HOURS, '0');

        self::assertSame(0, $this->configuration->delayInHours(1));
        self::assertTrue($this->configuration->isEnabled(1));
        self::assertSame([], $this->prerequisites->warnings());

        AbandonedCartReminder::setConfigValue(AbandonedCartReminder::FIRST_REMINDER_DELAY_IN_HOURS, '');

        self::assertNull($this->configuration->delayInHours(1));
        self::assertFalse($this->configuration->isEnabled(1));
    }

    public function testADisabledReminderStopsTheChainThere(): void
    {
        AbandonedCartReminder::setConfigValue(AbandonedCartReminder::SECOND_REMINDER_DELAY_IN_HOURS, '');

        self::assertSame(4, $this->configuration->longestReminderChainInHours());
    }

    private function firstWarning(): string
    {
        $warnings = $this->prerequisites->warnings();

        self::assertNotSame([], $warnings);

        return $warnings[0];
    }
}
