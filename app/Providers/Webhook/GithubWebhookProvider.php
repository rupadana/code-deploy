<?php

namespace App\Providers\Webhook;

use App\Jobs\Concerns\SetSiteSha;
use App\Jobs\DeploymentJob;
use App\Providers\Webhook\WebhookProvider;
use App\Services\DeployScript;
use Exception;

class GithubWebhookProvider extends WebhookProvider
{
    protected string $name = 'github';

    public function handle(): void
    {
        $record = $this->site;
        $request = $this->request;

        if ('refs/heads/' . $record->branch !== $request->ref) {
            throw new Exception('nothing to do', 200);
        }

        if ($request->header('X-GitHub-Event') == 'push') {
            $this->deploy($request->ref);
        }
    }
}
