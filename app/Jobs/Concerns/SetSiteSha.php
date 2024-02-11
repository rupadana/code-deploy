<?php

namespace App\Jobs\Concerns;

use App\Jobs\Concerns\Abstracts\DeploymentProcess;
use App\Models\Site;
use App\Services\DeployScript;

class SetSiteSha extends DeploymentProcess
{
    public function handle(DeployScript $script): void
    {
        $record = Site::find($script->getSite()->id);

        $record->current_sha = $this->data['sha'];

        $record->save();
    }
}
