<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreToppingRequest;
use App\Http\Requests\UpdateToppingRequest;
use App\Models\Topping;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class ToppingController extends Controller
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
        $toppings = Topping::all();
        return Inertia::render('CreateTopping', ['toppings' => $toppings]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreToppingRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreToppingRequest $request)
    {
        $attributes = $request->validated();
        Topping::create($attributes);

        return Redirect::back()->with('message', 'Success');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Topping  $topping
     * @return \Illuminate\Http\Response
     */
    public function show(Topping $topping)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Topping  $topping
     * @return \Illuminate\Http\Response
     */
    public function edit(Topping $topping)
    {
        return Inertia::render('EditTopping', ['topping' => $topping]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateToppingRequest  $request
     * @param  \App\Models\Topping  $topping
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateToppingRequest $request, Topping $topping)
    {
        $topping->update($request->validated());
        return Redirect::back()->with('message', 'Success');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Topping  $topping
     * @return \Illuminate\Http\Response
     */
    public function destroy(Topping $topping)
    {
        //
    }
}
