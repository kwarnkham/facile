<?php

namespace App\Traits;

use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

trait Spaceable
{

    public static function saveFile(File|UploadedFile $file)
    {
        return basename(Storage::putFile(config('app')['name'] . '/' . static::dir() . '/' . config('app')['env'], $file, 'public'));
    }

    private static function dir()
    {
        return strtolower(substr(static::class, strrpos(static::class, '\\') + 1));
    }

    // Item::factory()->has(Picture::factory()->state(['name'=>Item::saveFile(UploadedFile::fake()->image('foo.jpg'))]))->create(['user_id'=>2])
}
