<?php

namespace App\Filament\App\Resources\SiteResource\Pages;

use App\Filament\App\Resources\SiteResource;
use App\Models\Site;
use App\Services\DeployScript;
use App\Services\SiteService;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class GeneralSites extends EditRecord
{
    protected static string $resource = SiteResource::class;

    protected static ?string $title = 'General';

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-cog-6-tooth';
    }

    public function getBreadcrumb(): string
    {
        return 'General';
    }

    public function form(Form $form): Form
    {
        $resource = app($this->getResource());
        $server = $this->getRecord()->server;

        return $form
            ->schema([
                TextInput::make('domain')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                Section::make(function (string $operation) {
                    return $operation == 'create' ? 'What kind of site would you like to deploy?' : 'Detail';
                })
                    ->schema([
                        Select::make('project-type')
                            ->options([
                                'nodejs' => 'Node.js', // TODO: Need some work here
                                'php' => 'PHP',
                            ])
                            ->live()
                            ->columnSpanFull()
                            ->required(function (string $operation) {
                                return $operation == 'create';
                            })
                            ->disabledOn('edit'),

                        Checkbox::make('initialize')
                            ->columnSpanFull()
                            ->disabledOn('edit')
                            ->hiddenOn('edit')
                            ->live(),

                        Select::make('template')
                            ->options($resource->getTemplates($server))
                            ->disabledOn('edit')
                            ->searchable()
                            ->columns(1),
                        Select::make('version')
                            ->disabledOn('edit')
                            ->options(function (Get $get) {
                                switch ($get('project-type')) {
                                    case 'php':
                                        $options = collect(DeployScript::PHP_VERSIONS)->mapWithKeys(function ($version) {
                                            return [$version => $version];
                                        });
                                        break;
                                    case 'nodejs':
                                        $options = collect(DeployScript::NODE_VERSIONS)->mapWithKeys(function ($version) {
                                            return [$version => $version];
                                        });
                                        break;

                                    default:
                                        $options = [];
                                        break;
                                }

                                return $options;
                            })
                            ->required()
                            ->hidden(function (Get $get) {
                                return ! $get('project-type');
                            }),
                    ])
                    ->columns(2),

            ]);
    }

    protected function getWebhookUrlAction()
    {
        return Action::make('webhook-url')
            ->color('success')
            ->action(function (Site $record) {
                $url = SiteService::makeWebHookUrl($record);
                Notification::make()
                    ->title('Webhook URL Successfully generated!')
                    ->body($url)
                    ->success()
                    ->persistent()
                    ->send();
            });
    }
}
