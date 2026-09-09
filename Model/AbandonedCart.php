<?php

declare(strict_types=1);

namespace AbandonedCartReminder\Model;

use AbandonedCartReminder\Model\Base\AbandonedCart as BaseAbandonedCart;

class AbandonedCart extends BaseAbandonedCart
{
    public const STATUS_PENDING = 0;
    public const STATUS_REMINDED = 1;
    public const STATUS_RECOVERED = 2;
    public const STATUS_STOPPED = 3;

    public const EMAIL_SOURCE_CUSTOMER_ACCOUNT = 0;
    public const EMAIL_SOURCE_VISITOR_CAPTURE = 1;

    public const MAXIMUM_REMINDERS = 3;
}
