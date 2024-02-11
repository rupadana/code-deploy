<?php

namespace App\Filament\Resources\WaitlistResource\Pages;

use App\Filament\Resources\WaitlistResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWaitlist extends EditRecord
{
    protected static string $resource = WaitlistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
