<?php

namespace App\Jobs;

use App\Events\DeploymentNotificationEvent;
use App\Jobs\Concerns\Abstracts\DeploymentProcess;
use App\Jobs\Concerns\Abstracts\ExecuteDeploymentProcess;
use App\Jobs\Concerns\DefaultExecuteDeploymentProcess;
use App\Models\Deployment;
use App\Models\User;
use App\Notifications\Notification;
use App\Services\DeployScript;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\Process\Process;

class DeploymentJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected ?Deployment $deployment;

    protected Process $process;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected DeployScript $script,
        protected User $user,
        protected ?ExecuteDeploymentProcess $execute = null,
        protected ?DeploymentProcess $finish = null,
    ) {
        if ($this->execute === null) {
            $this->execute = DefaultExecuteDeploymentProcess::make();
        }

        $this->deployment = Deployment::create([
            'server_id' => $this->script->getServer()->id,
            'site_id' => $this->script->getSite()->id,
        ]);

        $this->sendPendingNotification();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->process = $this->execute->handle($this->script);

        $this->deletePendingNotification();

        if ($this->process->isSuccessful()) {
            $this->deployment->log = $this->process->getErrorOutput()."\n ".$this->process->getOutput();
            $this->deployment->status = 'success';
            $this->deployment->save();

            $this->sendSuccessNotification();
        } else {
            $this->deployment->log = $this->process->getErrorOutput()."\n ".$this->process->getOutput();
            $this->deployment->status = 'failure';
            $this->deployment->save();

            $this->sendErrorNotification();
        }

        if ($this->finish) {
            $this->finish->handle($this->script);
        }

    }

    protected function sendPendingNotification(): void
    {
        \App\Notifications\Notification::make('sd')
            ->icon('heroicon-o-arrow-path')
            ->title('Deployment in progress')
            ->duration('1m')
            ->body('Site :  '.$this->script->getDomain())
            ->deploymentId($this->deployment->id)
            ->sendToDatabase($this->user);
    }

    /**
     * Delete pending notification
     */
    protected function deletePendingNotification(): void
    {
        \Illuminate\Notifications\DatabaseNotification::query()->where('data->deployment-id', $this->deployment->id)->delete();
    }

    protected function sendSuccessNotification(): void
    {
        event(new DeploymentNotificationEvent($this->script->getSite()));
        Notification::make()
            ->success()
            ->title('Deployment successfully')
            ->body('Site : '.$this->script->getDomain())
            ->deploymentId($this->deployment->id)
            ->sendToDatabase($this->user);
    }

    protected function sendErrorNotification(): void
    {
        event(new DeploymentNotificationEvent($this->script->getSite(), false));
        Notification::make()
            ->danger()
            ->title('Deployment failed')
            ->body('Site : '.$this->script->getDomain())
            ->deploymentId($this->deployment->id)
            ->sendToDatabase($this->user);
    }
}
