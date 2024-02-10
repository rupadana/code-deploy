<?php

namespace App\Filament\Resources\ServerResource\Pages;

use App\Filament\Resources\ServerResource;
use App\Filament\Resources\ServerResource\RelationManagers\SitesRelationManager;
use Filament\Actions;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Resources\Pages\ViewRecord;

class DeploymentLogs extends ManageRelatedRecords
{
    protected static string $resource = ServerResource::class;

    protected static string $relationship = 'deployments';
    
    protected static ?string $title = "Deployment Logs";

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-exclamation-circle';
    }

    public function getBreadcrumb() : string
    {
        return "Deployment Logs";
    }

    public function getRelationManagers(): array
    {
        return [
            ServerResource\RelationManagers\DeploymentsRelationManager::class
        ];
    }
}
