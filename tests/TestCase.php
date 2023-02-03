<?php

namespace Tests;

use App\Models\Payment;
use App\Models\Role;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, RefreshDatabase;
    /**
     * Indicates whether the default seeder should run before each test.
     *
     * @var bool
     */
    protected $seed = false;

    protected $user;
    protected $payment;
    protected $tag;
    protected $payment_type_id;
    protected $payment_type_id_2;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()
            ->has(Role::factory()->state(['name' => 'admin']))
            ->has(Role::factory()->state(['name' => 'sale']))
            ->create([
                'email' => 'admin@gmail.com'
            ]);
        $this->tag = Tag::factory()->create();

        DB::table('payment_types')->insert([
            'name' => 'cash',
            'created_at' => now(),
            'updated_at' => now()
        ]);
        $this->payment_type_id = DB::table('payment_types')->first()->id;

        DB::table('payment_types')->insert([
            'name' => 'other',
            'created_at' => now(),
            'updated_at' => now()
        ]);
        $this->payment_type_id_2 = DB::table('payment_types')->where('name', 'other')->first()->id;

        $this->payment = Payment::factory()->create([
            'payment_type_id' => $this->payment_type_id
        ]);
    }
}
