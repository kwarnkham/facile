<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;

class ResetPlanUsage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reset:plan';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset plan usage of all tenants';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Tenant::with(['plan'])->get()->each(function ($tenant) {
            $tenant->update(['plan_usage' => $tenant->plan->details]);
        });
    }
}
