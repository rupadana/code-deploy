<?php

namespace App\Jobs;

use App\Filament\App\Resources\ServerResource\RelationManagers\SitesRelationManager;
use App\Models\Site;
use App\Services\DeployScript;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ChangeEnvironmentSite implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(protected DeployScript $script, protected string $path)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $record = Site::find($this->script->getSite()->id);

        $env = SitesRelationManager::changeEnvVariable(file_get_contents($this->path), 'DB_DATABASE', $this->script->getDatabaseName());
        $env = SitesRelationManager::changeEnvVariable($env, 'DB_PASSWORD', $this->script->getDatabasePassword());
        $env = SitesRelationManager::changeEnvVariable($env, 'DB_USERNAME', $this->script->getSiteUser());

        file_put_contents($this->path, $env);
        $record->environment = $env;

        $record->save();
    }
}
