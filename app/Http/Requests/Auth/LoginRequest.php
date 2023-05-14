<?php

namespace App\Http\Requests\Auth;

use App\Enums\ResponseStatus;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'username' => ['required'],
            'password' => ['required'],
        ];
    }


    public function authenticate(): User
    {
        $this->ensureIsNotRateLimited();


        $user = User::where('username', $this->username)->first();
        if (!$user || !Hash::check($this->password, $user->password ?? null)) {
            RateLimiter::hit($this->throttleKey());
            abort(ResponseStatus::BAD_REQUEST->value, trans('auth.failed'));
        }

        RateLimiter::clear($this->throttleKey());
        return $user;
    }


    public function ensureIsNotRateLimited()
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'username' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }


    public function throttleKey()
    {
        return Str::transliterate(Str::lower($this->input('username')) . '|' . $this->ip());
    }
}
