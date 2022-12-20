<?php

namespace App\Traits;

use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

trait Spaceable
{

    public static function saveFile(File|UploadedFile $file)
    {
        return basename(Storage::putFile(config('app')['name'] . '/pictures/' . static::spaceDir() . '/' . config('app')['env'], $file, 'public'));
    }

    public static function spaceDir()
    {
        $string = static::class;
        return strtolower(substr($string, strrpos($string, '\\') + 1));
    }
}
