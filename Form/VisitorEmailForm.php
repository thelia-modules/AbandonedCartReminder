<?php

declare(strict_types=1);

namespace AbandonedCartReminder\Form;

use AbandonedCartReminder\AbandonedCartReminder;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Thelia\Form\BaseForm;

class VisitorEmailForm extends BaseForm
{
    protected function buildForm(): void
    {
        $this->formBuilder->add('email', EmailType::class, [
            'required' => true,
            'label' => $this->translator->trans('Your email address', [], AbandonedCartReminder::DOMAIN_NAME),
            'constraints' => [new NotBlank(), new Email()],
        ]);
    }
}
