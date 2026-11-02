<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\FontProviders\LocalFontProvider;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->brandName('FIXA')
            // Sin ->registration(): las cuentas las crea solo el administrador (R03).
            ->login(Login::class)
            ->brandLogo(fn (): string => asset('images/fixa-logo.svg'))
            ->brandLogoHeight('2.25rem')
            ->favicon(asset('images/fixa-marca.svg'))
            ->darkMode(false)
            ->font('Atkinson Hyperlegible', url: asset('css/fixa-fuentes.css'), provider: LocalFontProvider::class)
            // Escalas armadas a partir de la paleta de FIXA: 600 es #2d6073, 900 es #1f192f y 400 es #65b8a6.
            ->colors([
                'primary' => [50 => '#eef6f7', 100 => '#d4e7eb', 200 => '#aed0d8', 300 => '#7fb1be', 400 => '#4f8ea0', 500 => '#3a7487', 600 => '#2d6073', 700 => '#27505f', 800 => '#23434f', 900 => '#1f3843', 950 => '#12232b'],
                'gray' => [50 => '#f6f5f8', 100 => '#ecebf0', 200 => '#d9d6e1', 300 => '#bab5c7', 400 => '#8f88a3', 500 => '#6f6885', 600 => '#575071', 700 => '#443e5a', 800 => '#332d47', 900 => '#1f192f', 950 => '#15101f'],
                'success' => [50 => '#effaf6', 100 => '#d6f2e5', 200 => '#b5e8c3', 300 => '#8ed4b8', 400 => '#65b8a6', 500 => '#459c8b', 600 => '#2f7f71', 700 => '#29665c', 800 => '#25524b', 900 => '#21443f', 950 => '#0f2825'],
            ])
            ->renderHook(PanelsRenderHook::STYLES_AFTER, fn (): HtmlString => new HtmlString('<link rel="stylesheet" href="'.e(asset('css/fixa-panel.css')).'">'))
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
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
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
