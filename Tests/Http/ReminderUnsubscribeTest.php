<?php

declare(strict_types=1);

namespace AbandonedCartReminder\Tests\Http;

use AbandonedCartReminder\Domain\Service\ReminderConsent;
use AbandonedCartReminder\Model\AbandonedCartReminderOptOutQuery;
use Thelia\Test\WebIntegrationTestCase;

final class ReminderUnsubscribeTest extends WebIntegrationTestCase
{
    private const EMAIL = 'leaves-carts-behind@example.com';

    public function testAValidLinkStopsTheRemindersAndSaysSo(): void
    {
        $this->assertPageRenders('/cart-reminder/unsubscribe/'.$this->tokenFor(self::EMAIL));

        self::assertStringContainsString(
            'Reminders stopped',
            (string) $this->client->getResponse()->getContent(),
        );
        self::assertNotNull(
            AbandonedCartReminderOptOutQuery::create()->findOneByEmail(self::EMAIL),
            'A valid unsubscribe link must record the refusal.',
        );
    }

    public function testABrokenLinkSaysSoAndChangesNothing(): void
    {
        $this->client->request('GET', '/cart-reminder/unsubscribe/not-a-real-token');

        self::assertSame(410, $this->client->getResponse()->getStatusCode());
        self::assertSame(0, AbandonedCartReminderOptOutQuery::create()->count());
    }

    private function tokenFor(string $email): string
    {
        return $this->getService(ReminderConsent::class)->unsubscribeToken($email);
    }
}
