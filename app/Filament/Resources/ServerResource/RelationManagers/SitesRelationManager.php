<?php

namespace App\Filament\Resources\ServerResource\RelationManagers;

use App\Models\Server;
use App\Models\Site;
use App\Services\DeployScript;
use ChrisReedIO\Socialment\Models\ConnectedAccount;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\View;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Cache;
use Rupadana\GithubApi\GithubApi;
use Spatie\Ssh\Ssh;

class SitesRelationManager extends RelationManager
{
    protected static string $relationship = 'sites';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Tabs')
                    ->schema([
                        Tab::make('General')
                            ->schema([
                                Forms\Components\TextInput::make('domain')
                                    ->required()
                                    ->maxLength(255),
                                Select::make('repository')
                                    ->options($this->getRepositories())
                                    ->required()
                                    ->searchable(),

                                Checkbox::make('initialize')
                                    ->hiddenOn('edit')
                                    ->columnSpanFull(),

                            ])
                            ->columns(),

                        Tab::make('Deployment')
                            ->schema([
                                Section::make('Deployment')
                                    ->schema([
                                        Textarea::make('script')
                                            ->rows(10),
                                    ])
                                    ->hiddenOn('create')
                                    ->headerActions([
                                        Action::make('dispatch')
                                            ->action(function () {
                                                $this->dispatch('deploy-logs', 'out', 'hello');
                                            }),
                                        Action::make('deploy')
                                            ->action(function (Site $record, Get $get) {

                                                $this->dispatch('deploy-logs', 'out', 'Starting deployment...');
                                                $record->script = $get('script');
                                                $deployScript = DeployScript::make()
                                                    ->server($record->server)
                                                    ->domain($record->domain)
                                                    ->actAsSiteUser()
                                                    ->toSiteDirectory()
                                                    ->gitPull()
                                                    ->script(explode('\n', substr(substr(json_encode($record->script), 1), 0, -1)));
                                                $process = $deployScript->execute();



                                                $notification = Notification::make();

                                                if ($process->isSuccessful()) {
                                                    $this->dispatch('deploy-logs', 'out', $process->getOutput());
                                                    $notification->title('Deployment successfully')
                                                        ->success();
                                                } else {
                                                    $this->dispatch('deploy-logs', 'out', $process->getErrorOutput());
                                                    $notification->title('Deployment failed')
                                                        ->danger();
                                                }


                                                $record->save();

                                                $notification->send();
                                            })
                                    ]),


                                Section::make('Deployment Log')
                                    ->schema([
                                        View::make('view-deployment-logs')
                                            ->viewData([
                                                'listener' => 'deploy-logs'
                                            ])
                                    ]),
                                ViewField::make('commits')
                                    ->view('view-commits')
                                    ->viewData([
                                        'commits' => $this->getCommits()->toArray(),
                                        'record' => $this->getCurrentRecordFromTable()
                                    ])
                                    ->columnSpanFull()
                            ]),
                        // Tab::make('Commits')
                        //     ->schema([
                        //         ,
                        //     ])
                        //     ->hiddenOn('create'),
                        Tab::make('Environment')
                            ->schema([
                                Section::make('.env')
                                    ->description(function (Site $record) {
                                        return $record->environment == null ? "You need to synchronize .env file" : "";
                                    })
                                    ->headerActions([
                                        Action::make('sync-env')
                                            ->label('Sync now')
                                            ->action(function (Site $record, Get $get, Set $set) {

                                                $path = storage_path('private/.env.' . $record->domain . '.' . $record->id);
                                                if ($record->environment) {
                                                    file_put_contents($path, $get('environment'));
                                                    $record->environment = $get('environment');
                                                    $process = DeployScript::make()
                                                        ->server($record->server)
                                                        ->domain($record->domain)
                                                        ->uploadEnv(storage_path('private/.env.' . $record->domain . '.' . $record->id));
                                                    $record->save();
                                                } else {
                                                    $process = DeployScript::make()
                                                        ->server($record->server)
                                                        ->domain($record->domain)
                                                        ->downloadEnv(storage_path('private/.env.' . $record->domain . '.' . $record->id));

                                                    $record->environment = file_get_contents($path);

                                                    $record->save();

                                                    $set('environment', $record->environment);
                                                }

                                                Notification::make()
                                                    ->success()
                                                    ->title('Sync environment successfully')
                                                    ->send();
                                            })
                                    ])
                                    ->schema([
                                        Textarea::make('environment')
                                            ->rows(15)
                                    ])
                            ])
                            ->hiddenOn('create')
                    ])
                    ->columnSpanFull()



            ]);
    }

    protected function getCurrentRecordFromTable()
    {
        return $this->cachedMountedTableActionRecord;
    }

    protected function getCommits()
    {
        if (!$this->cachedMountedTableActionRecord) return collect([]);
        $user = ConnectedAccount::query()->where('user_id', auth()->user()->id)->first();


        $data = GithubApi::make($user->token)
            ->repos($this->cachedMountedTableActionRecord->repository)
            ->commits()
            ->get();

        return $data;
    }

    protected function getRepositories()
    {
        $user = ConnectedAccount::query()->where('user_id', auth()->user()->id)->first();

        return Cache::remember('repositories-' . $user->nickname, 86400, function () use ($user) {
            $data = GithubApi::make($user->token)
                ->user()
                ->repos()
                ->get([
                    'per_page' => 200
                ])
                ->pluck('full_name', 'full_name');

            return $data;
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
                    ->after(function (array $data, Site $record) {
                        if ($data['initialize']) {
                            try {
                                $server = $record->server;
                                $connectedAccount = $server->owner->connectedAccount;
                                $token = $connectedAccount->token;

                                $apiToken = auth()->user()->createToken('github', ['site:deploy'])->plainTextToken;

                                $githubApi = GithubApi::make($token)
                                    ->repos($record->repository)
                                    ->hooks()
                                    ->post([
                                        'name' => 'web',
                                        'config' => [
                                            'url' => route('api.admin.sites.deploy', [
                                                'id' => $record->id,
                                                'token' => $apiToken
                                            ]),
                                            "content_type" => "json",
                                            "insecure_ssl" => "1"
                                        ]
                                    ]);


                                $domain = $record->domain;
                                // TODO : Use job to deploy
                                $process = DeployScript::make()
                                    ->domain($domain)
                                    ->repositoryUrl("https://$connectedAccount->nickname:$token@github.com/$record->repository.git");

                                if ($data['initialize']) {
                                    $process = $process->initiate();
                                }

                                $postProcess = $process
                                    ->server($server)
                                    ->execute();

                                file_put_contents(storage_path('private/error-post-process.log'), $postProcess->getErrorOutput());

                                $path = storage_path('private/.env.' . $record->domain . '.' . $record->id);

                                DeployScript::make()
                                    ->server($record->server)
                                    ->domain($record->domain)
                                    ->downloadEnv($path);


                                $env = SitesRelationManager::changeEnvVariable(file_get_contents($path), 'DB_DATABASE', $process->getDatabaseName());

                                $env = SitesRelationManager::changeEnvVariable($env, 'DB_PASSWORD', $process->getDatabasePassword());
                                $env = SitesRelationManager::changeEnvVariable($env, 'DB_USERNAME', $process->getSiteUser());

                                file_put_contents($path, $env);
                                $record->environment = $env;

                                $record->save();

                                DeployScript::make()
                                    ->server($record->server)
                                    ->domain($record->domain)
                                    ->uploadEnv($path);


                                if ($postProcess->isSuccessful()) {
                                    return Notification::make('notif-1')
                                        ->title('Your site is now live!')
                                        ->success()
                                        ->send();
                                }

                                return Notification::make('failed-notification')
                                    ->danger()
                                    ->title('Site deployment failed')
                                    ->body($postProcess->getErrorOutput())
                                    ->send();
                            } catch (\Exception $exception) {
                                dd($exception);
                                Notification::make('failed-notification')
                                    ->danger()
                                    ->title('Site deployment failed')
                                    ->body($postProcess->getErrorOutput())
                                    ->send();
                            }
                        }
                    })
                    ->slideOver(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Manage site')
                    ->slideOver(),
                Tables\Actions\DeleteAction::make()
                    ->after(function (Site $record) {

                        $domain = $record->domain;
                        $server = $record->server;

                        // TODO : Use job to deploy 
                        $process = DeployScript::make()
                            ->domain($domain)
                            ->deleteSite()
                            ->server($server)
                            ->execute();

                        if ($process->isSuccessful()) {
                            return Notification::make('notif-1')
                                ->title('Your site successfully delete')
                                ->success()
                                ->send();
                        }

                        return Notification::make('failed-notification')
                            ->danger()
                            ->title('Site failed deleted')
                            ->body($process->getErrorOutput())
                            ->send();
                    })
                    ->successNotification(null),
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ]);
    }

    public static function changeEnvVariable(string $envString, string $key, string $value)
    {
        $original = [];
        preg_match('/^' . $key . '=(.+)$/m', $envString, $original);

        $escaped = isset($original[0]) ? $original[0] : $key . '=';


        return preg_replace(
            "/^$escaped/m",
            "{$key}={$value}",
            $envString
        );
    }
}
