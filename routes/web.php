<?php

use ChrisReedIO\Socialment\Models\ConnectedAccount;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GithubProvider;
use Laravel\Socialite\Two\User;
use Rupadana\GithubApi\GithubApi;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/




Route::group(['middleware' => ['web']], function () {
    Route::get('/', function () {

        $user = Socialite::driver('github')->userFromToken(ConnectedAccount::query()->where('user_id', auth()->user()->id)->first()->token);
        

        $github = GithubApi::make(ConnectedAccount::query()->where('user_id', auth()->user()->id)->first()->token);

        dd($github->commits('app.codecrafters.id')->first());


        $name = $user->getNickname();
        $token = $user->token;
        // We generate the url for curl
        $curl_url = 'https://api.github.com/repos/' . $name . '/app.codecrafters.id/commits';

        // We generate the header part for the token
        $curl_token_auth = 'Authorization: token ' . $token;

        // We make the actuall curl initialization
        $ch = curl_init($curl_url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        // We set the right headers: any user agent type, and then the custom token header part that we generated
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('User-Agent: Awesome-Octocat-App', $curl_token_auth));

        // We execute the curl
        $output = curl_exec($ch);

        // And we make sure we close the curl       
        curl_close($ch);

        // Then we decode the output and we could do whatever we want with it
        $output = json_decode($output);
        dd($output);
    });
});
