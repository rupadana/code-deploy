<?php

namespace App\Enums;

enum DeploymentStatus: string implements \Filament\Support\Contracts\HasColor
{
    case PENDING = 'pending';
    case RUNNING = 'running';
    case FAILURE = 'failure';
    case SUCCESS = 'success';

    public function getColor(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::RUNNING => 'warning',
            self::FAILURE => 'danger',
            self::SUCCESS => 'success',
        };
    }
}
