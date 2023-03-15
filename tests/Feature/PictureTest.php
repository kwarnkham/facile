<?php

namespace Tests\Product;

use App\Enums\ResponseStatus;
use App\Models\Product;
use App\Models\Item;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class PictureTest extends TestCase
{


    public function test_store_pictures_item()
    {
        $item = Item::factory()->create();
        $this->actingAs($this->user)->postJson(route('pictures.store'), [
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

    public function test_store_pictures_product()
    {
        $product = Product::factory()->for(Item::factory())->create();
        $this->actingAs($this->user)->postJson(route('pictures.store'), [
            'pictures' => [UploadedFile::fake()->image('foo.jpg'), UploadedFile::fake()->image('bar.jpg')],
            'type' => 'product',
            'type_id' => $product->id
        ]);

        $this->assertDatabaseCount('pictures', 2);

        $this->assertTrue($product->pictures->every(fn ($picture) => $picture->exists()));

        $this->assertTrue($product->pictures->every(function ($picture) {
            $picture->delete();
            return $picture->fileDeleted();
        }));
    }

    public function test_picture_type()
    {
        $this->actingAs($this->user)->postJson(route('pictures.store'), [
            'pictures' => [UploadedFile::fake()->image('foo.jpg'), UploadedFile::fake()->image('bar.jpg')],
            'type' => 'iten',
            'type_id' => 1
        ])->assertUnprocessable();

        $item = Item::factory()->create();
        $this->actingAs($this->user)->postJson(route('pictures.store'), [
            'pictures' => [UploadedFile::fake()->image('foo.jpg'), UploadedFile::fake()->image('bar.jpg')],
            'type' => 'role',
            'type_id' => $item->id
        ])->assertUnprocessable();
    }

    public function test_picture_type_id()
    {
        $item = Item::factory()->create();
        $this->actingAs($this->user)->postJson(route('pictures.store'), [
            'pictures' => [UploadedFile::fake()->image('foo.jpg'), UploadedFile::fake()->image('bar.jpg')],
            'type' => 'item',
            'type_id' => $item->id + 1
        ])->assertUnprocessable();

        $this->actingAs($this->user)->postJson(route('pictures.store'), [
            'pictures' => [UploadedFile::fake()->image('foo.jpg'), UploadedFile::fake()->image('bar.jpg')],
            'type' => 'product',
            'type_id' => $item->id + 1
        ])->assertUnprocessable();
    }

    public function test_pictures_are_image_type()
    {
        $item = Item::factory()->create();
        $this->actingAs($this->user)->postJson(route('pictures.store'), [
            'pictures' => ['foo.jpg', 'bar.jpg'],
            'type' => 'item',
            'type_id' => $item->id
        ])->assertUnprocessable();
    }

    public function test_delete_picture()
    {
        $item = Item::factory()->create();
        $this->actingAs($this->user)->postJson(route('pictures.store'), [
            'pictures' => [UploadedFile::fake()->image('foo.jpg')],
            'type' => 'item',
            'type_id' => $item->id
        ]);

        $this->assertDatabaseCount('pictures', 1);

        $this->assertTrue($item->pictures->every(fn ($picture) => $picture->exists()));

        $picture = $item->pictures()->first();

        $this->actingAs($this->user)->delete(route('pictures.destroy', ['picture' => $picture->id]))
            ->assertStatus(ResponseStatus::REDIRECTED_BACK->value)
            ->assertSessionHas('message', 'Deleted');

        $this->assertTrue($picture->fileDeleted());
        $this->assertDatabaseCount('pictures', 0);
    }
}
