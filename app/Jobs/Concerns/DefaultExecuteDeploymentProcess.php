<?php

namespace App\Jobs\Concerns;

use App\Services\DeployScript;
use Symfony\Component\Process\Process;

class DefaultExecuteDeploymentProcess extends Abstracts\ExecuteDeploymentProcess
{
    public function handle(DeployScript $script): Process
    {
        return $script->execute();
    }
}
