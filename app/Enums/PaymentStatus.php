<?php

namespace App\Enums;

enum PaymentStatus: int
{
    case ENABLED = 1;
    case DISABLED = 2;
}
