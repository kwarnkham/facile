<?php

namespace Tests\Feature;

use App\Models\Item;
use Tests\TestCase;
use Inertia\Testing\AssertableInertia as Assert;

class ItemTest extends TestCase
{


    public function test_merchant_can_add_item()
    {
        $data = [
            'name' => 'item name',
            'price' => '1000',
            'description' => 'item description'
        ];

        $this->actingAs($this->merchant)->post(route('items.store'), $data)
            ->assertSessionHas('message', 'success');
        // ->assertRedirect(route('items.create'));

        $this->assertDatabaseCount('items', 1);
        $this->assertDatabaseHas('items', [...$data, 'merchant_id' => $this->merchant->merchant->id]);

        $this->actingAs($this->merchant)->post(route('items.store'), $data)->assertSessionHasErrors(['name']);
    }

    public function test_merchant_can_update_item()
    {
        $item = Item::factory()->create([
            'name' => 'item name',
            'price' => '1000',
            'description' => 'item description',
            'merchant_id' => $this->merchant->merchant->id
        ]);

        $data = [
            'name' => 'item name updated',
            'price' => '2000',
            'description' => 'item description updated'
        ];

        $this->actingAs($this->merchant)->put(route('items.update', ['item' => $item->id]), $data)
            ->assertSessionHas('message', 'success');

        $this->assertDatabaseHas('items', $data);

        //add another item to test item name uniqueness
        Item::factory()->create([
            'name' => 'item name',
            'price' => '1000',
            'description' => 'item description',
            'merchant_id' => $this->merchant->merchant->id
        ]);

        //can update item with same name
        $data = [
            'name' => 'item name updated',
            'price' => '3000',
            'description' => 'item description updated again'
        ];

        $this->actingAs($this->merchant)->put(route('items.update', ['item' => $item->id]), $data)
            ->assertSessionHas('message', 'success');

        //can't update item with another item name
        $data['name'] = 'item name';
        $this->actingAs($this->merchant)->put(route('items.update', ['item' => $item->id]), $data)
            ->assertSessionHasErrors(['name']);
    }

    public function test_user_cannot_add_item()
    {
        $data = [
            'name' => 'item name',
            'price' => '1000',
            'description' => 'item description'
        ];
        $this->actingAs($this->user)->post(route('items.store'), $data)
            ->assertStatus(302)
            ->assertSessionHas('error', 'unauthorized');
    }

    public function test_list_items()
    {
        $count = rand(10, 100);
        $per_page = (int)floor($count / 3);
        $items = Item::factory($count)->create(['merchant_id' => $this->merchant->merchant->id]);
        $this->get(
            route(
                'items.index',
                http_build_query([
                    'merchant_id' => $this->merchant->merchant->id,
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
                        ->where('merchant_id', $this->merchant->merchant->id)
                        ->etc()
                )
        )->assertOk();
    }

    public function test_only_merchant_can_visit_item_create_page()
    {
        $this->actingAs($this->merchant)->get(route('items.create'))->assertOk();
        $this->actingAs($this->user)->get(route('items.create'))->assertStatus(302);
        $this->get(route('items.create'))->assertStatus(302);
    }

    public function test_item_screen_can_be_rendered()
    {
        $item = Item::factory()->create(['merchant_id' => $this->merchant->merchant->id]);
        $this->get(route('items.show', ['item' => $item->id]))->assertOk()->assertInertia(
            fn (Assert $page) => $page->component('Item')->has(
                'item',
                fn (Assert $page) => $page->where('id', $item->id)->etc()
            )
        );
    }
}
