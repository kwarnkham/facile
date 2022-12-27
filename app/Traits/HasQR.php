<?php

namespace App\Traits;

use SimpleSoftwareIO\QrCode\Facades\QrCode;

trait HasQR
{
    public static function modelName()
    {
        $string = static::class;
        return strtolower(substr($string, strrpos($string, '\\') + 1));
    }

    public function generateQR()
    {
        return QrCode::generate(route(static::modelName() . 's.show', [static::modelName() => $this->id]));
    }
}
