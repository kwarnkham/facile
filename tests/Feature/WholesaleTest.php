<?php

namespace Tests\Feature;

use App\Enums\ResponseStatus;
use App\Models\Item;
use App\Models\Wholesale;
use Tests\TestCase;

class WholesaleTest extends TestCase
{

    public function test_wholesale_can_be_added()
    {
        $data = Wholesale::factory()->make()->toArray();
        $item = Item::factory()->create(['user_id' => $this->merchant->id]);
        $data['item_id'] = $item->id;
        $this->actingAs($this->merchant)->post(route('wholesales.store'), $data);
        $this->actingAs($this->merchant)->post(route('wholesales.store'), $data)->assertSessionHasErrors(['quantity']);
        $this->assertDatabaseHas('wholesales', $data);
        $this->assertDatabaseCount('wholesales', 1);

        $data['item_id'] = $item->id + rand(1, 10);
        $this->actingAs($this->merchant)->post(route('wholesales.store'), $data)->assertSessionHasErrors(['item_id']);
    }

    public function test_wholesale_can_be_updated()
    {
        $item = Item::factory()->create(['user_id' => $this->merchant->id]);
        $wholesale = Wholesale::factory()->create(['item_id' => $item->id]);
        $data = Wholesale::factory()->make()->toArray();
        $data['item_id'] = $item->id;

        $this->actingAs($this->merchant)->put(route('wholesales.update', ['wholesale' => $wholesale->id]), $data);
        $this->actingAs($this->merchant)->post(route('wholesales.store'), $data)->assertSessionHasErrors(['quantity']);
        $this->assertDatabaseHas('wholesales', $data);
        $this->assertDatabaseCount('wholesales', 1);

        $data['item_id'] = $item->id + rand(1, 10);
        $this->actingAs($this->merchant)->put(route('wholesales.update', ['wholesale' => $wholesale->id]), $data)->assertStatus(ResponseStatus::UNAUTHORIZED->value);
    }
}
