<?php

namespace App\Filament\Resources\SiteResource\Pages;

use App\Filament\Resources\SiteResource;
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

    public function form(Form $form): Form
    {
        $resource = app($this->getResource());
        $server = $this->getRecord()->server;

        return $form
            ->schema([
                Section::make('Deployment')
                    ->schema([
                        Textarea::make('script')
                            ->rows(10),
                    ])
                    ->hiddenOn('create')
                    ->headerActions([
                        Action::make('deploy')
                            ->color('success')
                            ->action(function (Site $record, Get $get) {
                                $this->dispatch('deploy-logs', 'out', 'Starting deployment...');
                                $record->script = $get('script');
                                $deployScript = DeployScript::make()
                                    ->server($record->server)
                                    ->site($record)
                                    ->actAsSiteUser()
                                    ->toSiteDirectory()
                                    ->gitStash()
                                    ->gitStashClear()
                                    ->gitFetch()
                                    ->checkoutTo($record->branch)
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
                            }),
                    ]),

                Section::make('Deployment Log')
                    ->schema([
                        View::make('view-deployment-logs')
                            ->viewData([
                                'listener' => 'deploy-logs',
                            ]),
                    ]),
                ViewField::make('commits')
                    ->view('view-commits')
                    ->viewData([
                        'commits' => $resource->getCommits($this->getRecord())->toArray(),
                        'record' => $this->getRecord(),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
