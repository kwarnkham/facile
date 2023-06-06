<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Tenant;
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

        if (!is_null(DB::connection('tenant')->getConfig()['database'])) {
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

            DB::connection('tenant')->table('payments')->insert([
                [
                    'payment_type_id' => 1,
                    'status' => 1,
                    'created_at' => $now,
                    'updated_at' => $now
                ]
            ]);
        }

        if (DB::connection('mysql')->table('plans')->count() == 0) {

            DB::connection('mysql')->table('plans')->insert(
                [
                    [
                        'name' => 'entry',
                        'details' => json_encode([
                            'order' => 50,
                            'product' => 50,
                            'purchase' => 50,
                            'user' => 10,
                            'task' => 50,
                            'duty' => 10,
                            'payment' => 10,
                            'absence' => 50,
                            'overtime' => 50,
                            'expense' => 50
                        ]),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                    [
                        'name' => 'unlimited',
                        'details' => json_encode([
                            'order' => -1,
                            'product' => -1,
                            'purchase' => -1,
                            'user' => -1,
                            'task' => -1,
                            'duty' => -1,
                            'payment' => -1,
                            'absence' => -1,
                            'overtime' => -1,
                            'expense' => -1
                        ]),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                ]

            );

            Tenant::create(['name' => 'taetaetin', 'domain' => 'taetaetin', 'database' => 'facile.taetaetin', 'type' => 2, 'plan_id' => 2, 'plan_usage' => Plan::find(2)->details->toArray()]);
        }
    }
}
