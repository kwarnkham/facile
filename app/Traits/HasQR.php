<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

trait HasQR
{
    public function fileName()
    {
        return $this->id . '.svg';
    }

    public static function qrDir()
    {
        $string = static::class;
        return strtolower(substr($string, strrpos($string, '\\') + 1));
    }

    public function generateQR()
    {
        $name =  $this->fileName();
        $path = static::qrPath() . '/' . $name;
        if (Storage::exists($path)) return;
        QrCode::generate(route(static::qrDir() . 's.show', [static::qrDir() => $this->id]), Storage::path($name));
        if (Storage::exists($name)) Storage::move($name, $path);
        return Storage::exists($path);
    }

    public static function qrPath()
    {
        return config('app')['name'] . '/qr/' . static::qrDir() . '/' . config('app')['env'];
    }

    public function qr()
    {
        $this->generateQR();
        $name =  $this->fileName();
        $path = static::qrPath() . '/' . $name;
        return Storage::exists($path) ? Storage::url($path) : null;
    }
}
