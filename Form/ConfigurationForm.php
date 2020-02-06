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

        $promoCodeList = [ '' => $this->translator->trans("Do not offer any promotional code", [], AbandonedCartReminder::DOMAIN_NAME) ];

        $coupons = CouponQuery::create()
            ->orderByCode()
            ->filterByExpirationDate(new \DateTime(), Criteria::GREATER_THAN)
            ->find();

        /** @var Coupon $coupon */
        foreach ($coupons as $coupon) {
            $promoCodeList[$coupon->getCode()] = $coupon->getCode() . ': ' . $coupon->setLocale($locale)->getTitle();
        }

        $this->formBuilder
            ->add(
                AbandonedCartReminder::REMINDER_TIME_1,
                NumberType::class,
                [
                    "required" => true,
                    "constraints" => [
                        new NotBlank(),
                        new GreaterThanOrEqual(array('value' => 0))
                    ],
                    "label" => $this->translator->trans('Time in minute before sending the first reminder', [], AbandonedCartReminder::DOMAIN_NAME),
                    'label_attr'  => [
                        'help' => $this->translator->trans(
                            'Number of minutes to wait when the cart becomes inactive before sending the first email.',
                            [],
                            AbandonedCartReminder::DOMAIN_NAME
                        ),
                    ],
                ]
            )
            ->add(
                AbandonedCartReminder::REMINDER_TIME_2,
                NumberType::class,
                [
                    "required" => true,
                    "constraints" => [
                        new NotBlank(),
                        new GreaterThanOrEqual(array('value' => 0))
                    ],
                    "label" => $this->translator->trans('Time in minute before sending the second reminder', [], AbandonedCartReminder::DOMAIN_NAME),
                    'label_attr'  => [
                        'help' => $this->translator->trans(
                            'Number of minutes to wait when the cart becomes inactive before sending the second email.',
                            [],
                            AbandonedCartReminder::DOMAIN_NAME
                        ),
                    ],
                ]
            )
            ->add(
                AbandonedCartReminder::PROMO_CODE_REMINDER,
                "choice",
                [
                    'required' => false,
                    "choices" => $promoCodeList,
                    "label" => $this->translator->trans('Promotional code to offer while sending the second reminder', [], AbandonedCartReminder::DOMAIN_NAME),
                    'label_attr'  => [
                        'help' => $this->translator->trans(
                            'You can specify if you want an existing promotional code.',
                            [],
                            AbandonedCartReminder::DOMAIN_NAME
                        ),
                    ],
                ]
            )
        ;
    }
}
