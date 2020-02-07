<?php

namespace AbandonedCartReminder\Events;

interface AbandonedCartEvent
{
    const EXAMINE_CARTS_EVENT = 'abandoned_cart_reminder.cron';
}
