<?php

declare(strict_types=1);

namespace AbandonedCartReminder\Controller\Admin;

use AbandonedCartReminder\AbandonedCartReminder;
use AbandonedCartReminder\Form\ConfigurationForm;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Tools\URL;

class ConfigurationController extends BaseAdminController
{
    #[Route('/admin/module/AbandonedCartReminder/save', name: 'abandoned_cart_reminder.configuration.save', methods: ['POST'])]
    public function save(): Response
    {
        if (null !== $response = $this->checkAuth(AdminResources::MODULE, [], AccessManager::UPDATE)) {
            return $response;
        }

        $form = $this->createForm(ConfigurationForm::getName());

        try {
            $data = $this->validateForm($form)->getData();

            foreach ($data as $key => $value) {
                AbandonedCartReminder::setConfigValue($key, null === $value ? '' : (string) $value);
            }
        } catch (\Throwable $throwable) {
            $this->setupFormErrorContext(
                $this->getTranslator()->trans('Abandoned cart reminder configuration', [], AbandonedCartReminder::DOMAIN_NAME),
                $throwable->getMessage(),
                $form,
                $throwable instanceof \Exception ? $throwable : null,
            );

            return $this->generateRedirect(
                URL::getInstance()->absoluteUrl('/admin/module/'.AbandonedCartReminder::getModuleCode())
            );
        }

        return new RedirectResponse(
            URL::getInstance()->absoluteUrl('/admin/module/'.AbandonedCartReminder::getModuleCode())
        );
    }
}
