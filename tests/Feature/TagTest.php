<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TagTest extends TestCase
{
    use RefreshDatabase;
    public function test_create_new_tag_and_attach_to_an_item()
    {
        $item = Item::factory()->create([
            'user_id' => $this->merchant->id
        ]);
        $data = [
            'name' => 'tag',
            'item_id' => $item->id
        ];
        $existed = Tag::count();
        $this->actingAs($this->merchant)->post(route('tags.store'), $data)->assertRedirect(route('items.edit', ['item' => $item->id]));

        $this->assertDatabaseCount('item_tag', 1);
        $this->assertDatabaseCount('tags', $existed + 1);
        $this->assertDatabaseHas('tags', ['name' => $data['name']]);
    }
}
