<?php

namespace App\Providers;

use ChrisReedIO\Socialment\Facades\Socialment;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\GithubProvider;

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

        Sanctum::getAccessTokenFromRequestUsing(function ($request) {

            if ($request->has('token')) {
                return $request->token;
            } else if($request->hasHeader('Authorization')) {
                return explode(' ', $request->header('Authorization'))[1];
            }

            return null;
        });
    }
}
