<?php

declare(strict_types=1);

namespace AbandonedCartReminder\EventListeners;

use AbandonedCartReminder\AbandonedCartReminder;
use AbandonedCartReminder\Domain\Exception\RestrictedSessionException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Thelia\Core\Translation\Translator;
use Thelia\Tools\URL;

final readonly class RestrictedSessionResponseListener implements EventSubscriberInterface
{
    public function answerTheRefusal(ExceptionEvent $event): void
    {
        if (!$this->wasRefusedByTheGuard($event->getThrowable())) {
            return;
        }

        $message = Translator::getInstance()->trans(
            'For your security, this cannot be done from a session opened by a cart reminder link. Please sign in with your password first.',
            [],
            AbandonedCartReminder::DOMAIN_NAME,
        );

        $session = $event->getRequest()->getSession();

        if ($session instanceof FlashBagAwareSessionInterface) {
            $session->getFlashBag()->add('error', $message);
        }

        $event->setResponse(new RedirectResponse(
            $event->getRequest()->headers->get('referer') ?? URL::getInstance()->getBaseUrl()
        ));
    }

    private function wasRefusedByTheGuard(\Throwable $throwable): bool
    {
        while ($throwable instanceof \Throwable) {
            if ($throwable instanceof RestrictedSessionException) {
                return true;
            }

            $throwable = $throwable->getPrevious();
        }

        return false;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['answerTheRefusal', 128],
        ];
    }
}
