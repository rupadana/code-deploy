<?php

namespace App\Jobs\Concerns\Abstracts;

use App\Jobs\Concerns\Abstracts\DeploymentProcess;
use App\Services\DeployScript;
use Symfony\Component\Process\Process;

class ExecuteDeploymentProcess extends DeploymentProcess
{

    public function handle(DeployScript $script): Process
    {
        return $script->execute();
    }
}
