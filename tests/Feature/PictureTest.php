<?php

namespace Tests\Feature;

use App\Enums\ResponseStatus;
use App\Models\Feature;
use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PictureTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_pictures_item()
    {
        $item = Item::factory()->create(['user_id' => $this->merchant->id]);
        $this->actingAs($this->merchant)->post(route('pictures.store'), [
            'pictures' => [UploadedFile::fake()->image('foo.jpg'), UploadedFile::fake()->image('bar.jpg')],
            'type' => 'item',
            'type_id' => $item->id
        ]);

        $this->assertDatabaseCount('pictures', 2);

        $this->assertTrue($item->pictures->every(fn ($picture) => $picture->exists()));

        $this->assertTrue($item->pictures->every(function ($picture) {
            $picture->delete();
            return $picture->fileDeleted();
        }));
    }

    public function test_store_pictures_feature()
    {
        $feature = Feature::factory()->for(Item::factory()->state(['user_id' => $this->merchant->id]))->create();
        $this->actingAs($this->merchant)->post(route('pictures.store'), [
            'pictures' => [UploadedFile::fake()->image('foo.jpg'), UploadedFile::fake()->image('bar.jpg')],
            'type' => 'feature',
            'type_id' => $feature->id
        ]);

        $this->assertDatabaseCount('pictures', 2);

        $this->assertTrue($feature->pictures->every(fn ($picture) => $picture->exists()));

        $this->assertTrue($feature->pictures->every(function ($picture) {
            $picture->delete();
            return $picture->fileDeleted();
        }));
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

        $this->actingAs($this->merchant)->post(route('pictures.store'), [
            'pictures' => [UploadedFile::fake()->image('foo.jpg'), UploadedFile::fake()->image('bar.jpg')],
            'type' => 'feature',
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

    public function test_delete_picture()
    {
        $item = Item::factory()->create(['user_id' => $this->merchant->id]);
        $this->actingAs($this->merchant)->post(route('pictures.store'), [
            'pictures' => [UploadedFile::fake()->image('foo.jpg')],
            'type' => 'item',
            'type_id' => $item->id
        ]);

        $this->assertDatabaseCount('pictures', 1);

        $this->assertTrue($item->pictures->every(fn ($picture) => $picture->exists()));

        $picture = $item->pictures()->first();

        $this->actingAs($this->merchant)->delete(route('pictures.destroy', ['picture' => $picture->id]))
            ->assertStatus(ResponseStatus::REDIRECTED_BACK->value)
            ->assertSessionHas('message', 'Deleted');

        $this->assertTrue($picture->fileDeleted());
        $this->assertDatabaseCount('pictures', 0);
    }
}
