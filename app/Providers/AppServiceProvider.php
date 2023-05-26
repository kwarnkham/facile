<?php

namespace App\Providers;

use App\Models\PersonalAccessToken;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Model::unguard();
        Model::preventLazyLoading();
        Model::preventAccessingMissingAttributes();

        Password::defaults(function () {
            $rule = Password::min(5);
            return App::isProduction()
                ? $rule->mixedCase()->uncompromised()
                : $rule;
        });

        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);


        // DB::listen(function ($query) {
        //     Log::info($query->sql);
        //     Log::info($query->bindings);
        //     // $query->time;
        // });
    }
}
