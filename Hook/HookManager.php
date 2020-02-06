<?php
/*************************************************************************************/
/*                                                                                   */
/*      This file is not free software                                               */
/*                                                                                   */
/*      Copyright (c) Franck Allimant, CQFDev                                        */
/*      email : thelia@cqfdev.fr                                                     */
/*      web : http://www.cqfdev.fr                                                   */
/*                                                                                   */
/*************************************************************************************/

/**
 * Created by Franck Allimant, CQFDev <franck@cqfdev.fr>
 * Date: 05/03/2016 18:11
 */

namespace AbandonedCartReminder\Hook;

use AbandonedCartReminder\AbandonedCartReminder;
use Thelia\Core\Event\Hook\HookRenderEvent;
use Thelia\Core\Hook\BaseHook;
use Thelia\Model\ModuleConfig;
use Thelia\Model\ModuleConfigQuery;

class HookManager extends BaseHook
{
    /**
     * @param HookRenderEvent $event
     */
    public function onModuleConfigure(HookRenderEvent $event)
    {
        $vars = [];

        // Passer toutes les variables de configuration au template
        if (null !== $params = ModuleConfigQuery::create()->findByModuleId(AbandonedCartReminder::getModuleId())) {
            /** @var ModuleConfig $param */
            foreach ($params as $param) {
                $vars[ $param->getName() ] = $param->getValue();
            }
        }

        $event->add(
            $this->render('AbandonedCart/module-configuration.html', $vars)
        );
    }
}
