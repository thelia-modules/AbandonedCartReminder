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

    const VAR_DELAI_RAPPEL_1 = 'delai_rappel_1_en_minutes';
    const VAR_DELAI_RAPPEL_2 = 'delai_rappel_2_en_minutes';
    const VAR_CODE_PROMO_RAPPEL_2 = 'code_promo_rappel_2';

    const MESSAGE_RAPPEL_1 = 'panier-abandonnes-message-rappel-1';
    const MESSAGE_RAPPEL_2 = 'panier-abandonnes-message-rappel-2';

    public function postActivation(ConnectionInterface $con = null)
    {
        $database = new Database($con);
        $database->insertSql(null, [__DIR__ . '/Config/thelia.sql']);

        self::setConfigValue(self::VAR_DELAI_RAPPEL_1, 2);
        self::setConfigValue(self::VAR_DELAI_RAPPEL_2, 10);
        self::setConfigValue(self::VAR_CODE_PROMO_RAPPEL_2, null);

        if (null === MessageQuery::create()->findOneByName(self::MESSAGE_RAPPEL_1)) {

            $message = new Message();
            $message
                ->setName(self::MESSAGE_RAPPEL_1)
                ->setHtmlLayoutFileName('')
                ->setHtmlTemplateFileName('mail-rappel-1.html')
                ->setTextLayoutFileName('')
                ->setTextTemplateFileName('mail-rappel-1.txt');

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

        if (null === MessageQuery::create()->findOneByName(self::MESSAGE_RAPPEL_2)) {
            $message = new Message();
            $message
                ->setName(self::MESSAGE_RAPPEL_2)
                ->setHtmlLayoutFileName('')
                ->setHtmlTemplateFileName('mail-rappel-2.html')
                ->setTextLayoutFileName('')
                ->setTextTemplateFileName('mail-rappel-2.txt');

            $languages = LangQuery::create()->find();

            foreach ($languages as $language) {
                /** @var Lang $language */
                $locale = $language->getLocale();

                $message->setLocale($locale);

                $message->setTitle(
                    Translator::getInstance()->trans('Votre panier vous attend toujours !', [], self::DOMAIN_NAME, $locale)
                );

                $message->setSubject(
                    Translator::getInstance()->trans('Votre panier vous attend toujours !', [], self::DOMAIN_NAME, $locale)
                );
            }

            $message->save();
        }
    }
}
