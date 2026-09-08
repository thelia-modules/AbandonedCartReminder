<?php

declare(strict_types=1);

namespace AbandonedCartReminder;

use Propel\Runtime\Connection\ConnectionInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServicesConfigurator;
use Thelia\Core\Install\Database;
use Thelia\Core\Translation\Translator;
use Thelia\Model\LangQuery;
use Thelia\Model\Message;
use Thelia\Model\MessageQuery;
use Thelia\Module\BaseModule;

class AbandonedCartReminder extends BaseModule
{
    public const DOMAIN_NAME = 'abandonedcartreminder';

    public const FIRST_REMINDER_DELAY_IN_HOURS = 'first_reminder_delay_in_hours';
    public const SECOND_REMINDER_DELAY_IN_HOURS = 'second_reminder_delay_in_hours';
    public const THIRD_REMINDER_DELAY_IN_HOURS = 'third_reminder_delay_in_hours';

    public const RECOVERY_LINK_LIFETIME_IN_SECONDS = 'recovery_link_lifetime_in_seconds';
    public const REMINDERS_PER_RUN = 'reminders_per_run';

    public const FIRST_REMINDER_MESSAGE = 'abandoned-cart-reminder-message-1';
    public const SECOND_REMINDER_MESSAGE = 'abandoned-cart-reminder-message-2';
    public const THIRD_REMINDER_MESSAGE = 'abandoned-cart-reminder-message-3';

    public const REMINDER_MESSAGES = [
        1 => self::FIRST_REMINDER_MESSAGE,
        2 => self::SECOND_REMINDER_MESSAGE,
        3 => self::THIRD_REMINDER_MESSAGE,
    ];

    private const REMINDER_SUBJECTS = [
        1 => 'Your cart is still waiting for you',
        2 => 'Your cart is still available',
        3 => 'Last chance to complete your order',
    ];

    private const CONFIGURATION_DEFAULTS = [
        self::FIRST_REMINDER_DELAY_IN_HOURS => '4',
        self::SECOND_REMINDER_DELAY_IN_HOURS => '24',
        self::THIRD_REMINDER_DELAY_IN_HOURS => '72',
        self::RECOVERY_LINK_LIFETIME_IN_SECONDS => '604800',
        self::REMINDERS_PER_RUN => '200',
    ];

    public function preActivation(?ConnectionInterface $con = null): bool
    {
        if (!$this->getConfigValue('is_initialized', false)) {
            (new Database($con))->insertSql(null, [__DIR__.'/Config/TheliaMain.sql']);

            $this->setConfigValue('is_initialized', true);
        }

        return true;
    }

    public function postActivation(?ConnectionInterface $con = null): void
    {
        foreach (self::CONFIGURATION_DEFAULTS as $key => $value) {
            if (null === self::getConfigValue($key)) {
                self::setConfigValue($key, $value);
            }
        }

        foreach (self::REMINDER_MESSAGES as $reminderNumber => $messageCode) {
            $this->seedMessage($messageCode, $reminderNumber);
        }
    }

    private function seedMessage(string $messageCode, int $reminderNumber): void
    {
        if (null !== MessageQuery::create()->findOneByName($messageCode)) {
            return;
        }

        $message = new Message();
        $message
            ->setName($messageCode)
            ->setHtmlLayoutFileName('')
            ->setHtmlTemplateFileName("reminder-mail-$reminderNumber.html.twig")
            ->setTextLayoutFileName('')
            ->setTextTemplateFileName("reminder-mail-$reminderNumber.txt.twig");

        $subject = self::REMINDER_SUBJECTS[$reminderNumber];

        foreach (LangQuery::create()->find() as $language) {
            $locale = $language->getLocale();

            $message->setLocale($locale);
            $message->setTitle(Translator::getInstance()->trans($subject, [], self::DOMAIN_NAME, $locale));
            $message->setSubject(Translator::getInstance()->trans($subject, [], self::DOMAIN_NAME, $locale));
        }

        $message->save();
    }

    public static function configureServices(ServicesConfigurator $servicesConfigurator): void
    {
        $servicesConfigurator->load(self::getModuleCode().'\\', __DIR__)
            ->exclude([
                __DIR__.'/I18n/*',
                __DIR__.'/Config/**/*.php',
                __DIR__.'/Model/*',
                __DIR__.'/Domain/Exception/*',
                __DIR__.'/Domain/Report/*',
                __DIR__.'/Tests/*',
                __DIR__.'/AbandonedCartReminder.php',
            ])
            ->autowire(true)
            ->autoconfigure(true);
    }
}
