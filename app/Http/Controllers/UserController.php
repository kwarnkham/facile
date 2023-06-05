<?php

namespace App\Http\Controllers;

use App\Enums\ResponseStatus;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function user()
    {
        return response()->json([
            'user' => request()->user()->load(['roles']),
            'tenant' => app('currentTenant')
        ]);
    }

    public function index()
    {
        $filters = request()->validate(['search' => ['sometimes', 'required']]);
        $query = User::query()->with(['roles'])->filter($filters)->orderBy('id', 'desc');
        return response()->json([
            'data' => $query->paginate(request()->per_page ?? 20)
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
        $attributes = $request->validate([
            'name' => ['required'],
            'username' => ['required', 'unique:tenant.users,username'],
        ]);

        $attributes['password'] = bcrypt('password');

        $user = User::create($attributes);

        return response()->json(['user' => $user->load(['roles'])]);
    }

    public function toggleRole(User $user, Role $role)
    {
        abort_if($role->name == 'admin', ResponseStatus::BAD_REQUEST->value, 'Cannot modify admin role');
        $user->roles()->toggle($role->id);
        return response()->json([
            'user' => $user->load(['roles'])
        ]);
    }

    public function resetPassword(User $user)
    {
        $password = rand(100000, 999999);

        $user->update(['password' => bcrypt($password)]);

        return response()->json([
            'password' => $password
        ]);
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
