<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TenantController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $filters = request()->validate([
            'search' => [''],
            'expired' => ['boolean'],
            'active' => ['boolean']
        ]);
        $query = Tenant::query()
            ->with(['plan'])
            ->where('type', 1)
            ->orderBy('expires_on')
            ->filter($filters);
        return response()->json([
            'data' => $query->paginate(request()->per_page ?? 30)
        ]);
    }

    public function renewSubscription(Tenant $tenant)
    {
        $data = request()->validate([
            'days' => ['required', 'numeric', 'gte:0'],
            'price' => ['required', 'numeric', 'gte:0'],
            'plan_id' => ['required', Rule::exists('mysql.plans', 'id')]
        ]);


        DB::connection('mysql')->transaction(function () use ($tenant, $data) {
            $tenant->subscriptions()->create([...$data, 'customer' => $tenant->name]);
            $updateData = [
                'expires_on' => $tenant->expires_on->addDays($data['days']),
                'plan_id' => $data['plan_id']
            ];
            if ($data['plan_id'] != $tenant->plan_id) $updateData['plan_usage'] = Plan::find($data['plan_id'])->details;
            $tenant->update($updateData);
        });

        return response()->json([
            'tenant' => $tenant->load(['plan'])
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', Rule::unique('mysql.tenants', 'name')],
            'domain' => ['required', Rule::unique('mysql.tenants', 'domain')],
            'database' => ['required', Rule::unique('mysql.tenants', 'database')],
            'days' => ['required', 'numeric', 'gte:0'],
            'price' => ['required', 'numeric', 'gte:0'],
            'plan_id' => ['required', Rule::exists('mysql.plans', 'id')]
        ]);

        $tenant = DB::transaction(function ()  use ($data) {
            $tenant = Tenant::create([
                'name' => $data['name'],
                'domain' => $data['domain'],
                'database' => $data['database'],
                'plan_id' => $data['plan_id'],
                'plan_usage' => Plan::find($data['plan_id'])->details,
                'expires_on' => now()
                    ->addDays($data['days'])
                    ->endOfDay()
                    ->subHours(6)
                    ->subMinutes(30)
            ]);

            $tenant->subscriptions()->create([
                'days' => $data['days'],
                'price' => $data['price'],
                'customer' => $tenant->name,
                'plan_id' => $data['plan_id']
            ]);
            return $tenant;
        });

        return response()->json(['tenant' => $tenant->fresh(['plan'])]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Tenant $tenant)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Tenant $tenant)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Tenant $tenant)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Tenant $tenant)
    {
        $tenant->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
