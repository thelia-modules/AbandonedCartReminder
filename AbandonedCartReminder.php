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

use Propel\Runtime\Connection\ConnectionInterface;
use Thelia\Core\Translation\Translator;
use Thelia\Install\Database;
use Thelia\Model\Base\MessageQuery;
use Thelia\Model\Lang;
use Thelia\Model\LangQuery;
use Thelia\Model\Message;
use Thelia\Module\BaseModule;

class AbandonedCartReminder extends BaseModule
{
    /** @var string */
    const DOMAIN_NAME = 'abandonedcartreminder';

    // TODO: Find a way to make those consts more flexible. Hint: an array ?
    const REMINDER_TIME_1 = 'first_reminder_in_minutes';
    const REMINDER_TIME_2 = 'second_reminder_in_minutes';
    const PROMO_CODE_REMINDER = 'promotional_code_reminder';

    const REMINDER_MESSAGE_1 = 'abandoned-cart-reminder-message-1';
    const REMINDER_MESSAGE_2 = 'abandoned-cart-reminder-message-2';

    public function postActivation(ConnectionInterface $con = null)
    {
        $database = new Database($con);
        $database->insertSql(null, [__DIR__ . '/Config/thelia.sql']);

        self::setConfigValue(self::REMINDER_TIME_1, 2);
        self::setConfigValue(self::REMINDER_TIME_2, 10);
        self::setConfigValue(self::PROMO_CODE_REMINDER, null);

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
}
