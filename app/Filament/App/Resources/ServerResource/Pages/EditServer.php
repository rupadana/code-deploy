<?php

namespace App\Filament\App\Resources\ServerResource\Pages;

use App\Filament\App\Resources\ServerResource;
use App\Models\Server;
use App\Services\DeployScript;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Colors\Color;

class EditServer extends EditRecord
{
    protected static string $resource = ServerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Action::make('check-connection')
                ->color(Color::Green)
                ->action(function (Server $record) {

                    $process = DeployScript::make($record)
                        ->execute("echo 'connection success'");

                    if ($process->isSuccessful()) {
                        return Notification::make('success-notification')
                            ->success()
                            ->title('Connection successfully')
                            ->send();
                    }

                    return Notification::make('failed-notification')
                        ->danger()
                        ->title('Connection failed')
                        ->body($process->getErrorOutput())
                        ->send();
                }),
        ];
    }
}
