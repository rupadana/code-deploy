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
use Filament\Forms\Components\ViewField;
use Filament\Forms\Form;
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

                                Section::make('Deployment')
                                    ->schema([
                                        Textarea::make('script')
                                            ->rows(10)
                                    ])
                                    ->hiddenOn('create')
                                    ->headerActions([
                                        Action::make('deploy')
                                            ->action(function (Site $record) {
                                                $server = $record->server;
                                                $connectedAccount = auth()->user()->connectedAccount;
                                                $token = $connectedAccount->token;

                                                $ssh_private_key_path = storage_path('private/' . $server->ssh_key_name);

                                                $domain = $record->domain;
                                                $siteUser = '';

                                                if (gettype($domain) == 'array') {
                                                    $siteUser = str($domain[0])->replace('.', '-')->toString();
                                                } else {
                                                    $siteUser = str($domain)->replace('.', '-')->toString();
                                                }

                                                $process = Ssh::create($server->user, $server->host)
                                                    ->disablePasswordAuthentication()
                                                    ->enableQuietMode()
                                                    ->usePrivateKey($ssh_private_key_path)
                                                    ->execute([
                                                        "clpctl site:add:php --domainName=$domain --phpVersion=8.2 --vhostTemplate='Laravel 10' --siteUser=$siteUser --siteUserPassword='!secretPassword!'",
                                                        "rm -rf /home/$siteUser/htdocs/$domain",
                                                        "su $siteUser",
                                                        "cd ~/htdocs",
                                                        "git clone https://$connectedAccount->nickname:$token@github.com/$record->repository.git $domain",
                                                        "cd $domain",
                                                        "cp .env.example .env",
                                                        "composer install",
                                                        "php artisan key:generate",
                                                        "exit",
                                                        "clpctl lets-encrypt:install:certificate --domainName=$domain",
                                                        "echo 'successfully initiated project'"
                                                    ]);

                                                Notification::make('notif-1')
                                                    ->title('Your site is now live!')
                                                    ->success()
                                                    ->send();
                                            })
                                    ]),

                                // Section::make('Deployment History')
                                //     ->schema([])


                            ])
                            ->columns(),

                        Tab::make('Commits')
                            ->schema([
                                ViewField::make('commits')
                                    ->view('view-commits')
                                    ->viewData([
                                        'commits' => $this->getCommits()->toArray()
                                    ])
                                    ->columnSpanFull()
                            ])
                            ->hiddenOn('create')
                    ])
                    ->columnSpanFull()



            ]);
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

                                $githubApi = GithubApi::make($token)
                                    ->repos($record->repository)
                                    ->hooks()
                                    ->post([
                                        'name' => 'web',
                                        'config' => [
                                            'url' => "https://demo.codecrafters.id/api/admin/sites/16/deploy?token=2%7CMtgqBeN2IoAcpiTUYE9qOjfAG1GJVu6F2z21LhA134e1b333",
                                            "content_type" => "json",
                                            "insecure_ssl" => "1"
                                        ]
                                    ]);
                                dd($githubApi, route('api.admin.sites.deploy', [
                                    'id' => $record->id,
                                    'token' => '2|MtgqBeN2IoAcpiTUYE9qOjfAG1GJVu6F2z21LhA134e1b333'
                                ]));

                                $domain = $record->domain;
                                // TODO : Use job to deploy
                                $process = DeployScript::make()
                                    ->domain($domain)
                                    ->repositoryUrl("https://$connectedAccount->nickname:$token@github.com/$record->repository.git")
                                    ->initiate($data['initialize'])
                                    ->server($server)
                                    ->execute();

                                if ($process->isSuccessful()) {
                                    return Notification::make('notif-1')
                                        ->title('Your site is now live!')
                                        ->success()
                                        ->send();
                                }

                                return Notification::make('failed-notification')
                                    ->danger()
                                    ->title('Site deployment failed')
                                    ->body($process->getErrorOutput())
                                    ->send();
                            } catch (\Exception $exception) {
                                Notification::make('failed-notification')
                                    ->danger()
                                    ->title('Site deployment failed')
                                    ->body($process->getErrorOutput())
                                    ->send();
                            }
                        }
                    })
                    ->slideOver(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
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
                    }),
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ]);
    }
}
