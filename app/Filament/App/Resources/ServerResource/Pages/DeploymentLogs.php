<?php

namespace App\Filament\App\Resources\ServerResource\Pages;

use App\Filament\App\Resources\ServerResource;
use Filament\Resources\Pages\ManageRelatedRecords;

class DeploymentLogs extends ManageRelatedRecords
{
    protected static string $resource = ServerResource::class;

    protected static string $relationship = 'deployments';

    protected static ?string $title = 'Deployment Logs';

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-exclamation-circle';
    }

    public function getBreadcrumb(): string
    {
        return 'Deployment Logs';
    }

    public function getRelationManagers(): array
    {
        return [
            ServerResource\RelationManagers\DeploymentsRelationManager::class,
        ];
    }
}
