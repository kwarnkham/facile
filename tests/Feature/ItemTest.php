<?php

namespace Tests\Feature;

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
}
