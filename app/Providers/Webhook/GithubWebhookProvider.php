<?php

namespace App\Providers\Webhook;

use Exception;

class GithubWebhookProvider extends WebhookProvider
{
    protected string $name = 'github';

    public function handle(): void
    {
        $record = $this->site;
        $request = $this->request;

        if ('refs/heads/' . $record->branch !== $request->ref) {
            throw new Exception('nothing to do, ' . $request->ref . 'ref is not ' . $record->branch, 200);
        }

        if ($request->header('X-GitHub-Event') == 'push') {
            $this->deploy($request->after);
        }
    }
}
