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

namespace AbandonedCartReminder\Form;

use AbandonedCartReminder\AbandonedCartReminder;
use Propel\Runtime\ActiveQuery\Criteria;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;
use Thelia\Form\BaseForm;
use Thelia\Model\Coupon;
use Thelia\Model\CouponQuery;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

class ConfigurationForm extends BaseForm
{
    protected function buildForm()
    {
        $locale = $this->getRequest()->getSession()->getLang()->getLocale();

        $promoListe = [ '' => $this->translator->trans("Ne pas offrir de code promo", [], AbandonedCartReminder::DOMAIN_NAME) ];

        $coupons = CouponQuery::create()
            ->orderByCode()
            ->filterByExpirationDate(new \DateTime(), Criteria::GREATER_THAN)
            ->find();

        /** @var Coupon $coupon */
        foreach ($coupons as $coupon) {
            $promoListe[$coupon->getCode()] = $coupon->getCode() . ': ' . $coupon->setLocale($locale)->getTitle();
        }

        $this->formBuilder
            ->add(
                AbandonedCartReminder::VAR_DELAI_RAPPEL_1,
                NumberType::class,
                [
                    "required" => true,
                    "constraints" => [
                        new NotBlank(),
                        new GreaterThanOrEqual(array('value' => 0))
                    ],
                    "label" => $this->translator->trans("Délai en minutes du premier rappel", [], AbandonedCartReminder::DOMAIN_NAME),
                    'label_attr'  => [
                        'help' => $this->translator->trans(
                            "Le nombre de minutes après la création du panier après lequel envoyer le premier rappel.",
                            [],
                            AbandonedCartReminder::DOMAIN_NAME
                        ),
                    ],
                ]
            )
            ->add(
                AbandonedCartReminder::VAR_DELAI_RAPPEL_2,
                NumberType::class,
                [
                    "required" => true,
                    "constraints" => [
                        new NotBlank(),
                        new GreaterThanOrEqual(array('value' => 0))
                    ],
                    "label" => $this->translator->trans("Délai en minutes du second rappel", [], AbandonedCartReminder::DOMAIN_NAME),
                    'label_attr'  => [
                        'help' => $this->translator->trans(
                            "Le nombre de minutes après la création du panier après lequel envoyer le second rappel.",
                            [],
                            AbandonedCartReminder::DOMAIN_NAME
                        ),
                    ],
                ]
            )
            ->add(
                AbandonedCartReminder::VAR_CODE_PROMO_RAPPEL_2,
                "choice",
                [
                    'required' => false,
                    "choices" => $promoListe,
                    "label" => $this->translator->trans("Code promotion a proposer dans le mail du second rappel", [], AbandonedCartReminder::DOMAIN_NAME),
                    'label_attr'  => [
                        'help' => $this->translator->trans(
                            "Indiquez si vous le souhaitez un des codes promo existants.",
                            [],
                            AbandonedCartReminder::DOMAIN_NAME
                        ),
                    ],
                ]
            )
        ;
    }
}
