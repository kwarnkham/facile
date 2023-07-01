<?php

namespace App\Enums;

enum ProductType: int
{
    case STOCKED = 1;
    case UNSTOCKED = 2;


    public static function all(): array
    {
        return [1, 2];
    }
}
