<?php

namespace App\Filament\App\Resources\SiteResource\Pages;

use App\Filament\App\Resources\SiteResource;
use App\Jobs\Concerns\SynchronizeEnvironment;
use App\Jobs\DeploymentJob;
use App\Models\Site;
use App\Services\DeployScript;
use App\Services\SiteService;
use Filament\Actions\Action as ActionsAction;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Rupadana\GithubApi\GithubApi;

class Repository extends EditRecord
{
    protected static string $resource = SiteResource::class;

    protected static ?string $title = 'Repository';

    protected ?string $repository = '';

    public static function getNavigationIcon(): ?string
    {
        return 'fluentui-branch-24-o';
    }

    public function getBreadcrumb(): string
    {
        return 'Repository';
    }

    public function form(Form $form): Form
    {
        $resource = app($this->getResource());
        $record = $this->getRecord();
        $server = $record->server;

        $this->repository = $record->repository;

        return $form
            ->schema([
                Section::make(__('deploy.repository'))
                    ->schema([
                        Select::make('repository')
                            ->options($resource->getRepositories($server->created_by))
                            ->searchable()
                            ->live(true)
                            ->afterStateUpdated(function (Get $get) {
                                $this->repository = $get('repository');
                            }),
                    ])
                    ->aside()
                    ->description("Change repository if the project did'nt installed by CodeDeploy yet. But, don't change the repository if already installed."),
                Section::make(__('deploy.branch'))
                    ->aside()
                    ->description('CodeDeploy uses this branch to gather details of the latest commit when you deploy your application.')
                    ->schema([
                        TextInput::make('branch')
                            ->required(fn (Get $get) => $get('repository') !== null),
                    ]),
                Section::make(__('deploy.site detail'))
                    ->aside()
                    ->description('CodeDeploy uses this data to deploy your application')
                    ->schema([
                        TextInput::make('directory')
                            ->label(__('deploy.site directory')),
                        TextInput::make('site_user')
                            ->label(__('deploy.site user')),
                    ]),

                Section::make(__('deploy.deploy webhook url'))
                    ->description('You can use this URL to trigger deployments remotely, You can send a "post" request to this URL.')
                    ->schema([
                        TextInput::make('webhook_url')
                            ->disabled(),
                    ])
                    ->aside()
                    ->footerActions([
                        Action::make('generate')
                            ->color('warning')
                            ->action(function (Site $record, Set $set) {
                                $url = SiteService::makeWebHookUrl($record);

                                $set('webhook_url', $url);

                                $record->webhook_url = $url;
                                $record->save();

                                Notification::make()
                                    ->title('Webhook URL Successfully generated!')
                                    ->success()
                                    ->send();
                            }),
                    ]),
            ]);
    }

    public function handleRecordUpdate(Model $record, array $data): Model
    {
        // $data = $this->form->getState();
        $record = $this->getRecord();

        parent::handleRecordUpdate($record, $data);

        if (! ($data['repository'] !== null && $record->repository_installed === 0)) {
            return $record;
        }

        if ($data['repository'] === null) {
            return $record;
        }

        try {

            $server = $record->server;
            $connectedAccount = auth()->user()->connectedAccount;
            $token = $connectedAccount->token;

            $webhookUrl = SiteService::makeWebHookUrl($record);

            GithubApi::make($token)
                ->repos($record->repository)
                ->hooks()
                ->post([
                    'name' => 'web',
                    'config' => [
                        'url' => $webhookUrl,
                        'content_type' => 'json',
                    ],
                ]);

            $process = DeployScript::make()
                ->site($record)
                ->repositoryUrl("https://{$connectedAccount->nickname}:{$token}@github.com/{$record->repository}.git");

            $process = $process
                ->site($record)
                ->initiate(
                    template: $record->template,
                    projectType: $record->{'project-type'},
                    version: $record->version
                );

            DeploymentJob::dispatch($process, auth()->user());

            $path = storage_path('private/.env.'.$record->domain.'.'.$record->id);

            DeploymentJob::dispatch(
                DeployScript::make()
                    ->server($record->server)
                    ->site($record)
                    ->databasePassword($process->getDatabasePassword()),
                auth()->user(),
                execute: SynchronizeEnvironment::make([
                    'path' => $path,
                ])
            );

            $record->repository_installed = 1;
            $record->webhook_url = $webhookUrl;
            $record->save();
        } catch (\Exception $exception) {
            Notification::make('failed-notification')
                ->danger()
                ->title('Site deployment failed')
                ->send();

            throw $exception;
        }

        return $record;
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make('notif-1')
            ->title('Your site is on deployment process!')
            ->body('Please wait for a few minutes')
            ->info()
            ->persistent();
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction(),
            $this->getCancelFormAction(),
        ];
    }

    protected function getSaveFormAction(): ActionsAction
    {
        return ActionsAction::make('save')
            ->label(function (Site $record) {
                if ($this->repository !== null && $record->repository_installed === 0) {
                    return __('deploy.install repository');
                }

                return __('filament-panels::resources/pages/edit-record.form.actions.save.label');
            })
            ->submit('save')
            ->color(function (Site $record) {
                if ($this->repository !== null && $record->repository_installed === 0) {
                    return 'success';
                }

                return 'primary';
            })
            ->keyBindings(['mod+s']);
    }
}
