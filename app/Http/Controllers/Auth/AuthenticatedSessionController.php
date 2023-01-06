<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     *
     * @return \Inertia\Response
     */
    public function create()
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     *
     * @param  \App\Http\Requests\Auth\LoginRequest  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(LoginRequest $request)
    {

        $request->authenticate();

        if ($request->wantsJson()) {
            $user = User::where('email', $request->email)->first();
            $token = $user->createToken('');
            return response()->json(['token' => $token->plainTextToken, 'user' => $user]);
        }

        $request->session()->regenerate();

        return redirect()->intended(RouteServiceProvider::HOME);
    }

    /**
     * Destroy an authenticated session.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request)
    {
        if ($request->wantsJson()) {
            $user = $request->user();
            $user->currentAccessToken()->delete();
            return response()->json(['message' => 'Success']);
        }
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    public function changePassword()
    {

        $attributes = request()->validate([
            'password' => ['required'],
            'new_password' => ['required', 'confirmed'],
        ]);
        $message = 'Incorrect Password';
        $user = request()->user();

        if (Hash::check($attributes['password'], $user->password)) {
            $user->password = bcrypt($attributes['new_password']);
            $user->save();
            $message = 'Success';
        }
        return Redirect::back()->with('message', $message);
    }

    public function editPassword()
    {
        return Inertia::render('ChangePassword');
    }
}
