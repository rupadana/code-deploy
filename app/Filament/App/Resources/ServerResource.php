<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\ServerResource\Pages;
use App\Filament\App\Resources\ServerResource\Pages\ManageSite;
use App\Models\Server;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ServerResource extends Resource
{
    protected static ?string $model = Server::class;

    protected static ?string $navigationIcon = 'heroicon-o-server-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required(),
                TextInput::make('user')
                    ->required(),
                TextInput::make('host')
                    ->required(),
                TextInput::make('ssh_port')
                    ->label('SSH Port')
                    ->default(22)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('host')
                    ->searchable()
                    ->sortable(),

            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Action::make('manage-site')
                    ->label('Site')
                    ->url(fn (Server $record): string => ManageSite::getUrl(['record' => $record]))
                    ->icon('heroicon-o-globe-alt'),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServers::route('/'),
            'create' => Pages\CreateServer::route('/create'),
            'view' => Pages\ViewServer::route('/{record}'),
            'edit' => Pages\EditServer::route('/{record}/edit'),
            'site' => Pages\ManageSite::route('/{record}/site'),
            'deploy-logs' => Pages\DeploymentLogs::route('/{record}/deploy-logs'),
            'notification' => Pages\NotificationPage::route('/{record}/notification'),
        ];
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\EditServer::class,
            Pages\ViewServer::class,
            Pages\ManageSite::class,
            Pages\DeploymentLogs::class,
            Pages\NotificationPage::class,
        ]);
    }
}
