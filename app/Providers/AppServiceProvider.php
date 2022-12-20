<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        // Model::preventAccessingMissingAttributes();

        Password::defaults(function () {
            $rule = Password::min(5);

            return $this->app->isProduction()
                ? $rule->mixedCase()->uncompromised()
                : $rule;
        });

        /* Use https links instead http links */
        if (Request::server('HTTP_X_FORWARDED_PROTO') == 'https') {
            URL::forceScheme('https');
        }
    }
}
