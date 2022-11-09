<?php

namespace Tests;

use App\Models\Payment;
use App\Models\Role;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, RefreshDatabase;
    /**
     * Indicates whether the default seeder should run before each test.
     *
     * @var bool
     */
    protected $seed = false;

    protected $merchant;
    protected $user;
    protected $payment;
    protected $tag;

    public function setUp(): void
    {
        parent::setUp();

        $this->merchant = User::factory()->has(Role::factory()->state(['name' => 'merchant']))->create();
        $this->user = User::factory()->create();
        $this->payment = Payment::factory()->create();
        $this->tag = Tag::factory()->create();
        $this->merchant->payments()->attach($this->payment);
    }
}
