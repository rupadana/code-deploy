<?php

namespace App\Livewire;

use App\Services\DeployScript;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;
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
        $server = $record->server;

        // TODO : Use job 
        $process = DeployScript::make()
            ->server($server)
            ->domain($record->domain)
            ->actAsSiteUser()
            ->toSiteDirectory()
            ->checkoutTo($sha)
            ->script(explode('\n', substr(substr(json_encode($record->script), 1), 0, -1)))
            ->execute();


        if ($process->isSuccessful()) {

            Cache::set('release', $sha, 86400 * 30);

            $record->current_sha = $sha;
            $record->save();

            $this->dispatch('deploy-logs', 'out', $process->getOutput());

            Notification::make('notification')
                ->success()
                ->title('Deployment successfully')
                ->send();
        } else {
            $this->dispatch('deploy-logs', 'out', $process->getErrorOutput());

            Notification::make('notification')
                ->danger()
                ->title('Deployment failed')
                ->send();
        }
    }
}
