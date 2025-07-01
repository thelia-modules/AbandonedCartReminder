<?php

namespace AbandonedCartReminder\EventListeners;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelEvents;
use Thelia\Log\Tlog;

class KernelListener implements EventSubscriberInterface
{
    public function __construct(protected RequestStack $requestStack)
    {}

    /**
     * @return void
     */
    public function detectCustomerEmailFromRequest(): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $source = $request?->get('utm_source');

        if ($source === 'mail' && null !== $email = $request?->get('mail')) {
            $request?->getSession()->set('utm_source_email', $email);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [ 'detectCustomerEmailFromRequest', 0]
        ];
    }
}