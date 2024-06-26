<?php

namespace App\Http\Controllers;

use App\Enums\ResponseStatus;
use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Models\Expense;
use App\Models\Picture;
use Illuminate\Support\Facades\Redirect;

class ExpenseController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data = Expense::query()->orderBy('id', 'desc')->paginate(request()->per_page ?? 20);
        return response()->json([
            'data' => $data
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreExpenseRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreExpenseRequest $request)
    {
        $tenant = app('currentTenant');
        abort_if(
            $tenant->plan_usage['expense'] >= 0 &&
                $tenant->plan_usage['expense'] <= Expense::query()->count(),
            ResponseStatus::BAD_REQUEST->value,
            'Your plan only allow maximum of ' . $tenant->plan_usage['expense'] . ' expenses.'
        );
        $attributes = $request->validated();
        $expense = Expense::create($attributes);
        if (request()->wantsJson()) return response()->json(['expense' => $expense]);
    }

    public function record(Expense $expense)
    {
        $tenant = app('currentTenant');
        abort_if(
            $tenant->plan_usage['purchase'] > -1 &&
                $tenant->plan_usage['purchase'] == 0,
            ResponseStatus::BAD_REQUEST->value,
            'Your plan only allow maximum of ' . $tenant->plan->details['purchase'] . ' purchases per day.'
        );

        $attributes = request()->validate([
            'price' => ['required', 'numeric'],
            'note' => ['sometimes', 'required'],
            'picture' => ['sometimes', 'required', 'image']
        ]);
        $attributes['name'] = $expense->name;
        $picture = array_key_exists('picture', $attributes) ? Picture::savePictureInDisk($attributes['picture'], 'purchases') : null;

        if ($picture) {
            $attributes['picture'] = $picture;
        }

        $purcahse = $expense->purchases()->create($attributes);
        if (request()->wantsJson()) return response()->json(['purchase' => $purcahse]);
        return Redirect::back()->with('message', 'Success');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Expense  $expense
     * @return \Illuminate\Http\Response
     */
    public function show(Expense $expense)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Expense  $expense
     * @return \Illuminate\Http\Response
     */
    public function edit(Expense $expense)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateExpenseRequest  $request
     * @param  \App\Models\Expense  $expense
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateExpenseRequest $request, Expense $expense)
    {
        $expense->update($request->validated());
        if ($request->wantsJson()) return response()->json(['expense' => $expense]);
        return Redirect::back()->with('message', 'Success');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Expense  $expense
     * @return \Illuminate\Http\Response
     */
    public function destroy(Expense $expense)
    {
        //
    }
}
