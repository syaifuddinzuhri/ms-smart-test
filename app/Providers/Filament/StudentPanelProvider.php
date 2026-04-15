<?php

namespace App\Providers\Filament;

use App\Enums\PanelType;
use App\Filament\Pages\Auth\Login;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Assets\Css;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class StudentPanelProvider extends PanelProvider
{
    public function boot(): void
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE,
            fn() => view('components.login-logo'),
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
            fn() => view('components.login-footer'),
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::TOPBAR_START,
            fn() => view('filament.student.components.topbar-logo'),
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_END,
            fn() => view('filament.student.components.footer'),
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_END,
            function (): string {
                if (app()->environment('production')) {
                    return Blade::render('
                    <script>
                        document.oncontextmenu = function() { return false; };
                    </script>
                ');
                }
                return '';
            },
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::GLOBAL_SEARCH_BEFORE,
            fn() => Blade::render('@livewire(\'realtime-server-time\')'),
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_END,
            fn(): string => Blade::render('
            <script src="https://cdn.tailwindcss.com"></script>

            <script>
                tailwind.config = {
                    darkMode: "class",
                    corePlugins: {
                        preflight: false, // Penting: Agar tidak merusak CSS bawaan Filament
                    }
                }
            </script>
        '),
        );

        // FilamentAsset::register([
        //     Css::make('custom-style', asset('css/tailwind-full.css')),
        // ]);
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id(PanelType::STUDENT->value)
            ->path(PanelType::STUDENT->value)
            ->darkMode(false)
            ->authGuard('web')
            ->defaultThemeMode(ThemeMode::Light)
            ->brandName('MANUSGI SMART TEST')
            ->topNavigation()
            ->login(Login::class)
            ->colors([
                'primary' => Color::Green,
            ])
            ->discoverResources(in: app_path('Filament/Student/Resources'), for: 'App\\Filament\\Student\\Resources')
            ->discoverPages(in: app_path('Filament/Student/Pages'), for: 'App\\Filament\\Student\\Pages')
            ->discoverWidgets(in: app_path('Filament/Student/Widgets'), for: 'App\\Filament\\Student\\Widgets')
            ->pages([])
            ->widgets([])
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
            ])
            ->spa();
    }
}
