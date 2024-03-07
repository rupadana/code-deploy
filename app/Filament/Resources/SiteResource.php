<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SiteResource\Pages;
use App\Models\Server;
use App\Models\Site;
use App\Services\DeployScript;
use ChrisReedIO\Socialment\Models\ConnectedAccount;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;
use Rupadana\GithubApi\GithubApi;

class SiteResource extends Resource
{
    protected static ?string $model = Site::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public function getRepositories(string $user_id)
    {
        $user = ConnectedAccount::query()->where('user_id', $user_id)->first();
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
            'create' => Pages\CreateSite::route('/create'),
            'view' => Pages\ViewSite::route('/{record}'),
            'edit' => Pages\EditSite::route('/{record}/edit'),
            'general' => Pages\GeneralSites::route('/{record}/general'),
            'deployment' => Pages\DeploymentSites::route('/{record}/deployment'),
            'environment' => Pages\EnvironmentSites::route('/{record}/environment'),
            'deployment-log' => Pages\ListDeployment::route('/{record}/deployment-log') 
        ];
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\GeneralSites::class,
            Pages\DeploymentSites::class,
            Pages\EnvironmentSites::class,
            Pages\ListDeployment::class
        ]);
    }
}
