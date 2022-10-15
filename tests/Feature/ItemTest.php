<?php

namespace Tests\Feature;

use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Inertia\Testing\AssertableInertia as Assert;

class ItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_merchant_can_add_item()
    {
        $data = [
            'name' => 'item name',
            'price' => '1000',
            'description' => 'item description'
        ];
        $this->actingAs($this->merchant)->post(route('items.store'), $data)
            ->assertSessionHas('message', 'success')
            ->assertRedirect(route('items.create'));

        $this->assertDatabaseCount('items', 1);
        $this->assertDatabaseHas('items', [...$data, 'user_id' => $this->merchant->id]);
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
        $count = rand(1, 100);
        $per_page = (int)floor($count / 3);
        $items = Item::factory($count)->create(['user_id' => $this->merchant->id]);
        $this->get(
            route(
                'items.index',
                http_build_query([
                    'user_id' => $this->merchant->id,
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
                        ->where('user_id', $this->merchant->id)
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
}
