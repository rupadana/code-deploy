<?php

namespace App\Filament\App\Resources\ServerResource\Pages;

use App\Filament\App\Resources\ServerResource;
use App\Filament\App\Resources\ServerResource\RelationManagers\SitesRelationManager;
use Filament\Resources\Pages\ManageRelatedRecords;

class ManageSite extends ManageRelatedRecords
{
    protected static string $resource = ServerResource::class;

    protected static string $relationship = 'sites';

    protected static ?string $title = 'Manage Site';

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-globe-alt';
    }

    public function getBreadcrumb(): string
    {
        return 'Manage Site';
    }

    public function getRelationManagers(): array
    {
        return [
            SitesRelationManager::class,
        ];
    }
}
