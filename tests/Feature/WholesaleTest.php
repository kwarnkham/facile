<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Wholesale;
use Tests\TestCase;

class WholesaleTest extends TestCase
{
    protected $tenancy = true;
    public function test_wholesale_can_be_added()
    {
        $data = Wholesale::factory()->make()->toArray();
        $item = Item::factory()->create();
        $data['item_id'] = $item->id;
        $this->actingAs($this->user)->post(route('wholesales.store'), $data);
        $this->actingAs($this->user)->post(route('wholesales.store'), $data)->assertSessionHasErrors(['quantity']);
        $this->assertDatabaseHas('wholesales', $data);
        $this->assertDatabaseCount('wholesales', 1);

        $data['item_id'] = $item->id + rand(1, 10);
        $this->actingAs($this->user)->post(route('wholesales.store'), $data)->assertSessionHasErrors(['item_id']);
    }

    public function test_wholesale_can_be_updated()
    {
        $item = Item::factory()->create();
        $wholesale = Wholesale::factory()->create(['item_id' => $item->id]);
        $data = Wholesale::factory()->make()->toArray();
        $data['item_id'] = $item->id;

        $this->actingAs($this->user)->put(route('wholesales.update', ['wholesale' => $wholesale->id]), $data);
        $this->actingAs($this->user)->post(route('wholesales.store'), $data)->assertSessionHasErrors(['quantity']);
        $this->assertDatabaseHas('wholesales', $data);
        $this->assertDatabaseCount('wholesales', 1);
    }
}
