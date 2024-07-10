<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\SiteResource\Pages;
use App\Filament\App\Resources\SiteResource\Pages\GeneralSites;
use App\Jobs\DeploymentJob;
use App\Models\Server;
use App\Models\Site;
use App\Services\DeployScript;
use ChrisReedIO\Socialment\Models\ConnectedAccount;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;
use Rupadana\GithubApi\GithubApi;

class SiteResource extends Resource
{
    protected static ?string $model = Site::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('server.name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('domain')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('repository')
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Action::make('edit')
                    ->url(fn (Site $record) => GeneralSites::getUrl(['record' => $record]))
                    ->label('Manage site')
                    ->icon('heroicon-o-globe-alt'),
                DeleteAction::make()
                    ->after(function (Site $record) {
                        // $server = $record->server;

                        // $process = DeployScript::make()
                        //     ->site($record)
                        //     ->deleteSite()
                        //     ->server($server);

                        // DeploymentJob::dispatch($process, auth()->user());

                        // return Notification::make('notif-1')
                        //     ->title('Your site is on delete process!')
                        //     ->success()
                        //     ->send();
                    })
                    ->successNotification(null),
            ]);
    }

    public function getRepositories(string $user_id)
    {
        $user = ConnectedAccount::query()->where('user_id', $user_id)->first();

        return Cache::remember('repositories-'.$user->nickname, 86400, function () use ($user) {
            // TODO: Get repository from organization too.
            $data = GithubApi::make($user->token)
                ->user()
                ->repos()
                ->get([
                    'per_page' => 200,
                ]);

            if (isset($data['message'])) {
                return [];
            }

            return $data->pluck('full_name', 'full_name');
        });
    }

    public function getTemplates(Server $server): array
    {
        return DeployScript::make($server)
            ->getTemplates()
            ->pluck('name', 'name')
            ->toArray();
    }

    public function getCommits(Site $site)
    {
        $user = ConnectedAccount::query()->where('user_id', $site->server->created_by)->first();

        return GithubApi::make($user->token)
            ->repos($site->repository)
            ->commits()
            ->get();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSites::route('/'),
            'view' => Pages\ViewSite::route('/{record}'),
            'general' => Pages\GeneralSites::route('/{record}/general'),
            'deployment' => Pages\DeploymentSites::route('/{record}/deployment'),
            'environment' => Pages\EnvironmentSites::route('/{record}/environment'),
            'deployment-log' => Pages\ListDeployment::route('/{record}/deployment-log'),
            'repository' => Pages\Repository::route('{record}/repository'),
        ];
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\GeneralSites::class,
            Pages\DeploymentSites::class,
            Pages\EnvironmentSites::class,
            Pages\Repository::class,
            Pages\ListDeployment::class,
        ]);
    }
}
