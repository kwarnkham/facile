<?php

namespace App\Enums;

enum OrderStatus: int
{
    case PENDING = 1;
    case PARTIALLY_PAID = 2;
    case PAID = 3;
    case CANCELED = 4;
    case COMPLETED = 5;
    case PACKED = 6;

    public static function array(): array
    {
        return [
            1 => 'Pending',
            2 => 'Partially Paid',
            3 => 'Paid',
            4 => 'Canceled',
            5 => 'Completed',
            6 => 'Packed'
        ];
    }

    public static function all(): array
    {
        return [1, 2, 3, 4, 5, 6];
    }
}
