<?php

namespace App\Http\Controllers;

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
        $query = Tenant::query()->orderBy('expires_on')->filter($filters);
        return response()->json([
            'data' => $query->paginate(request()->per_page ?? 30)
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
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
            'days' => ['required', 'numeric', 'gt:0'],
            'price' => ['required', 'numeric', 'gt:0'],
        ]);

        $currentTenant = app('currentTenant');

        $tenant = DB::transaction(function ()  use ($data) {
            $tenant = Tenant::create([
                'name' => $data['name'],
                'domain' => $data['domain'],
                'database' => $data['database'],
                'expires_on' => now()->addDays($data['days'])
            ]);

            $tenant->subscriptions()->create([
                'days' => $data['days'],
                'price' => $data['price'],
            ]);
            return $tenant;
        });

        $currentTenant->makeCurrent();

        return response()->json(['tenant' => $tenant]);
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
        //
    }
}
