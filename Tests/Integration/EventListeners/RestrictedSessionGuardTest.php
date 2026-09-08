<?php

declare(strict_types=1);

namespace AbandonedCartReminder\Tests\Integration\EventListeners;

use AbandonedCartReminder\Domain\Exception\RestrictedSessionException;
use AbandonedCartReminder\Domain\Service\RestrictedSession;
use AbandonedCartReminder\EventListeners\RestrictedSessionGuard;
use AbandonedCartReminder\EventListeners\RestrictedSessionReleaseListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Thelia\Core\Event\Customer\CustomerCreateOrUpdateEvent;
use Thelia\Core\Event\Customer\CustomerEvent;
use Thelia\Core\HttpFoundation\Session\Session;
use Thelia\Core\Security\SecurityContext;
use Thelia\Model\Customer;
use Thelia\Test\FixtureFactory;
use Thelia\Test\IntegrationTestCase;

final class RestrictedSessionGuardTest extends IntegrationTestCase
{
    private RestrictedSession $restrictedSession;
    private RestrictedSessionGuard $guard;
    private Session $session;
    private FixtureFactory $fixtures;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $request = Request::create('https://shop.test/');
        $this->session = new Session(new MockArraySessionStorage());
        $request->setSession($this->session);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $this->restrictedSession = new RestrictedSession($requestStack);
        $this->guard = new RestrictedSessionGuard($this->restrictedSession, new SecurityContext($requestStack));

        $this->fixtures = $this->createFixtureFactory();
        $this->customer = $this->fixtures->customer($this->fixtures->customerTitle());
    }

    public function testAPasswordChangeIsRefusedFromARestrictedSession(): void
    {
        $this->restrict($this->customer);

        $this->expectException(RestrictedSessionException::class);

        $this->guard->refuseAPasswordChange($this->passwordChange('a-new-password'));
    }

    public function testAnAccountDeletionIsRefusedFromARestrictedSession(): void
    {
        $this->restrict($this->customer);

        $this->expectException(RestrictedSessionException::class);

        $this->guard->refuseAnAccountDeletion(new CustomerEvent($this->customer));
    }

    public function testAProfileChangeWithoutAPasswordIsLeftAlone(): void
    {
        $this->restrict($this->customer);

        $this->guard->refuseAPasswordChange($this->passwordChange(null));

        self::assertTrue($this->restrictedSession->isRestricted());
    }

    public function testAnOrdinarySessionChangesItsPasswordFreely(): void
    {
        $this->guard->refuseAPasswordChange($this->passwordChange('a-new-password'));

        self::assertFalse($this->restrictedSession->isRestricted());
    }

    public function testTheRestrictionExpires(): void
    {
        $this->restrictedSession->open(0, (int) $this->customer->getId());

        self::assertFalse($this->restrictedSession->isRestricted());

        $this->guard->refuseAPasswordChange($this->passwordChange('a-new-password'));
    }

    public function testAnotherCustomerKeepsChangingTheirPassword(): void
    {
        $someoneElse = $this->fixtures->customer($this->fixtures->customerTitle());

        $this->restrict($someoneElse);

        $this->guard->refuseAPasswordChange($this->passwordChange('a-new-password'));

        self::assertTrue($this->restrictedSession->isRestricted());
    }

    public function testAnAdministratorChangesACustomerPasswordFromTheBackOffice(): void
    {
        $this->restrict($this->customer);
        $this->session->setAdminUser($this->fixtures->admin());

        $this->guard->refuseAPasswordChange($this->passwordChange('a-new-password'));

        self::assertTrue($this->restrictedSession->isRestricted());
    }

    public function testSigningInLiftsTheRestriction(): void
    {
        $this->restrict($this->customer);

        (new RestrictedSessionReleaseListener($this->restrictedSession))->releaseTheRestriction();

        self::assertFalse($this->restrictedSession->isRestricted());

        $this->guard->refuseAPasswordChange($this->passwordChange('a-new-password'));
    }

    private function restrict(Customer $customer): void
    {
        $this->restrictedSession->open(1800, (int) $customer->getId());
    }

    private function passwordChange(?string $password): CustomerCreateOrUpdateEvent
    {
        $event = new CustomerCreateOrUpdateEvent(
            firstname: 'John',
            lastname: 'Doe',
            email: (string) $this->customer->getEmail(),
            password: $password,
        );
        $event->setCustomer($this->customer);

        return $event;
    }
}
