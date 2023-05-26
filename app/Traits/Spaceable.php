<?php

namespace App\Traits;

use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

trait Spaceable
{

    public static function saveFile(File|UploadedFile $file)
    {
        $path = config('app')['name'] . '/pictures/' . static::spaceDir() . '/' . config('app')['env'];
        $name = Storage::putFile($path, $file, 'public');
        return basename($name);
    }

    public static function spaceDir()
    {
        $string = static::class;
        return strtolower(substr($string, strrpos($string, '\\') + 1));
    }
}
