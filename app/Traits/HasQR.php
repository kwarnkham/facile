<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

trait HasQR
{
    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::deleted(function ($model) {
            $name =  $model->qrFileName();
            $file = static::qrDir() . '/' . $name;
            if (Storage::exists($file)) Storage::delete($file);
        });
    }

    public function qrFileName()
    {
        return $this->id . '.svg';
    }

    public static function modelName()
    {
        $string = static::class;
        return strtolower(substr($string, strrpos($string, '\\') + 1));
    }

    public function generateQR()
    {
        $name =  $this->qrFileName();
        $path = static::qrDir() . '/' . $name;
        if (Storage::exists($path)) return;
        QrCode::generate(route(static::modelName() . 's.show', [static::modelName() => $this->id]), Storage::path($name));
        if (Storage::exists($name)) Storage::move($name, $path);
        return Storage::exists($path);
    }

    public static function qrDir()
    {
        return config('app')['name'] . '/qr/' . static::modelName() . '/' . config('app')['env'];
    }

    public function qr()
    {
        $this->generateQR();
        $name =  $this->qrFileName();
        $file = static::qrDir() . '/' . $name;
        return Storage::exists($file) ? Storage::url($file) : null;
    }

    public function qrFilePath()
    {
        return static::qrDir() . '/' . $this->qrFileName();
    }
}
