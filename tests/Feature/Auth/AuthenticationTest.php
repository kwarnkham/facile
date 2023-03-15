<?php

namespace Tests\Product\Auth;

use App\Models\User;
use App\Providers\RouteServiceProvider;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{


    public function test_login_screen_can_be_rendered()
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen()
    {
        $user = User::factory()->create();

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(RouteServiceProvider::HOME);
    }

    public function test_users_can_not_authenticate_with_invalid_password()
    {
        $user = User::factory()->create();

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_change_password()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('changePassword'), [
            'password' => 'password',
            'new_password' => 'new_password',
            'new_password_confirmation' => 'new_password',
        ]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'new_password',
        ]);

        $this->assertAuthenticated();
    }
}
