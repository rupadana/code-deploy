<?php

use App\Filament\Resources\ServerResource\RelationManagers\SitesRelationManager;
use App\Models\Server;
use App\Services\DeployScript;
use ChrisReedIO\Socialment\Models\ConnectedAccount;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Laravel\Envoy\SSH;
use Laravel\Envoy\Task;
use Rupadana\GithubApi\GithubApi;
use Spatie\Ssh\Ssh as SshSsh;
use Symfony\Component\Process\Process;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


Artisan::command('test', function () {

//    $data = \App\Notifications\Notification::make('sd')
//        ->icon('heroicon-o-arrow-path')
//        ->actions([
//        ])
//        ->title('Deployment in progress')
//        ->body('Site : app.codecrafters.id')
//        ->deploymentId(uniqid())
//        ->sendToDatabase(\App\Models\User::find(1));


    // $key = 'APP_DEBUG';
    // $value = 'Hello world';

    // $envPath = '/Users/rupadana/Freelance/app.codecrafters.id/storage/private/.env.demo123.codecrafters.id.39';
    // $envString = file_get_contents($envPath);

    // // $env = SitesRelationManager::changeEnvVariable($envString, 'DB_DATABASE', $value);
    // $env = SitesRelationManager::changeEnvVariable($envString, 'DB_PASSWORD', $value);
    // // $env = SitesRelationManager::changeEnvVariable($env, 'DB_USERNAME', $value);
    // dd($env);

    $server = Server::first();
//    // dd($server);
//    $process = DeployScript::make()
//        ->server($server)
//        ->site(\App\Models\Site::find(41))
//        ->script([
//            'echo "Hello World"',
//            'echo "Hello World 2"',
//            'echo "Hello World 3"',
//            'echo "Hello World 4"',
//            'echo "Hello World 5"',
//            'echo "Hello World 6"',
//            'echo "Hello World 7"',
//            'echo "Hello World"',
//        ]);


    $path = storage_path('private/.env.' . 'testkuiy' . '.' . 'sd');
    // TODO : Use job to deploy
    $process = DeployScript::make()
        ->server($server)
        ->site(\App\Models\Site::find(41))
        ->script('echo "hello"');

    \App\Jobs\DeploymentJob::dispatch($process, \App\Models\User::find(1));


    // ->execute();
    // dd($process->getScript());
    // DeployScript::make()
    //     ->server($server)
    //     ->domain($record->domain)
    //     ->actAsSiteUser()
    //     ->toSiteDirectory()
    // ->checkoutTo('11ab9553f46f9470134e75fe4bb30f55b815b366');
});


Artisan::command('auth-con', function () {
    $user = ConnectedAccount::first();

    $data = GithubApi::make($user->token)
        ->repos('rupadana/app.codecrafters.id')
        ->commits()
        ->get();
});


if (!function_exists('put_permanent_env')) {
    function put_permanent_env($key, $value)
    {
        $path = app()->environmentFilePath();

        if (gettype(env($key)) === 'boolean') {
            $bool = env($key) ? 'true' : 'false';
            $escaped = preg_quote('=' . $bool, '/');
        } else {
            $escaped = preg_quote('=' . env($key), '/');
        }

        file_put_contents($path, preg_replace(
            "/^{$key}{$escaped}/m",
            "{$key}={$value}",
            file_get_contents($path)
        ));
    }
}
