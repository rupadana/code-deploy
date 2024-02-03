<?php

use App\Models\Server;
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

    $server = Server::find(1);
    $host = $server->host;
    $user = $server->user;

    $path = storage_path('private/' . $server->ssh_key_name);
    exec('chmod 600 ' . $path);

    $process = SshSsh::create($user, $host)
        ->disablePasswordAuthentication()
        ->enableQuietMode()
        ->usePrivateKey($path)
        ->configureProcess(
            fn (Process $process) => $process->setTimeout(null)
            // ->setTty(true)
        )
        ->execute([
            "mkdir rups",
        ]);

    // dd($process->getErrorOutput(), $process->isSuccessful());



});


Artisan::command('auth-con', function () {
    $user = ConnectedAccount::first();

    $data = GithubApi::make($user->token)
        ->repos('rupadana/app.codecrafters.id')
        ->commits()
        ->get();

    dd($data);
});
