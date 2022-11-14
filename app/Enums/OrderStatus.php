<?php

namespace App\Enums;

enum OrderStatus: int
{
    case PENDING = 1;
    case PARTIALLY_PAID = 2;
    case PAID = 3;
    case CANCELED = 4;
    case COMPLETED = 5;
}
