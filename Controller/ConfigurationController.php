<?php
/*************************************************************************************/
/*                                                                                   */
/*      Thelia 2 PortLocalAvecFranco payment module                                               */
/*                                                                                   */
/*      Copyright (c) CQFDev                                                         */
/*      email : thelia@cqfdev.fr                                                     */
/*      web : http://www.cqfdev.fr                                                   */
/*                                                                                   */
/*************************************************************************************/

namespace AbandonedCartReminder\Controller;

use AbandonedCartReminder\AbandonedCartReminder;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Form\Exception\FormValidationException;
use Thelia\Tools\URL;

class ConfigurationController extends BaseAdminController
{
    public function configure()
    {
        if (null !== $response = $this->checkAuth(AdminResources::MODULE, 'AbandonedCart', AccessManager::UPDATE)) {
            return $response;
        }

        $configurationForm = $this->createForm('AbandonedCart.configuration.form');

        try {
            $form = $this->validateForm($configurationForm, "POST");

            // Get the form field values
            $data = $form->getData();

            foreach ($data as $name => $value) {
                if (is_array($value)) {
                    $value = implode(';', $value);
                }

                AbandonedCartReminder::setConfigValue($name, $value);
            }

            $this->adminLogAppend(
                "AbandonedCart.configuration.message",
                AccessManager::UPDATE,
                sprintf("AbandonedCart configuration updated")
            );

            if ($this->getRequest()->get('save_mode') == 'stay') {
                // If we have to stay on the same page, redisplay the configuration page/
                $url = '/admin/module/AbandonedCartReminder';
            } else {
                // If we have to close the page, go back to the module back-office page.
                $url = '/admin/modules';
            }

            return $this->generateRedirect(URL::getInstance()->absoluteUrl($url));
        } catch (FormValidationException $ex) {
            $error_msg = $this->createStandardFormValidationErrorMessage($ex);
        } catch (\Exception $ex) {
            $error_msg = $ex->getMessage();
        }

        $this->setupFormErrorContext(
            $this->getTranslator()->trans("AbandonedCart configuration", [], AbandonedCartReminder::DOMAIN_NAME),
            $error_msg,
            $configurationForm,
            $ex
        );

        return $this->generateRedirect(URL::getInstance()->absoluteUrl('/admin/module/AbandonedCartReminder'));
    }
}
