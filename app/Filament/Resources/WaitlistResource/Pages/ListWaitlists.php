<?php

namespace App\Filament\Resources\WaitlistResource\Pages;

use App\Filament\Resources\WaitlistResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWaitlists extends ListRecords
{
    protected static string $resource = WaitlistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
