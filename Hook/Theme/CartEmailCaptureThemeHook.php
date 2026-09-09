<?php

declare(strict_types=1);

namespace AbandonedCartReminder\Hook\Theme;

use AbandonedCartReminder\Form\VisitorEmailForm;
use Thelia\Core\Form\TheliaFormFactory;
use Thelia\Core\Hook\Theme\ThemeHookInterface;
use Thelia\Core\Security\SecurityContext;
use Twig\Environment;

final readonly class CartEmailCaptureThemeHook implements ThemeHookInterface
{
    private const HOOK_NAME = 'cart.bottom';

    public function __construct(
        private Environment $twig,
        private TheliaFormFactory $formFactory,
        private SecurityContext $securityContext,
    ) {
    }

    public function supports(string $hookName): bool
    {
        return self::HOOK_NAME === $hookName;
    }

    public function render(string $hookName, array $parameters): string
    {
        if (!$this->supports($hookName) || $this->securityContext->hasCustomerUser()) {
            return '';
        }

        return $this->twig->render('@AbandonedCartReminderModule/theme-hook/cart-email-capture.html.twig', [
            'form' => $this->formFactory->createForm(VisitorEmailForm::getName())->createView()->getView(),
        ]);
    }
}
