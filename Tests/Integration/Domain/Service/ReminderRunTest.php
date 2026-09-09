<?php

declare(strict_types=1);

namespace AbandonedCartReminder\Tests\Integration\Domain\Service;

use AbandonedCartReminder\AbandonedCartReminder;
use AbandonedCartReminder\Domain\Service\AbandonedCartDetector;
use AbandonedCartReminder\Domain\Service\AbandonedCartTracker;
use AbandonedCartReminder\Domain\Service\CartRecoveryLink;
use AbandonedCartReminder\Domain\Service\ReminderConfiguration;
use AbandonedCartReminder\Domain\Service\ReminderConsent;
use AbandonedCartReminder\Domain\Service\ReminderMailer;
use AbandonedCartReminder\Domain\Service\ReminderRun;
use AbandonedCartReminder\Domain\Service\ReminderScheduler;
use AbandonedCartReminder\Model\AbandonedCart;
use AbandonedCartReminder\Model\AbandonedCartQuery;
use AbandonedCartReminder\Model\AbandonedCartReminderOptOut;
use AbandonedCartReminder\Tests\Support\RecordingMailer;
use Thelia\Model\Cart;
use Thelia\Model\CartItemQuery;
use Thelia\Model\CartQuery;
use Thelia\Model\Customer;
use Thelia\Model\Product;
use Thelia\Test\FixtureFactory;
use Thelia\Test\IntegrationTestCase;

final class ReminderRunTest extends IntegrationTestCase
{
    private ReminderRun $run;
    private RecordingMailer $mailer;
    private FixtureFactory $fixtures;
    private Customer $customer;
    private Product $product;
    private \DateTimeImmutable $now;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mailer = new RecordingMailer();

        $configuration = new ReminderConfiguration();

        $this->run = new ReminderRun(
            new AbandonedCartDetector(),
            new AbandonedCartTracker(new ReminderConsent()),
            new ReminderScheduler($configuration),
            new ReminderMailer($this->mailer, $configuration, new CartRecoveryLink('test-secret', $configuration), new ReminderConsent('test-secret')),
            $configuration,
        );

        $this->fixtures = $this->createFixtureFactory();
        $this->customer = $this->fixtures->customer($this->fixtures->customerTitle());
        $this->product = $this->fixtures->product(
            $this->fixtures->category(),
            $this->fixtures->taxRule(),
            $this->fixtures->currency(),
        );
        $this->now = new \DateTimeImmutable();

        AbandonedCartReminder::setConfigValue(AbandonedCartReminder::FIRST_REMINDER_DELAY_IN_HOURS, '4');
        AbandonedCartReminder::setConfigValue(AbandonedCartReminder::SECOND_REMINDER_DELAY_IN_HOURS, '24');
        AbandonedCartReminder::setConfigValue(AbandonedCartReminder::THIRD_REMINDER_DELAY_IN_HOURS, '72');
    }

    public function testAnAbandonedCartGetsTheFirstReminder(): void
    {
        $cart = $this->abandonedCart('-5 hours');

        $report = $this->run->execute(false, null, $this->now);

        self::assertSame(1, $report->cartsTracked);
        self::assertCount(1, $this->messagesFor(1));
        self::assertSame(1, $this->trackedCart($cart)->getRemindersSent());
        self::assertSame(AbandonedCart::STATUS_REMINDED, $this->trackedCart($cart)->getStatus());
    }

    public function testASecondRunRightAfterSendsNothing(): void
    {
        $this->abandonedCart('-5 hours');

        $this->run->execute(false, null, $this->now);
        $secondReport = $this->run->execute(false, null, $this->now);

        self::assertSame(0, $secondReport->remindersSent);
        self::assertCount(1, $this->messagesFor(1));
    }

    public function testTheThreeRemindersFollowTheirOwnDelaysAndThenStop(): void
    {
        $cart = $this->abandonedCart('-5 hours');

        $this->run->execute(false, null, $this->now);
        $this->run->execute(false, null, $this->now->modify('+25 hours'));
        $this->run->execute(false, null, $this->now->modify('+98 hours'));
        $this->run->execute(false, null, $this->now->modify('+200 hours'));

        self::assertCount(1, $this->messagesFor(1));
        self::assertCount(1, $this->messagesFor(2));
        self::assertCount(1, $this->messagesFor(3));
        self::assertSame(3, $this->trackedCart($cart)->getRemindersSent());
        self::assertSame(AbandonedCart::STATUS_STOPPED, $this->trackedCart($cart)->getStatus());
    }

    public function testACustomerWhoRefusedRemindersIsNeverFollowed(): void
    {
        (new AbandonedCartReminderOptOut())
            ->setEmail((string) $this->customer->getEmail())
            ->save();

        $cart = $this->abandonedCart('-5 hours');

        $report = $this->run->execute(false, null, $this->now);

        self::assertSame(0, $report->cartsTracked);
        self::assertSame([], $this->messagesFor(1));
        self::assertNull(AbandonedCartQuery::create()->findOneByCartId($cart->getId()));
    }

    public function testACartEmptiedSinceIsStoppedInsteadOfReminded(): void
    {
        $cart = $this->abandonedCart('-5 hours');
        $this->run->execute(false, null, $this->now);

        CartItemQuery::create()->filterByCartId($cart->getId())->delete();

        $report = $this->run->execute(false, null, $this->now->modify('+25 hours'));

        self::assertSame(1, $report->cartsNoLongerEligible);
        self::assertSame([], $this->messagesFor(2));
        self::assertSame(AbandonedCart::STATUS_STOPPED, $this->trackedCart($cart)->getStatus());
    }

    public function testADryRunNeitherSendsNorRecords(): void
    {
        $cart = $this->abandonedCart('-5 hours');

        $report = $this->run->execute(true, null, $this->now);

        self::assertTrue($report->dryRun);
        self::assertSame(1, $report->remindersSent);
        self::assertSame([], $this->mailer->customerMessages);
        self::assertNull(AbandonedCartQuery::create()->findOneByCartId($cart->getId()));
    }

    public function testAVisitorWhoLeftAnAddressIsRemindedToo(): void
    {
        $cart = $this->fixtures->cart();
        $this->fixtures->cartItem($cart, $this->product);
        $cart->setUpdatedAt(new \DateTime('-5 hours'));
        $cart->save($this->getPropelConnection());

        $tracker = new AbandonedCartTracker(new ReminderConsent('test-secret'));
        $trackedCart = $tracker->trackVisitorEmail($cart, 'Visiteur@Example.com ', 'fr_FR');

        self::assertInstanceOf(AbandonedCart::class, $trackedCart);
        self::assertSame('visiteur@example.com', $trackedCart->getEmail());
        self::assertSame(AbandonedCart::EMAIL_SOURCE_VISITOR_CAPTURE, $trackedCart->getEmailSource());

        $this->run->execute(false, null, $this->now);

        self::assertSame(['visiteur@example.com'], $this->mailer->recipientsOf(
            AbandonedCartReminder::REMINDER_MESSAGES[1]
        ));
    }

    public function testAVisitorAddressThatIsNotOneIsRefused(): void
    {
        $cart = $this->fixtures->cart();
        $tracker = new AbandonedCartTracker(new ReminderConsent('test-secret'));

        self::assertNull($tracker->trackVisitorEmail($cart, 'not-an-address'));
        self::assertNull($tracker->trackVisitorEmail($cart, ''));
    }

    public function testTheLimitCountsRemindersSentRatherThanCartsLookedAt(): void
    {
        $emptiedSince = $this->remindedCart('-100 hours');
        $alsoEmptiedSince = $this->remindedCart('-100 hours');
        $stillWaiting = $this->remindedCart('-30 hours');

        foreach ([$emptiedSince, $alsoEmptiedSince] as $trackedCart) {
            CartItemQuery::create()->filterByCartId($trackedCart->getCartId())->delete();
        }

        $report = $this->run->execute(false, 1, $this->now);

        self::assertSame(1, $report->remindersSent);
        self::assertSame(2, $report->cartsNoLongerEligible);
        self::assertSame(
            [(string) $stillWaiting->getCart()?->getCustomer()?->getEmail()],
            $this->mailer->recipientsOf(AbandonedCartReminder::REMINDER_MESSAGES[2])
        );
    }

    private function remindedCart(string $lastReminder): AbandonedCart
    {
        $cart = $this->abandonedCart('-200 hours');
        $trackedCart = (new AbandonedCartTracker(new ReminderConsent('test-secret')))->track($cart);

        self::assertInstanceOf(AbandonedCart::class, $trackedCart);

        $trackedCart
            ->setStatus(AbandonedCart::STATUS_REMINDED)
            ->setRemindersSent(1)
            ->setLastReminderAt(new \DateTime($lastReminder))
            ->save();

        return $trackedCart;
    }

    public function testACartPurgedByMaintenanceLeavesTheAbandonedList(): void
    {
        $cart = $this->abandonedCart('-5 hours');
        $this->run->execute(false, null, $this->now);

        self::assertNotNull(AbandonedCartQuery::create()->findOneByCartId($cart->getId()));

        CartQuery::create()->filterById($cart->getId())->delete();

        self::assertNull(AbandonedCartQuery::create()->findOneByCartId($cart->getId()));
    }

    private function abandonedCart(string $lastTouched): Cart
    {
        $cart = $this->fixtures->cart($this->customer);
        $this->fixtures->cartItem($cart, $this->product);
        $cart->setUpdatedAt(new \DateTime($lastTouched));
        $cart->save($this->getPropelConnection());

        return $cart;
    }

    private function trackedCart(Cart $cart): AbandonedCart
    {
        $trackedCart = AbandonedCartQuery::create()->findOneByCartId($cart->getId());

        self::assertInstanceOf(AbandonedCart::class, $trackedCart);
        $trackedCart->reload();

        return $trackedCart;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function messagesFor(int $reminderNumber): array
    {
        return $this->mailer->parametersOfMessagesSent(
            AbandonedCartReminder::REMINDER_MESSAGES[$reminderNumber]
        );
    }
}
