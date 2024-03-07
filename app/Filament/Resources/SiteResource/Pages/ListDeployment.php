<?php

namespace App\Filament\Resources\SiteResource\Pages;

use App\Filament\Resources\SiteResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ListDeployment extends ManageRelatedRecords
{
    protected static string $resource = SiteResource::class;

    protected static string $relationship = 'deployment';

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-circle';

    protected static ?string $title = 'Logs';

    public function getBreadcrumb(): string
    {
        return 'Logs';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('id')
                    ->required()
                    ->label('Deployment ID')
                    ->maxLength(255),
                Forms\Components\Textarea::make('log')
                    ->columnSpanFull()
                    ->rows(15),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Deployment ID'),
                Tables\Columns\TextColumn::make('status')
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
