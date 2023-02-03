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

    public function test_user_change_password()
    {
        $this->actingAs($this->user)->post(route('users.changePassword'), [
            'password' => 'password',
            'new_password' => 'new_password',
            'new_password_confirmation' => 'new_password',
        ]);

        $this->postJson('api/login', [
            'email' => 'admin@gmail.com',
            'password' => 'new_password'
        ])->assertOk();
    }
}
