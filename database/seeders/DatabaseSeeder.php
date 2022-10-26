<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $now = now();
        DB::table('roles')->insert([
            ['name' => 'admin', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'merchant', 'created_at' => $now, 'updated_at' => $now],
        ]);
        DB::table('users')->insert([
            [
                'name' => 'admin',
                'email' => '911@gmail.com',
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'name' => 'mechnat',
                'email' => '123@gmail.com',
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
                'created_at' => $now,
                'updated_at' => $now
            ],

        ]);
        DB::table('role_user')->insert([
            ['role_id' => 1, 'user_id' => 1],
            ['role_id' => 2, 'user_id' => 2],
        ]);
        DB::table('merchants')->insert([
            ['user_id' => 2, 'address' => 'merchant address', 'description' => 'merchant business description']
        ]);
        DB::table('tags')->insert([
            ['name' => 'accessory'],
            ['name' => 'handmade'],
            ['name' => 'lamp']
        ]);
    }
}
