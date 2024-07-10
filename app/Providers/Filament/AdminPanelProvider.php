<?php

namespace App\Providers\Filament;

use App\Filament\App\Resources\ServerResource;
use App\Filament\App\Resources\SiteResource;
use App\Filament\Pages\LaravelPulse;
use App\Livewire\LoginPage;
use BezhanSalleh\FilamentExceptions\FilamentExceptionsPlugin;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use ChrisReedIO\Socialment\SocialmentPlugin;
use Croustibat\FilamentJobsMonitor\FilamentJobsMonitorPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use pxlrbt\FilamentEnvironmentIndicator\EnvironmentIndicatorPlugin;
use Rupadana\ApiService\ApiServicePlugin;
use Rupadana\FilamentUserResource\FilamentUserResourcePlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(LoginPage::class)
            ->favicon(url('favicon.png'))
            ->colors([
                'primary' => Color::Blue,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->resources([
                ServerResource::class,
                SiteResource::class,
            ])
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->plugins([
                FilamentUserResourcePlugin::make(),
                FilamentShieldPlugin::make(),
                SocialmentPlugin::make()
                    ->registerProvider('github', 'fab-github', 'Github', scopes: [
                        'repo', 'read:user',
                    ]),
                ApiServicePlugin::make(),
                FilamentExceptionsPlugin::make(),
                EnvironmentIndicatorPlugin::make(),
                FilamentJobsMonitorPlugin::make()
                    ->enableNavigation(),

            ])

            ->pages([
                Pages\Dashboard::class,
                // LaravelPulse::class
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->databaseNotifications()
            ->databaseNotificationsPolling('10s')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            // ->topNavigation()
            ->profile()
            ->spa()
            // ->topbar(false)
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
