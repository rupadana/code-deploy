<?php

namespace App\Notifications;

class Notification extends \Filament\Notifications\Notification
{
    protected ?string $deploymentId = null;

    public function getDeploymentId(): ?string
    {
        return $this->deploymentId;
    }

    public function deploymentId(?string $deploymentId): Notification
    {
        $this->deploymentId = $deploymentId;

        return $this;
    }

    public function getDatabaseMessage(): array
    {
        $data = $this->toArray();
        $data['duration'] = 'persistent';
        $data['format'] = 'filament';
        $data['deployment-id'] = $this->getDeploymentId();
        unset($data['id']);

        return $data;
    }
}
