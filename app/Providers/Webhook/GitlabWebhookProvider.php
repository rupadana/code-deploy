<?php

namespace App\Providers\Webhook;

use Exception;

class GitlabWebhookProvider extends WebhookProvider
{
    protected string $name = 'gitlab';

    public function handle(): void
    {
        $record = $this->site;
        $request = $this->request;

        if ('refs/heads/'.$record->branch !== $request->ref) {
            throw new Exception('nothing to do', 200);
        }

        if ($request->event_name == 'push') {
            $this->deploy($request->checkout_sha);
        }
    }
}
