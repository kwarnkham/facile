<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    /**
     * Indicates whether the default seeder should run before each test.
     *
     * @var bool
     */
    protected $seed = true;

    protected $merchant;
    protected $user;

    public function setUp(): void
    {
        parent::setUp();
        $this->merchant = User::factory()->create();
        $this->merchant->roles()->attach(2);
        $this->user = User::factory()->create();
    }
}
