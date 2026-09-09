<?php

declare(strict_types=1);

namespace AbandonedCartReminder\Tests\Integration\EventListeners;

use AbandonedCartReminder\Domain\Exception\RestrictedSessionException;
use AbandonedCartReminder\EventListeners\RestrictedSessionResponseListener;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Thelia\Core\HttpFoundation\Session\Session;
use Thelia\Test\IntegrationTestCase;

final class RestrictedSessionResponseListenerTest extends IntegrationTestCase
{
    private RestrictedSessionResponseListener $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->listener = new RestrictedSessionResponseListener();
    }

    public function testTheRefusalSendsTheVisitorBackWithAMessage(): void
    {
        $event = $this->exceptionEvent(new RestrictedSessionException('refused'));

        $this->listener->answerTheRefusal($event);

        $response = $event->getResponse();

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('https://shop.test/account', $response->getTargetUrl());
        self::assertNotSame([], $event->getRequest()->getSession()->getFlashBag()->get('error'));
    }

    public function testARefusalWrappedInAnotherExceptionIsStillRecognised(): void
    {
        $event = $this->exceptionEvent(new \RuntimeException('wrapped', 0, new RestrictedSessionException('refused')));

        $this->listener->answerTheRefusal($event);

        self::assertInstanceOf(RedirectResponse::class, $event->getResponse());
    }

    public function testAnyOtherFailureIsLeftAlone(): void
    {
        $event = $this->exceptionEvent(new \RuntimeException('something else'));

        $this->listener->answerTheRefusal($event);

        self::assertNull($event->getResponse());
    }

    private function exceptionEvent(\Throwable $throwable): ExceptionEvent
    {
        $request = Request::create('https://shop.test/account/password');
        $request->headers->set('referer', 'https://shop.test/account');
        $request->setSession(new Session(new MockArraySessionStorage()));

        return new ExceptionEvent(
            $this->getService(KernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $throwable,
        );
    }
}
