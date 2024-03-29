<?php

namespace App\Filament\App\Resources\ServerResource\Pages;

use App\Filament\App\Resources\ServerResource;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Pages\EditRecord;

class NotificationPage extends EditRecord
{
    protected static string $resource = ServerResource::class;

    protected static ?string $title = 'Notification';

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-bell';
    }

    public function getBreadcrumb(): string
    {
        return 'Notification';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('notification.provider')
                    ->label('Provider')
                    ->options([
                        'discord' => 'Discord',
                    ]),
                Repeater::make('notification.webhook')
                    ->schema([
                        TextInput::make('url')
                            ->url(),
                    ]),

            ])
            ->columns(1);
    }
}
