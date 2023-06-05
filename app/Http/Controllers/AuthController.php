<?php

namespace App\Http\Controllers;

use App\Enums\ResponseStatus;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $user =  $request->authenticate();

        $token = $user->createToken('');

        return response()->json([
            'token' => $token->plainTextToken,
            'user' => $user->load(['roles']),
            'tenant' => app('currentTenant')
        ]);
    }

    public function changePassword()
    {
        $attributes = request()->validate([
            'password' => ['required'],
            'new_password' => ['required', 'confirmed'],
        ]);

        $user = request()->user();

        abort_unless(
            Hash::check($attributes['password'], $user->password),
            ResponseStatus::UNAUTHENTICATED->value,
            'Incorrect password'
        );

        $user->update([
            'password' => bcrypt($attributes['new_password'])
        ]);

        return response()->json([
            'message' => 'Password changed'
        ]);
    }

    public function logout()
    {
        $user = request()->user();
        $user->currentAccessToken()->delete();
        return response()->json(['message' => 'Success']);
    }
}
