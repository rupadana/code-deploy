<?php

namespace App\Listeners;

use App\Events\DeploymentNotificationEvent;
use Illuminate\Support\Facades\Http;

class SendDeploymentNotification
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(DeploymentNotificationEvent $event): void
    {
        $content = view('discord.deployment-notification', [
            'site' => $event->site,
            'success' => $event->success,
        ])->render();

        $server = $event->site->server;

        collect($server->notification['webhook'])->each(function (array $webhook) use ($content) {
            Http::post($webhook['url'], [
                'content' => $content,
            ]);
        });
    }
}
