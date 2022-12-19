<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\User;
use Tests\TestCase;
use Inertia\Testing\AssertableInertia as Assert;

class ItemTest extends TestCase
{


    public function test_admin_can_add_item()
    {
        $data = Item::factory()->make()->toArray();
        $this->actingAs($this->user)->post(route('items.store'), $data)
            ->assertSessionHas('message', 'success');

        $this->assertDatabaseCount('items', 1);
        $this->assertDatabaseHas('items', $data);

        $this->actingAs($this->user)->post(route('items.store'), $data)->assertSessionHasErrors(['name']);
    }

    public function test_admin_can_update_item()
    {
        $item = Item::factory()->create();

        $data = Item::factory()->make(['name' => 'updated'])->toArray();

        $this->actingAs($this->user)->put(route('items.update', ['item' => $item->id]), $data)
            ->assertSessionHas('message', 'success');

        $this->assertDatabaseHas('items', $data);

        Item::factory()->create([
            'name' => 'dupe',
        ]);

        $this->actingAs($this->user)->put(route('items.update', ['item' => $item->id]), Item::factory()->make(['name' => 'updated'])->toArray())
            ->assertSessionHas('message', 'success');

        $this->actingAs($this->user)->put(route('items.update', ['item' => $item->id]), Item::factory()->make(['name' => 'dupe'])->toArray())
            ->assertSessionHasErrors(['name']);
    }

    public function test_non_admin_user_cannot_add_item()
    {
        $data = [
            'name' => 'item name',
            'price' => '1000',
            'description' => 'item description'
        ];

        $this->actingAs(User::factory()->create())->post(route('items.store'), $data)
            ->assertStatus(302)
            ->assertSessionHas('error', 'unauthorized');

        $this->assertDatabaseCount('items', 0);
    }

    public function test_list_items()
    {
        $count = rand(10, 100);
        $per_page = (int)floor($count / 3);
        $items = Item::factory($count)->create();
        $this->get(
            route(
                'items.index',
                http_build_query([
                    'per_page' => $per_page
                ])
            )
        )->assertInertia(
            fn (Assert $page) => $page->component('Items')
                ->has('items.data', $per_page)
                ->where('items.per_page', $per_page)
                ->where('items.total', $count)
                ->has(
                    'items.data.0',
                    fn (Assert $page) => $page
                        ->where('id', $items[0]->id)
                        ->etc()
                )
        )->assertOk();
    }


    public function test_item_screen_can_be_rendered()
    {
        $item = Item::factory()->create();
        $this->get(route('items.show', ['item' => $item->id]))->assertOk()->assertInertia(
            fn (Assert $page) => $page->component('Item')->has(
                'item',
                fn (Assert $page) => $page->where('id', $item->id)->etc()
            )
        );
    }
}
