<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered()
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register()
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(RouteServiceProvider::HOME);
    }

    public function test_new_merchants_can_register()
    {
        $data = [
            'name' => 'Test Merchnat',
            'email' => 'merchant@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role_id' => 2,
            'description' => 'description',
            'address' => 'address'
        ];
        $response = $this->post('/register', $data);

        $this->assertAuthenticated();
        $response->assertRedirect(RouteServiceProvider::HOME);
        $user = User::where('email', $data['email'])->first();
        $this->assertTrue($user->hasRole('merchant'));
        $this->assertEquals($user->merchant->description, $data['description']);
        $this->assertEquals($user->merchant->address, $data['address']);
    }

    public function test_cannot_register_with_invalid_role()
    {
        $response = $this->post('/register', [
            'name' => 'Test Merchnat',
            'email' => 'merchant@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role_id' => 0
        ]);
        $response->assertSessionHasErrors(['role_id']);
    }

    public function test_admin_role_cannot_be_used_to_register()
    {
        $response = $this->post('/register', [
            'name' => 'Test Merchnat',
            'email' => 'merchant@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role_id' => 1
        ]);
        $response->assertSessionHasErrors(['role_id']);
    }
}
