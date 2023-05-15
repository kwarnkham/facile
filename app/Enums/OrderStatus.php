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
    case ON_DELIVERY = 7;

    public static function array(): array
    {
        return [
            [
                'id' => 1,
                'label' => 'Pending'
            ],
            [
                'id' => 2,
                'label' => 'Partially Paid'
            ],
            [
                'id' => 3,
                'label' => 'Paid'
            ],
            [
                'id' => 4,
                'label' => 'Canceled'
            ],
            [
                'id' => 5,
                'label' => 'Completed'
            ],
            [
                'id' => 6,
                'label' => 'Packed'
            ],
            [
                'id' => 7,
                'label' => 'On Delivery'
            ],
        ];
    }

    public static function all(): array
    {
        return [1, 2, 3, 4, 5, 6, 7];
    }
}
