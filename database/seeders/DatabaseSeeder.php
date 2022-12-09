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
        ]);

        DB::table('merchants')->insert([
            [
                'name' => 'merchant name',
                'address' => 'merchant address',
                'description' => 'merchant business description',
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'name' => 'merchant name',
                'address' => 'merchant address',
                'description' => 'merchant business description',
                'created_at' => $now,
                'updated_at' => $now
            ]
        ]);

        DB::table('users')->insert([
            [
                'name' => 'admin',
                'email' => '911@gmail.com',
                'password' => '$2y$10$XVaseaCA.MTLMDMPeC0lVuuRvZNRagUlB3E2kfXJk/slvshzPbyL2',
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'name' => 'merchant',
                'email' => 'merchant@gmail.com',
                'password' => '$2y$10$XVaseaCA.MTLMDMPeC0lVuuRvZNRagUlB3E2kfXJk/slvshzPbyL2',
                'active_merchant_id' => 1,
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'name' => 'merchant2',
                'email' => 'merchant2@gmail.com',
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
                'active_merchant_id' => 2,
                'created_at' => $now,
                'updated_at' => $now
            ],

        ]);

        DB::table('merchant_user')->insert([
            [
                'user_id' => 2,
                'merchant_id' => 1,
                'is_owner' => true,
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'user_id' => 3,
                'merchant_id' => 2,
                'is_owner' => true,
                'created_at' => $now,
                'updated_at' => $now
            ]
        ]);

        DB::table('role_user')->insert([
            [
                'role_id' => 1,
                'user_id' => 1,
                'created_at' => $now,
                'updated_at' => $now
            ],
        ]);


        DB::table('payments')->insert([
            [
                'name' => 'Cash',
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'name' => 'KBZPay',
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'name' => 'WavePay',
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'name' => 'KBZmBanking',
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'name' => 'CB Pay',
                'created_at' => $now,
                'updated_at' => $now
            ],
        ]);
    }
}
