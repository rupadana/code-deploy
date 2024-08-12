<?php

namespace App\Livewire;

use App\Jobs\Concerns\SetSiteSha;
use App\Jobs\DeploymentJob;
use App\Services\DeployScript;
use Filament\Notifications\Notification;
use Livewire\Component;

class CommitsTable extends Component
{
    public $commits;

    public $record;

    public function render()
    {
        return view('livewire.commits-table');
    }

    public function deploy($sha)
    {
        $record = $this->record;

        $script = DeployScript::make()
            ->site($record)
            ->actAsSiteUser()
            ->toSiteDirectory()
            ->gitStash()
            ->checkoutTo($sha)
            ->script(explode('\n', substr(substr(json_encode($record->script), 1), 0, -1)));

        DeploymentJob::dispatch($script, auth()->user(), finish: SetSiteSha::make(['sha' => $sha]));

        Notification::make('notification')
            ->info()
            ->title('Your Deployment is in progress')
            ->body('Please wait for a minute')
            ->send();
    }
}
