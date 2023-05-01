<?php

namespace App\Enums;

enum ProductStatus: int
{
    case NORMAL = 1;
    case DISABLED = 2;

    public static function array(): array
    {
        return [
            1 => 'Normal',
            2 => 'Disabled',
        ];
    }

    public static function all(): array
    {
        return [1, 2];
    }
}
