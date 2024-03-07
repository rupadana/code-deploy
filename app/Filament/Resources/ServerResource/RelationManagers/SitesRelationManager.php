<?php

namespace App\Filament\Resources\ServerResource\RelationManagers;

use App\Filament\Resources\SiteResource\Pages\GeneralSites;
use App\Jobs\Concerns\SynchronizeEnvironment;
use App\Jobs\DeploymentJob;
use App\Models\Site;
use App\Services\DeployScript;
use ChrisReedIO\Socialment\Models\ConnectedAccount;
use Filament\Forms;
use Filament\Forms\Components\Checkbox;
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
                        $set('directory', DeployScript::make()->domain($get['domain'])->getSiteDirectory());
                        $set('site-user', DeployScript::make()->domain($get['domain'])->getSiteUser());
                    })
                    ->live(onBlur: true),
                Select::make('repository')
                    ->options($this->getRepositories())
                    ->required()
                    ->searchable(),
                TextInput::make('branch')
                    ->required(),

                Section::make(function (string $operation) {
                    return $operation == 'create' ? 'What kind of site would you like to deploy?' : 'Detail';
                })
                    ->schema([
                        Select::make('project-type')
                            ->options([
                                // 'nodejs' => 'Node.js', TODO: Need some work here
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
                            ->live(),

                        Select::make('template')
                            ->options($this->getTemplates())
                            ->disabledOn('edit')
                            ->searchable()
                            ->hidden(function (Get $get) {
                                // dd($get('project-type'));
                                return !($get('initialize') === true && $get('project-type') === 'php');
                            })
                            ->columns(1),
                        Select::make('version')
                            ->disabledOn('edit')
                            ->options(function (Get $get) {
                                if ($get('project-type') === 'php') {
                                    return collect(DeployScript::PHP_VERSIONS)->mapWithKeys(function ($version) {
                                        return [$version => $version];
                                    });
                                }

                                return [];
                            })
                            ->required()
                            ->hidden(function (Get $get) {
                                return !$get('project-type');
                            }),
                    ])
                    ->columns(2),

                Section::make('Advanced')
                    ->schema([
                        TextInput::make('directory')
                            ->helperText('A custom directory will be used if you initiate the project manually.'),
                        TextInput::make('site-user')
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
        if (!$this->cachedMountedTableActionRecord) {
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

        return Cache::remember('repositories-' . $user->nickname, 86400, function () use ($user) {
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
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data) {
                        $data['created_by'] = auth()->user()->id;

                        return $data;
                    })
                    ->after(function (array $data, Site $record) {
                        // TODO : Use nested Deployment Process on this Process
                        if ($data['initialize']) {
                            try {

                                $server = $record->server;
                                $connectedAccount = $server->owner->connectedAccount;
                                $token = $connectedAccount->token;

                                $apiToken = auth()->user()->createToken('github', ['site:deploy'])->plainTextToken;

                                GithubApi::make($token)
                                    ->repos($record->repository)
                                    ->hooks()
                                    ->post([
                                        'name' => 'web',
                                        'config' => [
                                            'url' => route('api.admin.sites.deploy', [
                                                'id' => $record->id,
                                                'token' => $apiToken,
                                            ]),
                                            'content_type' => 'json',
                                        ],
                                    ]);

                                $process = DeployScript::make()
                                    ->site($record)
                                    ->repositoryUrl("https://{$connectedAccount->nickname}:{$token}@github.com/{$record->repository}.git");

                                if ($data['initialize']) {
                                    $process = $process->initiate(
                                        template: $data['template'],
                                        projectType: $data['project-type'],
                                        version: $data['version']
                                    );
                                }

                                DeploymentJob::dispatch($process, auth()->user());

                                $path = storage_path('private/.env.' . $record->domain . '.' . $record->id);

                                DeploymentJob::dispatch(
                                    DeployScript::make()
                                        ->server($record->server)
                                        ->site($record),
                                    auth()->user(),
                                    execute: SynchronizeEnvironment::make([
                                        'path' => $path,
                                    ])
                                );

                                return Notification::make('notif-1')
                                    ->title('Your site is on deployment process!')
                                    ->body('Please wait for a few minutes')
                                    ->info()
                                    ->persistent()
                                    ->send();
                            } catch (\Exception $exception) {
                                Notification::make('failed-notification')
                                    ->danger()
                                    ->title('Site deployment failed')
                                    ->send();
                            }
                        }
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
        preg_match('/^' . $key . '=(.+)$/m', $envString, $original);

        $escaped = $original[0] ?? $key . '=';

        return preg_replace(
            "/^{$escaped}/m",
            "{$key}={$value}",
            $envString
        );
    }
}
