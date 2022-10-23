<?php

namespace Database\Seeders;

use App\Models\Merchant;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = User::factory()->has(Merchant::factory())->create(['email' => '123@gmail.com']);
        User::factory()->create(['email' => '111@gmail.com']);
        $user->roles()->attach(2);
    }
}
