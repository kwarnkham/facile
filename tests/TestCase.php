<?php

namespace Tests;

use App\Models\Payment;
use App\Models\Role;
use App\Models\Tag;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

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
    protected $tenancy = false;
    protected $payment_type_id;

    public function setUp(): void
    {
        parent::setUp();
        if ($this->tenancy) {
            $this->initializeTenancy();
        }
        $this->user = User::factory()->has(Role::factory()->state(['name' => 'admin']))->create();
        $this->tag = Tag::factory()->create();

        DB::table('payment_types')->insert([
            'name' => 'cash',
            'created_at' => now(),
            'updated_at' => now()
        ]);
        $this->payment_type_id = DB::table('payment_types')->first()->id;
        $this->payment = Payment::factory()->create([
            'payment_type_id' => $this->payment_type_id
        ]);
    }

    public function tearDown(): void
    {
        Tenant::all()->each(fn ($tenant) => $tenant->delete());
    }

    public static function tearDownAfterClass(): void
    {
        // Tenant::all()->each(fn ($tenant) => $tenant->delete());
    }

    public function initializeTenancy()
    {
        $tenant = Tenant::create();
        tenancy()->initialize($tenant);
        URL::defaults(['tenant' => $tenant->id]);
    }
}
