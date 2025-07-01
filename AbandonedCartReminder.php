<?php
/*************************************************************************************/
/*      This file is part of the Thelia package.                                     */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*      email : dev@thelia.net                                                       */
/*      web : http://www.thelia.net                                                  */
/*                                                                                   */
/*      For the full copyright and license information, please view the LICENSE.txt  */
/*      file that was distributed with this source code.                             */
/*************************************************************************************/

namespace AbandonedCartReminder;

use AbandonedCartReminder\Model\AbandonedCartQuery;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\PropelException;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServicesConfigurator;
use Thelia\Core\Translation\Translator;
use Thelia\Install\Database;
use Thelia\Model\MessageQuery;
use Thelia\Model\Lang;
use Thelia\Model\LangQuery;
use Thelia\Model\Message;
use Thelia\Module\BaseModule;

class AbandonedCartReminder extends BaseModule
{
    /** @var string */
    public const DOMAIN_NAME = 'abandonedcartreminder';
    public const REMINDER_TIME_1 = 'first_reminder_in_minutes';
    public const REMINDER_TIME_2 = 'second_reminder_in_minutes';
    public const PROMO_CODE_REMINDER = 'promotional_code_reminder';
    public const REMINDER_MESSAGE_1 = 'abandoned-cart-reminder-message-1';
    public const REMINDER_MESSAGE_2 = 'abandoned-cart-reminder-message-2';

    /**
     * @param ConnectionInterface|null $con
     * @return void
     * @throws PropelException
     */
    public function postActivation(ConnectionInterface $con = null): void
    {
        if (null === self::getConfigValue('is-initialized')) {
            $database = new Database($con);
            $database->insertSql(null, [__DIR__ . "/Config/TheliaMain.sql"]);

            self::setConfigValue('is-initialized', 1);
        }

        if (null === self::getConfigValue(self::REMINDER_TIME_1)) {
            self::setConfigValue(self::REMINDER_TIME_1, 2);
        }

        if (null === self::getConfigValue(self::REMINDER_TIME_2)) {
            self::setConfigValue(self::REMINDER_TIME_2, 10);
        }

        if (null === MessageQuery::create()->findOneByName(self::REMINDER_MESSAGE_1)) {

            $message = new Message();
            $message
                ->setName(self::REMINDER_MESSAGE_1)
                ->setHtmlLayoutFileName('')
                ->setHtmlTemplateFileName('reminder-mail-1.html')
                ->setTextLayoutFileName('')
                ->setTextTemplateFileName('reminder-mail-1.txt');

            $languages = LangQuery::create()->find();

            foreach ($languages as $language) {
                /** @var Lang $language */
                $locale = $language->getLocale();

                $message->setLocale($locale);

                $message->setTitle(
                    Translator::getInstance()->trans("Alors comme ça, vous êtes du genre à oublier votre panier ?", [], self::DOMAIN_NAME, $locale)
                );

                $message->setSubject(
                    Translator::getInstance()->trans("Alors comme ça, vous êtes du genre à oublier votre panier ?", [], self::DOMAIN_NAME, $locale)
                );
            }

            $message->save();
        }

        if (null === MessageQuery::create()->findOneByName(self::REMINDER_MESSAGE_2)) {
            $message = new Message();
            $message
                ->setName(self::REMINDER_MESSAGE_2)
                ->setHtmlLayoutFileName('')
                ->setHtmlTemplateFileName('reminder-mail-2.html')
                ->setTextLayoutFileName('')
                ->setTextTemplateFileName('reminder-mail-2.txt');

            $languages = LangQuery::create()->find();

            foreach ($languages as $language) {
                /** @var Lang $language */
                $locale = $language->getLocale();

                $message->setLocale($locale);

                $message->setTitle(
                    Translator::getInstance()->trans('Your cart is still waiting for you !', [], self::DOMAIN_NAME, $locale)
                );

                $message->setSubject(
                    Translator::getInstance()->trans('Your cart is still waiting for you !', [], self::DOMAIN_NAME, $locale)
                );
            }

            $message->save();
        }
    }

    /**
     * @param ServicesConfigurator $servicesConfigurator
     * @return void
     */
    public static function configureServices(ServicesConfigurator $servicesConfigurator): void
    {
        $servicesConfigurator->load(self::getModuleCode().'\\', __DIR__)
            ->exclude([THELIA_MODULE_DIR . ucfirst(self::getModuleCode()). "/I18n/*"])
            ->autowire(true)
            ->autoconfigure(true);
    }
}
