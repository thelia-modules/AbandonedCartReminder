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

namespace AbandonedCartReminder\Controller;

use AbandonedCartReminder\AbandonedCartReminder;
use AbandonedCartReminder\Form\ConfigurationForm;
use Symfony\Component\Routing\Attribute\Route;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\HttpFoundation\Request;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Core\Translation\Translator;
use Thelia\Form\Exception\FormValidationException;
use Thelia\Tools\URL;

class ConfigurationController extends BaseAdminController
{
    #[Route('/admin/module/AbandonedCartReminder/configure', name: 'abandoned_cart_reminder_admin_configure', methods: ['POST'])]
    public function configure(Request $request)
    {
        if (null !== $response = $this->checkAuth(AdminResources::MODULE, 'AbandonedCartReminder', AccessManager::UPDATE)) {
            return $response;
        }

        $configurationForm = $this->createForm(ConfigurationForm::getName());

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
                "abandoned_cart_reminder.configuration.message",
                AccessManager::UPDATE,
                sprintf("AbandonedCart configuration updated")
            );

            if ($request->get('save_mode') === 'stay') {
                // If we have to stay on the same page, redisplay the configuration page/
                $url = '/admin/module/AbandonedCartReminder';
            } else {
                // If we have to close the page, go back to the module back-office page.
                $url = '/admin/modules';
            }

            return $this->generateRedirect(URL::getInstance()->absoluteUrl($url));
        } catch (FormValidationException $ex) {
            $errorMessage = $this->createStandardFormValidationErrorMessage($ex);
        } catch (\Exception $ex) {
            $errorMessage = $ex->getMessage();
        }

        $this->setupFormErrorContext(
            Translator::getInstance()->trans("AbandonedCart configuration", [], AbandonedCartReminder::DOMAIN_NAME),
            $errorMessage,
            $configurationForm,
            $ex
        );

        return $this->generateRedirect(URL::getInstance()->absoluteUrl('/admin/module/AbandonedCartReminder'));
    }
}
