<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Tests\TestCase;
use Inertia\Testing\AssertableInertia as Assert;

class UserTest extends TestCase
{

    public function test_users_screen_of_merchant_role_can_be_rendered()
    {
        $count = rand(3, 100);
        $per_page = (int)floor($count / 3);
        $existed = User::count();
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
}
