<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Tag;
use Tests\TestCase;

class TagTest extends TestCase
{
    protected $tenancy = true;
    public function test_create_new_tag_and_attach_to_an_item()
    {
        $item = Item::factory()->create();
        $data = [
            'name' => 'tag',
            'item_id' => $item->id
        ];
        $existed = Tag::count();
        $this->actingAs($this->user)->post(route('tags.store'), $data)
            ->assertRedirect(route('items.edit', ['item' => $item->id]));

        $this->assertDatabaseCount('item_tag', 1);
        $this->assertDatabaseCount('tags', $existed + 1);
        $this->assertDatabaseHas('tags', ['name' => $data['name']]);
    }

    public function test_toggle_tag_from_an_item()
    {
        $item = Item::factory()->create();
        $data = [
            'name' => 'tag',
            'item_id' => $item->id
        ];
        $this->actingAs($this->user)->post(route('tags.store'), $data);
        $this->actingAs($this->user)->post(route('tags.toggle', ['tag' => $this->tag->id]), [
            'item_id' => $item->id
        ]);
        $this->assertTrue($item->tags->contains(fn ($value) => $value->id == $this->tag->id));

        $this->actingAs($this->user)->post(route('tags.toggle', ['tag' => $this->tag->id]), [
            'item_id' => $item->id
        ]);
        $this->assertFalse($item->fresh()->tags->contains(fn ($value) => $value->id == $this->tag->id));
    }

    public function test_create_tag_only_if_it_does_not_exist()
    {
        $item = Item::factory()->create();
        $tag = Tag::factory()->create();
        $data = [
            'name' => $tag->name,
            'item_id' => $item->id
        ];
        $existed = Tag::count();
        $this->actingAs($this->user)->post(route('tags.store'), $data)->assertRedirect(route('items.edit', ['item' => $item->id]));

        $this->assertDatabaseCount('item_tag', 1);
        $this->assertDatabaseCount('tags', $existed);
        $this->assertDatabaseHas('tags', ['name' => $data['name']]);
    }
}
