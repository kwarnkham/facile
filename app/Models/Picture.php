<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\File;
use Illuminate\Http\UploadedFile;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class Picture extends Model
{
    use HasFactory, UsesTenantConnection;

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::deleted(function ($picture) {
            $file = $picture->path();
            if (Storage::exists($file)) Storage::delete($file);
        });
    }

    public function pictureable()
    {
        return $this->morphTo();
    }

    public function name(): Attribute
    {
        return Attribute::make(
            fn () => Storage::url(
                $this->path()
            )
        );
    }

    private function path()
    {
        return config('app')['name']
            . '/pictures/' . strtolower(substr($this->pictureable_type, strrpos($this->pictureable_type, '\\') + 1)) . '/' . config('app')['env'] . '/' . $this->getRawOriginal('name');
    }

    public function exists()
    {
        return Storage::exists($this->path());
    }

    public function fileDeleted()
    {
        return !Storage::exists($this->path());
    }

    public static function savePictureInDisk(File|UploadedFile $picture, string $dir)
    {
        return basename(Storage::putFile(config('app')['name'] . '/' . $dir . '/' . config('app')['env'], $picture, 'public'));
    }

    public static function deletePictureFromDisk(string $name, string $dir)
    {
        $file = static::picturePath($name, $dir);
        if (Storage::exists($file)) return Storage::delete($file);
    }

    public static function picturePath(string $name, string $dir)
    {
        return config('app')['name'] . '/' . $dir . '/' . config('app')['env'] . '/' . $name;
    }
}
