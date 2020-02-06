<?php

namespace AbandonedCartReminder\Model;

use AbandonedCartReminder\Model\Base\AbandonedCart as BaseAbandonedCart;

class AbandonedCart extends BaseAbandonedCart
{
    const RAPPEL_PAS_ENVOYE = 0;
    const RAPPEL_1_ENVOYE = 1;
    const RAPPEL_2_ENVOYE = 2;
}
