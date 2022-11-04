<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Inertia\Testing\AssertableInertia as Assert;

class UserTest extends TestCase
{
    use RefreshDatabase;
    public function test_users_screen_of_merchant_role_can_be_rendered()
    {
        $count = rand(3, 100);
        $per_page = (int)floor($count / 3);

        Role::where('name', 'merchant')->first()->update(['name' => 'not merchant']);
        $role = Role::factory()->create(['name' => 'merchant']);
        $users = User::factory($count)->hasAttached($role)->create();
        $this->get(route('users.index', [
            'role' => 'merchant',
            'per_page' => $per_page
        ]))->assertInertia(
            fn (Assert $page) => $page->component('Users')
                ->has('users.data', $per_page)
                ->where('users.per_page', $per_page)
                ->where('users.total', $count)
                ->has(
                    'users.data.0',
                    fn (Assert $page) => $page
                        ->where('id', $users[0]->id)
                        ->etc()
                )
        );
    }
}
