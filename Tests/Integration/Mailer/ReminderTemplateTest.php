<?php

declare(strict_types=1);

namespace AbandonedCartReminder\Tests\Integration\Mailer;

use AbandonedCartReminder\AbandonedCartReminder;
use Thelia\Mailer\MailerFactory;
use Thelia\Model\Cart;
use Thelia\Model\ConfigQuery;
use Thelia\Model\Customer;
use Thelia\Model\Product;
use Thelia\Test\FixtureFactory;
use Thelia\Test\IntegrationTestCase;

final class ReminderTemplateTest extends IntegrationTestCase
{
    private MailerFactory $mailer;
    private FixtureFactory $fixtures;
    private Customer $customer;
    private Product $product;
    private Cart $cart;

    protected function setUp(): void
    {
        parent::setUp();

        ConfigQuery::write('store_email', 'shop@example.com');
        ConfigQuery::write('store_name', 'Test Shop');

        (new AbandonedCartReminder())->postActivation();

        $this->mailer = $this->getService(MailerFactory::class);
        $this->fixtures = $this->createFixtureFactory();
        $this->customer = $this->fixtures->customer($this->fixtures->customerTitle());
        $this->product = $this->fixtures->product(
            $this->fixtures->category(),
            $this->fixtures->taxRule(),
            $this->fixtures->currency(),
        );
        $this->cart = $this->fixtures->cart($this->customer);
        $this->fixtures->cartItem($this->cart, $this->product);
    }

    /**
     * @return list<array{int}>
     */
    public static function reminderNumbers(): array
    {
        return [[1], [2], [3]];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('reminderNumbers')]
    public function testTheReminderRendersWithTheCartItems(int $reminderNumber): void
    {
        $email = $this->mailer->createEmailMessage(
            AbandonedCartReminder::REMINDER_MESSAGES[$reminderNumber],
            [(string) ConfigQuery::getStoreEmail() => (string) ConfigQuery::getStoreName()],
            [(string) $this->customer->getEmail() => 'John Doe'],
            [
                'cart_id' => $this->cart->getId(),
                'currency_id' => $this->cart->getCurrencyId(),
                'recovery_token' => '1.9999999999.deadbeef',
                'unsubscribe_token' => 'dGVzdEBleGFtcGxlLmNvbQ.deadbeef',
            ],
            'en_US',
        );

        self::assertNotSame('', trim((string) $email->getSubject()));
        self::assertStringContainsString((string) $this->product->getRef(), (string) $email->getHtmlBody());
        self::assertStringContainsString((string) $this->product->getRef(), (string) $email->getTextBody());
    }
}
