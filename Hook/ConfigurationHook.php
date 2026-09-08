<?php

declare(strict_types=1);

namespace AbandonedCartReminder\Hook;

use AbandonedCartReminder\AbandonedCartReminder;
use AbandonedCartReminder\Domain\Service\ReminderPrerequisites;
use AbandonedCartReminder\Form\ConfigurationForm;
use AbandonedCartReminder\Service\AbandonedCartListFilters;
use AbandonedCartReminder\Service\AbandonedCartListPresenter;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Thelia\Core\Event\Hook\HookRenderEvent;
use Thelia\Core\Form\TheliaFormFactory;
use Thelia\Core\Hook\BaseHook;
use Thelia\Core\Template\Parser\ParserResolver;

class ConfigurationHook extends BaseHook
{
    public function __construct(
        private readonly TheliaFormFactory $formFactory,
        private readonly AbandonedCartListPresenter $listPresenter,
        private readonly ReminderPrerequisites $prerequisites,
        ?EventDispatcherInterface $dispatcher = null,
        ?ParserResolver $parserResolver = null,
    ) {
        parent::__construct($dispatcher, $parserResolver);
    }

    public static function getSubscribedHooks(): array
    {
        return [
            'module.configuration' => [
                ['type' => 'back', 'method' => 'onModuleConfiguration'],
            ],
        ];
    }

    private function configurationForm(): \Thelia\Form\BaseForm
    {
        return $this->formFactory->createForm(ConfigurationForm::getName(), data: [
            AbandonedCartReminder::FIRST_REMINDER_DELAY_IN_HOURS => AbandonedCartReminder::getConfigValue(AbandonedCartReminder::FIRST_REMINDER_DELAY_IN_HOURS),
            AbandonedCartReminder::SECOND_REMINDER_DELAY_IN_HOURS => AbandonedCartReminder::getConfigValue(AbandonedCartReminder::SECOND_REMINDER_DELAY_IN_HOURS),
            AbandonedCartReminder::THIRD_REMINDER_DELAY_IN_HOURS => AbandonedCartReminder::getConfigValue(AbandonedCartReminder::THIRD_REMINDER_DELAY_IN_HOURS),
            AbandonedCartReminder::RECOVERY_LINK_LIFETIME_IN_SECONDS => AbandonedCartReminder::getConfigValue(AbandonedCartReminder::RECOVERY_LINK_LIFETIME_IN_SECONDS),
            AbandonedCartReminder::REMINDERS_PER_RUN => AbandonedCartReminder::getConfigValue(AbandonedCartReminder::REMINDERS_PER_RUN),
        ]);
    }

    public function onModuleConfiguration(HookRenderEvent $event): void
    {
        $filters = AbandonedCartListFilters::fromRequest($this->getRequest());
        $list = $this->listPresenter->page($filters->page, $filters);

        $event->add($this->render('AbandonedCartReminder/module_configuration.html.twig', [
            'form' => $this->configurationForm()->createView()->getView(),
            'module_code' => AbandonedCartReminder::getModuleCode(),
            'warnings' => $this->prerequisites->warnings(),
            'blocking' => $this->prerequisites->blockSending(),
            'rows' => $list['rows'],
            'current_page' => $list['currentPage'],
            'pages' => $list['lastPage'],
            'recovered_total' => $list['recoveredTotal'],
            'followed' => $list['followed'],
            'matching' => $list['matching'],
            'filters' => $filters,
            'sortable_fields' => array_keys(AbandonedCartListFilters::SORTABLE_FIELDS),
        ]));
    }
}
