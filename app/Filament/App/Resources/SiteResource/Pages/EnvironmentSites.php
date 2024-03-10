<?php

namespace App\Filament\App\Resources\SiteResource\Pages;

use App\Filament\App\Resources\SiteResource;
use App\Models\Site;
use App\Services\DeployScript;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EnvironmentSites extends EditRecord
{
    protected static string $resource = SiteResource::class;

    protected static ?string $title = 'Environment';

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-document';
    }

    public function getBreadcrumb(): string
    {
        return 'Environment';
    }

    protected function getFormActions(): array
    {
        return [];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('.env')
                    ->description(function (Site $record) {
                        return $record->environment == null ? 'You need to synchronize .env file' : '';
                    })
                    ->headerActions([
                        Action::make('sync-env')
                            ->label('Sync now')
                            ->action(function (Site $record, Get $get, Set $set) {

                                $path = storage_path('private/.env.'.$record->domain.'.'.$record->id);

                                if ($record->environment) {
                                    file_put_contents($path, $get('environment'));
                                    $record->environment = $get('environment');
                                    $process = DeployScript::make()
                                        ->site($record)
                                        ->uploadEnv($path);
                                    $record->save();
                                } else {
                                    $process = DeployScript::make()
                                        ->site($record)
                                        ->downloadEnv($path);

                                    $record->environment = file_get_contents($path);

                                    $record->save();

                                    $set('environment', $record->environment);
                                }

                                Notification::make()
                                    ->success()
                                    ->title('Sync environment successfully')
                                    ->send();
                            }),
                    ])
                    ->schema([
                        Textarea::make('environment')
                            ->rows(15),
                    ]),
            ]);
    }
}
