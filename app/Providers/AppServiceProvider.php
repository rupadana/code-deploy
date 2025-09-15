<?php

namespace App\Providers;

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {

        Gate::define('viewPulse', function (User $user) {
            return $user->hasRole('super_admin');
        });

        Sanctum::getAccessTokenFromRequestUsing(function ($request) {

            if ($request->has('token')) {
                return $request->token;
            } elseif ($request->hasHeader('Authorization')) {
                return explode(' ', $request->header('Authorization'))[1];
            }

            return null;
        });

        if ($this->app->environment() === 'production') {
            URL::forceScheme('https');
            
        }
    }
}
