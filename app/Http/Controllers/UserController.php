<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $validator = Validator::make(request()->only(['role', 'search']), [
            'role' => ['exists:roles,name'],
            'search' => ['string']
        ]);
        $filters = $validator->safe()->only(['role', 'search']);
        $query = User::query()->filter($filters);
        return Inertia::render('Users', [
            'users' => $query->paginate(request()->per_page ?? 20),
            'filters' => $filters,
        ]);
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
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        $validator = Validator::make(request()->only(['search', 'status', 'selected_tags']), [
            'search' => ['string'],
            'status' => ['exists:items,status'],
            'selected_tags' => ['sometimes', 'required', 'string', function ($attribute, $value, $fail) {
                $tag_ids = collect(explode(',', $value));
                if (!$tag_ids->every(fn ($tag_id) => Tag::where('id', $tag_id)->exists())) {
                    $fail('The selected ' . $attribute . ' is invalid.');
                }
            }],
        ]);
        $filters = $validator->safe()->only(['search', 'status', 'selected_tags']);
        if (!array_key_exists('status', $filters)) $filters['status'] = 2;
        $tags = Tag::whereHas('items', function ($query) use ($user) {
            $query->where('merchant_id', $user->id);
        })->get();
        $user->items = User::find($user->id)->items()->filter($filters)->paginate(request()->per_page ?? 20);
        return Inertia::render('User', ['user' => $user, 'filters' => $filters, 'tags' => $tags]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function edit(User $user)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        //
    }
}
