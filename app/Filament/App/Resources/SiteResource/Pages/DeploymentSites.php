<?php

namespace App\Filament\App\Resources\SiteResource\Pages;

use App\Filament\App\Resources\SiteResource;
use App\Models\Site;
use App\Services\DeployScript;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\View;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class DeploymentSites extends EditRecord
{
    protected static string $resource = SiteResource::class;

    protected static ?string $title = 'Deployment';

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-arrow-up-circle';
    }

    public function getBreadcrumb(): string
    {
        return 'Deployment';
    }

    public function getCurrentFormSchema()
    {
        $resource = app($this->getResource());
        $record = $this->getRecord();

        $components =
            [
                Section::make(__('deploy.deployment'))
                    ->schema([
                        Textarea::make('script')
                            ->label(__('deploy.script'))
                            ->rows(10),
                    ])
                    ->hiddenOn('create')
                    ->headerActions([
                        Action::make('toggle-quick-deploy')
                            ->label(fn (Site $record) => $record->quick_deploy === 0 ? __('deploy.enable quick deploy') : __('deploy.disable quick deploy'))
                            ->color(fn (Site $record) => $record->quick_deploy === 0 ? 'primary' : 'danger')
                            ->action(function (Site $record) {
                                if ($record->quick_deploy === 0) {
                                    $record->quick_deploy = 1;
                                } else {
                                    $record->quick_deploy = 0;
                                }

                                $record->save();

                                Notification::make()
                                    ->success()
                                    ->title(__('deploy.quick deploy successfully toggled'))
                                    ->send();
                            }),
                        Action::make('deploy')
                            ->label(__('deploy.deploy now'))
                            ->color('success')
                            ->action(function (Site $record, Get $get) {
                                $this->dispatch('deploy-logs', 'out', 'Starting deployment...');
                                $record->script = $get('script');
                                $deployScript = DeployScript::make()
                                    ->server($record->server)
                                    ->site($record)
                                    ->actAsSiteUser();

                                if ($record->{'project-type'} == 'nodejs') {
                                    $deployScript->script('source .nvm/nvm.sh');
                                }

                                $deployScript->toSiteDirectory()
                                    ->gitStash()
                                    ->gitStashClear()
                                    ->checkoutTo($record->branch)
                                    ->gitPull()
                                    ->script(explode('\n', substr(substr(json_encode($record->script), 1), 0, -1)));

                                $process = $deployScript->execute();

                                $notification = Notification::make();

                                if ($process->isSuccessful()) {
                                    $this->dispatch('deploy-logs', 'out', $process->getOutput().'\n'.$process->getOutput());
                                    $notification->title('Deployment successfully')
                                        ->success();
                                } else {
                                    $this->dispatch('deploy-logs', 'out', $process->getErrorOutput().'\n'.$process->getOutput());
                                    $notification->title('Deployment failed')
                                        ->danger();
                                }

                                $record->save();

                                $notification->send();
                            }),
                    ]),

                Section::make(__('deploy.deployment log'))
                    ->schema([
                        View::make('view-deployment-logs')
                            ->viewData([
                                'listener' => 'deploy-logs',
                            ]),
                    ]),

            ];

        if ($record->repository) {
            $components[] =
                ViewField::make('commits')
                    ->view('view-commits')
                    ->viewData([
                        'commits' => $resource->getCommits($this->getRecord())->toArray(),
                        'record' => $this->getRecord(),
                    ])
                    ->columnSpanFull();
        }

        return $components;
    }

    public function form(Form $form): Form
    {

        return $form
            ->schema($this->getCurrentFormSchema());
    }
}
