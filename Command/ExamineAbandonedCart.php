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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Thelia\Command\ContainerAwareCommand;
use Thelia\Core\Event\DefaultActionEvent;

class ExamineAbandonedCart extends ContainerAwareCommand
{
    /**
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setName("examine-abandoned-carts")
            ->setDescription("Examine abandoned carts and send a reminder if needed.");
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initRequest();

        try {
            $this->getDispatcher()->dispatch(new DefaultActionEvent(), AbandonedCartEvent::EXAMINE_CARTS_EVENT);
        } catch (\Exception $ex) {
            $output->writeln(
                "<error>".$ex->getMessage()."</error>"
            );
            $output->writeln(
                "<error>".$ex->getTraceAsString()."</error>"
            );

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
