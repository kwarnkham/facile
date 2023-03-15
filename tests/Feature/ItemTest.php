<?php

namespace Tests\Product;

use App\Models\Item;
use App\Models\User;
use Tests\TestCase;

class ItemTest extends TestCase
{
    public function test_admin_can_add_item()
    {
        $data = Item::factory()->make()->toArray();
        $this->actingAs($this->user)->postJson(route('items.store'), $data);

        $this->assertDatabaseCount('items', 1);
        $this->assertDatabaseHas('items', $data);

        $this->actingAs($this->user)->postJson(route('items.store'), $data)->assertUnprocessable();
    }

    public function test_admin_can_update_item()
    {
        $item = Item::factory()->create();

        $data = Item::factory()->make(['name' => 'updated'])->toArray();

        $this->actingAs($this->user)->putJson(route('items.update', ['item' => $item->id]), $data);

        $this->assertDatabaseHas('items', $data);

        Item::factory()->create([
            'name' => 'dupe',
        ]);

        $this->actingAs($this->user)->putJson(route('items.update', ['item' => $item->id]), Item::factory()->make(['name' => 'updated'])->toArray())
            ->assertOk();

        $this->actingAs($this->user)->putJson(route('items.update', ['item' => $item->id]), Item::factory()->make(['name' => 'dupe'])->toArray())
            ->assertUnprocessable();
    }

    public function test_non_admin_user_cannot_add_item()
    {
        $data = [
            'name' => 'item name',
            'price' => '1000',
            'description' => 'item description'
        ];

        $this->actingAs(User::factory()->create())->postJson(route('items.store'), $data)->assertForbidden();

        $this->assertDatabaseCount('items', 0);
    }

    // public function test_list_items()
    // {
    //     $count = rand(10, 100);
    //     $per_page = (int)floor($count / 3);
    //     $items = Item::factory($count)->create();
    //     $this->get(
    //         route(
    //             'items.index',
    //             http_build_query([
    //                 'per_page' => $per_page
    //             ])
    //         )
    //     )->assertInertia(
    //         fn (Assert $page) => $page->component('Items')
    //             ->has('items.data', $per_page)
    //             ->where('items.per_page', $per_page)
    //             ->where('items.total', $count)
    //             ->has(
    //                 'items.data.0',
    //                 fn (Assert $page) => $page
    //                     ->where('id', $items[0]->id)
    //                     ->etc()
    //             )
    //     )->assertOk();
    // }


    public function test_item_screen_can_be_rendered()
    {
        $item = Item::factory()->create();
        $this->getJson(route('items.show', ['item' => $item->id]))->assertOk();
    }
}
