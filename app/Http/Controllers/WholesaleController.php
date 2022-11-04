<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWholesaleRequest;
use App\Http\Requests\UpdateWholesaleRequest;
use App\Models\Wholesale;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class WholesaleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreWholesaleRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreWholesaleRequest $request)
    {
        Wholesale::create($request->validated());
        return Redirect::back()->with('message', 'Success');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Wholesale  $wholesale
     * @return \Illuminate\Http\Response
     */
    public function show(Wholesale $wholesale)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Wholesale  $wholesale
     * @return \Illuminate\Http\Response
     */
    public function edit(Wholesale $wholesale)
    {
        return Inertia::render('EditWholesale', ['wholesale' => $wholesale->load(['item'])]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateWholesaleRequest  $request
     * @param  \App\Models\Wholesale  $wholesale
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateWholesaleRequest $request, Wholesale $wholesale)
    {
        $wholesale->update($request->validated());
        return Redirect::route('items.edit', ['item' => $wholesale->item_id, 'edit' => 'wholesales'])->with('message', 'Updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Wholesale  $wholesale
     * @return \Illuminate\Http\Response
     */
    public function destroy(Wholesale $wholesale)
    {
        //
    }
}
