<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;
use Inertia\Testing\AssertableInertia as Assert;

class UserTest extends TestCase
{

    public function test_users_screen_can_be_rendered()
    {
        $count = rand(5, 100);
        $existed = User::count();
        $per_page = (int)floor($count / 3) + $existed;
        $users = User::factory($count)->create();
        $this->get(route('users.index', [
            'per_page' => $per_page
        ]))->assertInertia(
            fn (Assert $page) => $page->component('Users')
                ->has('users.data', $per_page)
                ->where('users.per_page', $per_page)
                ->where('users.total', $count + $existed)
                ->has(
                    'users.data.' . $existed,
                    fn (Assert $page) => $page
                        ->where('id', $users[0]->id)
                        ->etc()
                )
        );
    }

    public function test_admin_add_a_user()
    {
        $this->actingAs($this->user)->post(route('users.store'), [
            'name' => 'name',
            'email' => 'email@email.com',
        ]);

        $this->assertDatabaseCount('users', 2);
    }
}
