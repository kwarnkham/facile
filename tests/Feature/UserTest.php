<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;
use Inertia\Testing\AssertableInertia as Assert;

class UserTest extends TestCase
{
    public function test_admin_add_a_user()
    {
        $this->actingAs($this->user)->post(route('users.store'), [
            'name' => 'name',
            'email' => 'email@email.com',
        ]);

        $this->assertDatabaseCount('users', 2);
    }
}
