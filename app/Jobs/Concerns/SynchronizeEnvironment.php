<?php

namespace App\Jobs\Concerns;

use App\Filament\App\Resources\ServerResource\RelationManagers\SitesRelationManager;
use App\Jobs\Concerns\Abstracts\ExecuteDeploymentProcess;
use App\Models\Site;
use App\Services\DeployScript;
use Symfony\Component\Process\Process;

class SynchronizeEnvironment extends ExecuteDeploymentProcess
{
    public function handle(DeployScript $script): Process
    {

        $process = $script->downloadEnv($this->getData('path'));

        $record = Site::find($script->getSite()->id);

        $env = SitesRelationManager::changeEnvVariable(file_get_contents($this->getData('path')), 'DB_DATABASE', $script->getDatabaseName());
        $env = SitesRelationManager::changeEnvVariable($env, 'DB_PASSWORD', $script->getDatabasePassword());
        $env = SitesRelationManager::changeEnvVariable($env, 'DB_USERNAME', $script->getSiteUser());

        file_put_contents($this->getData('path'), $env);
        $record->environment = $env;

        $record->save();

        DeployScript::make()
            ->server($record->server)
            ->site($record)
            ->uploadEnv($this->getData('path'));

        return $process;
    }
}
