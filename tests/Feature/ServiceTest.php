<?php

namespace Tests\Product;

use App\Models\Service;
use Tests\TestCase;

class ServiceTest extends TestCase
{
    public function test_create_service()
    {
        $service = Service::factory()->make();
        $this->actingAs($this->user)
            ->post(route('services.store'), $service->toArray());

        $this->assertDatabaseCount('services', 1);
        $this->assertDatabaseHas('services', $service->toArray());
    }

    public function test_update_service()
    {
        Service::factory()->create();
        $updatedService = Service::factory()->make();
        $this->actingAs($this->user)
            ->post(route('services.store'), $updatedService->toArray());

        $this->assertDatabaseHas('services', $updatedService->toArray());
    }
}
