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

namespace AbandonedCartReminder\Command;

use AbandonedCartReminder\Events\AbandonedCartEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Thelia\Command\ContainerAwareCommand;
use Thelia\Core\Event\DefaultActionEvent;
use Thelia\Core\HttpFoundation\Request;
use Thelia\Core\HttpFoundation\Session\Session;

class ExamineAbandonedCart extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName("examine-abandoned-carts")
            ->setDescription("Examine les paniers abandonnes en envoie les mails de rappel si nécessaire.")
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->initRequest();

        try {
            $this->getDispatcher()->dispatch(AbandonedCartEvent::EXAMINE_CARTS_EVENT, new DefaultActionEvent());

        } catch (\Exception $ex) {
            $output->writeln(
                "<error>".$ex->getMessage()."</error>"
            );
            $output->writeln(
                "<error>".$ex->getTraceAsString()."</error>"
            );
        }
    }
}
