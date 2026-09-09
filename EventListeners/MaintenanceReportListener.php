<?php

declare(strict_types=1);

namespace AbandonedCartReminder\EventListeners;

use AbandonedCartReminder\Model\AbandonedCart;
use AbandonedCartReminder\Model\AbandonedCartQuery;
use Propel\Runtime\ActiveQuery\Criteria;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Thelia\Core\Event\Maintenance\MaintenancePurgeEvent;
use Thelia\Core\Event\TheliaEvents;

final readonly class MaintenanceReportListener implements EventSubscriberInterface
{
    public function reportTheFollowedCarts(MaintenancePurgeEvent $event): void
    {
        $followed = AbandonedCartQuery::create()
            ->filterByStatus([AbandonedCart::STATUS_PENDING, AbandonedCart::STATUS_REMINDED], Criteria::IN)
            ->count();

        $recovered = AbandonedCartQuery::create()
            ->filterByStatus(AbandonedCart::STATUS_RECOVERED)
            ->count();

        $event->addResult(\sprintf(
            'Abandoned cart reminder: %d cart(s) still followed, %d recovered. Rows follow their cart, so this purge already took the ones whose cart it deleted.',
            $followed,
            $recovered,
        ));
    }

    public static function getSubscribedEvents(): array
    {
        return [
            TheliaEvents::MAINTENANCE_PURGE => ['reportTheFollowedCarts', 0],
        ];
    }
}
