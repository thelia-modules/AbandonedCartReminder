<?php

namespace AbandonedCartReminder\Model;

use AbandonedCartReminder\Model\Base\AbandonedCart as BaseAbandonedCart;

class AbandonedCart extends BaseAbandonedCart
{
    public const RAPPEL_PAS_ENVOYE = 0;
    public const RAPPEL_1_ENVOYE = 1;
    public const RAPPEL_2_ENVOYE = 2;
}
