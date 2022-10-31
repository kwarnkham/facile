<?php

namespace Tests\Feature;

use App\Models\Feature;
use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ItemFeatureTest extends TestCase
{
    use RefreshDatabase;
    public function test_add_feature_to_an_item()
    {
        $data = Feature::factory()->make()->toArray();
        $item = Item::factory()->create(['user_id' => $this->merchant->id]);
        $data['item_id'] = $item->id;
        $this->actingAs($this->merchant)->post(route('features.store'), $data);
        $this->assertDatabaseCount('features', 1);
        $this->assertDatabaseHas('features', $data);
        $this->assertEquals($item->features()->first()->name, $data['name']);
    }
}
