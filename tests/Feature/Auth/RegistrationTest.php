<?php

namespace Tests\Feature\Auth;

use App\Providers\RouteServiceProvider;

use Tests\TestCase;

class RegistrationTest extends TestCase
{
    protected $tenancy = true;

    public function test_registration_screen_can_be_rendered()
    {
        $response = $this->get(route('register'));

        $response->assertStatus(200);
    }

    public function test_new_users_can_register()
    {
        $response = $this->post(route('register'), [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(RouteServiceProvider::HOME);
    }

    public function test_cannot_register_with_invalid_role()
    {
        $response = $this->post(route('register'), [
            'name' => 'Test admin',
            'email' => 'admin@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role_id' => 0
        ]);
        $response->assertSessionHasErrors(['role_id']);
    }

    public function test_admin_role_cannot_be_used_to_register()
    {
        $response = $this->post(route('register'), [
            'name' => 'Test admin',
            'email' => 'admin@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role_id' => 1
        ]);
        $response->assertSessionHasErrors(['role_id']);
    }
}
