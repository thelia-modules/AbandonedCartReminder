<?php

declare(strict_types=1);

namespace AbandonedCartReminder\Form;

use AbandonedCartReminder\AbandonedCartReminder;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Thelia\Form\BaseForm;

class ConfigurationForm extends BaseForm
{
    protected function buildForm(): void
    {
        $this->formBuilder
            ->add(AbandonedCartReminder::FIRST_REMINDER_DELAY_IN_HOURS, IntegerType::class, [
                'required' => false,
                'label' => $this->translator->trans('Hours of inactivity before the first reminder', [], AbandonedCartReminder::DOMAIN_NAME),
                'constraints' => [new GreaterThanOrEqual(value: 0)],
            ])
            ->add(AbandonedCartReminder::SECOND_REMINDER_DELAY_IN_HOURS, IntegerType::class, [
                'required' => false,
                'label' => $this->translator->trans('Hours after the first reminder before the second', [], AbandonedCartReminder::DOMAIN_NAME),
                'constraints' => [new GreaterThanOrEqual(value: 0)],
            ])
            ->add(AbandonedCartReminder::THIRD_REMINDER_DELAY_IN_HOURS, IntegerType::class, [
                'required' => false,
                'label' => $this->translator->trans('Hours after the second reminder before the third', [], AbandonedCartReminder::DOMAIN_NAME),
                'constraints' => [new GreaterThanOrEqual(value: 0)],
            ])
            ->add(AbandonedCartReminder::RECOVERY_LINK_LIFETIME_IN_SECONDS, IntegerType::class, [
                'required' => false,
                'label' => $this->translator->trans('How long a recovery link stays valid, in seconds', [], AbandonedCartReminder::DOMAIN_NAME),
                'constraints' => [new GreaterThanOrEqual(value: 60)],
            ])
            ->add(AbandonedCartReminder::REMINDERS_PER_RUN, IntegerType::class, [
                'required' => false,
                'label' => $this->translator->trans('How many reminders one run may send at most', [], AbandonedCartReminder::DOMAIN_NAME),
                'constraints' => [new GreaterThanOrEqual(value: 1)],
            ]);
    }
}
