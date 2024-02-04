<?php

namespace App\Filament\Resources\ServerResource\Pages;

use App\Filament\Resources\ServerResource;
use App\Filament\Resources\ServerResource\RelationManagers\SitesRelationManager;
use App\Infolists\Components\SshPubView;
use Filament\Actions;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Pages\Page;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Storage;

class ManageSite extends ViewRecord
{
    protected static string $resource = ServerResource::class;
    protected static ?string $title = "Manage Site";

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-globe-alt';
    }

    public function getBreadcrumb() : string
    {  
        return "Manage Site";
    }


    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Grid::make([
                    'default' => 1,
                    'sm' => 2,
                    'md' => 3,
                    'lg' => 4,
                    'xl' => 6,
                    '2xl' => 8,
                ])
            ]);
    }

    public function getRelationManagers(): array
    {
        return [
            SitesRelationManager::class
        ];
    }
}
