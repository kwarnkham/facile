<?php

namespace Database\Seeders;

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
        DB::connection('tenant')->table('roles')->insert([
            ['name' => 'admin', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'sale', 'created_at' => $now, 'updated_at' => $now],
        ]);

        DB::connection('tenant')->table('settings')->insert([
            [
                'print_logo' => '',
                'delivery_logo' => '',
                'active_order_status' => '1,2,3,4,5,6,7',
                'created_at' => $now,
                'updated_at' => $now
            ],
        ]);


        DB::connection('tenant')->table('users')->insert([
            [
                'username' => 'admin',
                'name' => 'admin',
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
                'created_at' => $now,
                'updated_at' => $now
            ],
        ]);
        DB::connection('tenant')->table('expenses')->insert([
            [
                'name' => 'Electric Bill',
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'name' => 'Rent',
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'name' => 'General',
                'created_at' => $now,
                'updated_at' => $now
            ],
        ]);

        DB::connection('tenant')->table('role_user')->insert([
            [
                'role_id' => 1,
                'user_id' => 1,
                'created_at' => $now,
                'updated_at' => $now
            ],
            [
                'role_id' => 2,
                'user_id' => 1,
                'created_at' => $now,
                'updated_at' => $now
            ],
        ]);


        DB::connection('tenant')->table('payment_types')->insert([
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
