<?php

namespace Tests\Feature;

use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PictureTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_pictures()
    {
        $item = Item::factory()->create(['user_id' => $this->merchant->id]);
        $this->actingAs($this->merchant)->post(route('pictures.store'), [
            'pictures' => [UploadedFile::fake()->image('foo.jpg'), UploadedFile::fake()->image('bar.jpg')],
            'type' => 'item',
            'type_id' => $item->id
        ]);

        $this->assertDatabaseCount('pictures', 2);
    }

    public function test_picture_type()
    {
        $this->actingAs($this->merchant)->post(route('pictures.store'), [
            'pictures' => [UploadedFile::fake()->image('foo.jpg'), UploadedFile::fake()->image('bar.jpg')],
            'type' => 'iten',
            'type_id' => 1
        ])->assertSessionHasErrors(['type', 'type_id']);

        $item = Item::factory()->create(['user_id' => $this->merchant->id]);
        $this->actingAs($this->merchant)->post(route('pictures.store'), [
            'pictures' => [UploadedFile::fake()->image('foo.jpg'), UploadedFile::fake()->image('bar.jpg')],
            'type' => 'role',
            'type_id' => $item->id
        ])->assertSessionHasErrors(['type']);
    }

    public function test_picture_type_id()
    {
        $item = Item::factory()->create(['user_id' => $this->merchant->id]);
        $this->actingAs($this->merchant)->post(route('pictures.store'), [
            'pictures' => [UploadedFile::fake()->image('foo.jpg'), UploadedFile::fake()->image('bar.jpg')],
            'type' => 'item',
            'type_id' => $item->id + 1
        ])->assertSessionHasErrors(['type_id']);
    }

    public function test_pictures_are_image_type()
    {
        $item = Item::factory()->create(['user_id' => $this->merchant->id]);
        $this->actingAs($this->merchant)->post(route('pictures.store'), [
            'pictures' => ['foo.jpg', 'bar.jpg'],
            'type' => 'item',
            'type_id' => $item->id
        ])->assertSessionHasErrors(['pictures.0']);
    }
}
