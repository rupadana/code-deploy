<?php

namespace App\Filament\App\Resources\ServerResource\RelationManagers;

use App\Filament\App\Resources\SiteResource\Pages\GeneralSites;
use App\Jobs\DeploymentJob;
use App\Models\Site;
use App\Services\DeployScript;
use ChrisReedIO\Socialment\Models\ConnectedAccount;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\Action as ActionsAction;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;
use Rupadana\GithubApi\GithubApi;

class SitesRelationManager extends RelationManager
{
    protected static string $relationship = 'sites';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('domain')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull()
                    ->afterStateUpdated(function (Get $get, Set $set) {
                        $set('directory', DeployScript::make()->domain($get('domain'))->getSiteDirectory());
                        $set('site_user', DeployScript::make()->domain($get('domain'))->getSiteUser());
                    })
                    ->live(onBlur: true),

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

                        Select::make('template')
                            ->options($this->getTemplates())
                            ->disabledOn('edit')
                            ->searchable()
                            ->hidden(function (Get $get) {
                                // dd($get('project-type'));
                                return ! ($get('project-type') === 'php');
                            })
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

                Section::make('Advanced')
                    ->schema([
                        TextInput::make('directory')
                            ->helperText('A custom directory will be used if you initiate the project manually.'),
                        TextInput::make('site_user')
                            ->helperText('It will be used to deployment process'),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->columns(),

            ]);
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    protected function getCurrentRecordFromTable()
    {
        return $this->cachedMountedTableActionRecord;
    }

    protected function getCommits()
    {
        if (! $this->cachedMountedTableActionRecord) {
            return collect([]);
        }
        $user = ConnectedAccount::query()->where('user_id', $this->getOwnerRecord()->created_by)->first();

        return GithubApi::make($user->token)
            ->repos($this->cachedMountedTableActionRecord->repository)
            ->commits()
            ->get();
    }

    protected function getTemplates(): array
    {
        return DeployScript::make($this->getOwnerRecord())
            ->getTemplates()
            ->pluck('name', 'name')
            ->toArray();
    }

    protected function getRepositories()
    {
        $user = ConnectedAccount::query()->where('user_id', $this->getOwnerRecord()->created_by)->first();

        return Cache::remember('repositories-'.$user->nickname, 86400, function () use ($user) {
            // TODO: Get repository from organization too

            return GithubApi::make($user->token)
                ->user()
                ->repos()
                ->get([
                    'per_page' => 200,
                ])
                ->pluck('full_name', 'full_name');
        });
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('domain')
            ->columns([
                Tables\Columns\TextColumn::make('domain'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data) {
                        $data['created_by'] = auth()->user()->id;

                        return $data;
                    })
                    ->after(function (Site $record) {
                        $record->owner()->attach($record->server->owner);
                        $record->save();
                    })
                    ->slideOver(),
            ])
            ->actions([
                ActionsAction::make('edit')
                    ->url(fn (Site $record) => GeneralSites::getUrl(['record' => $record]))
                    ->label('Manage site')
                    ->icon('heroicon-o-globe-alt'),
                Tables\Actions\DeleteAction::make()
                    ->after(function (Site $record) {
                        $server = $record->server;

                        $process = DeployScript::make()
                            ->site($record)
                            ->deleteSite()
                            ->server($server);

                        DeploymentJob::dispatch($process, auth()->user());

                        return Notification::make('notif-1')
                            ->title('Your site is on delete process!')
                            ->success()
                            ->send();
                    })
                    ->successNotification(null),
            ]);
    }

    public static function changeEnvVariable(string $envString, string $key, string $value)
    {
        $original = [];
        preg_match('/^'.$key.'=(.+)$/m', $envString, $original);

        $escaped = $original[0] ?? $key.'=';

        return preg_replace(
            "/^{$escaped}/m",
            "{$key}={$value}",
            $envString
        );
    }
}
