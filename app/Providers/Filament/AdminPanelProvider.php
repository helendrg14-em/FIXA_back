<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\FontProviders\LocalFontProvider;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Tables\Table;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    /** Filas por página; con esta cantidad o menos la tabla se muestra entera, sin paginación. */
    private const FILAS_POR_PAGINA = 10;

    public function boot(): void
    {
        // Paginar solo cuando hay más registros que una página: con 2 feriados o 3 usuarios sobra.
        // Cuenta sobre la consulta ya filtrada, así la búsqueda también la quita si quedan pocos.
        Table::configureUsing(fn (Table $table) => $table
            ->paginationPageOptions([self::FILAS_POR_PAGINA, 25, 50])
            ->defaultPaginationPageOption(self::FILAS_POR_PAGINA)
            ->paginated(fn (Table $table): bool => ($table->getLivewire()->getFilteredTableQuery()?->count() ?? 0) > self::FILAS_POR_PAGINA));
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->brandName('FIXA')
            // Navegación sin recargar la página: entre secciones del panel solo cambia el contenido.
            ->spa()
            // Sin ->registration(): las cuentas las crea solo el administrador (R03).
            ->login(Login::class)
            // Dos versiones del logo: la clara va sobre la barra lateral verde oscura (fixa-panel.css).
            ->brandLogo(fn (): HtmlString => new HtmlString(view('panel.logo')->render()))
            ->brandLogoHeight('2.25rem')
            ->favicon(recurso('images/fixa-marca.png'))
            ->darkMode(false)
            ->font('Atkinson Hyperlegible', url: recurso('css/fixa-fuentes.css'), provider: LocalFontProvider::class)
            ->renderHook(PanelsRenderHook::HEAD_START, fn (): string => view('partes.precarga-fuentes')->render())
            // Escalas armadas a partir del logo: 600 es el verde hoja, 950 el verde bosque del texto y warning el naranja de la hoja.
            ->colors([
                'primary' => [50 => '#effaf2', 100 => '#d8f2df', 200 => '#b3e4c2', 300 => '#81cf9c', 400 => '#4fb474', 500 => '#2e9a58', 600 => '#1f7d47', 700 => '#1b643b', 800 => '#194f31', 900 => '#15412a', 950 => '#0b2418'],
                'gray' => [50 => '#f5f7f2', 100 => '#e9eee5', 200 => '#d5ddd0', 300 => '#b3c0ae', 400 => '#889884', 500 => '#677a64', 600 => '#4f614d', 700 => '#3e4e3d', 800 => '#2a3a2e', 900 => '#172a20', 950 => '#0c1a13'],
                'success' => [50 => '#f6fbe9', 100 => '#eaf5cf', 200 => '#d5eca3', 300 => '#b8dd6c', 400 => '#9ccc3c', 500 => '#7fb126', 600 => '#628c1b', 700 => '#4b6b19', 800 => '#3e5619', 900 => '#354919', 950 => '#1a2808'],
                'warning' => [50 => '#fff6eb', 100 => '#ffe9cc', 200 => '#fdd096', 300 => '#fab05a', 400 => '#f5952f', 500 => '#ec7b12', 600 => '#d0600b', 700 => '#ac470d', 800 => '#8b3912', 900 => '#723112', 950 => '#3e1606'],
            ])
            // Estilos de escritorio y, aparte, los de tablet y celular (public/movil).
            ->renderHook(PanelsRenderHook::STYLES_AFTER, fn (): HtmlString => new HtmlString('<link rel="stylesheet" href="'.e(recurso('css/fixa-panel.css')).'">'
                .'<link rel="stylesheet" href="'.e(recurso('movil/css/panel.css')).'">'))
            // Columna de marca junto al formulario de acceso.
            ->renderHook(PanelsRenderHook::SIMPLE_LAYOUT_START,fn (): string => view('panel.acceso-marca')->render(), scopes: Login::class)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            // Pages/Inicio.php reemplaza al Dashboard por defecto; cada perfil ve su resumen en Widgets/{Admin,Supervisor,Tecnico}.
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
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
