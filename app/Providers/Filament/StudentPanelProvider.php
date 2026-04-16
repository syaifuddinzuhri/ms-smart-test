<?php

namespace App\Providers\Filament;

use App\Enums\PanelType;
use App\Filament\Pages\Auth\Login;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
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
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id(PanelType::STUDENT->value)
            ->path(PanelType::STUDENT->value)
            ->darkMode(false)
            ->authGuard('web')
            ->defaultThemeMode(ThemeMode::Light)
            ->brandLogo(fn() => view('components.logo'))
            ->brandLogoHeight('2rem')
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
            ->renderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE,
                fn() => view('components.login-logo'),
            )
            ->renderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
                fn() => view('components.login-footer'),
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn() => view('components.footer'),
            )
            ->renderHook(
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
            )
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_BEFORE,
                fn() => Blade::render('@livewire(\'realtime-server-time\')'),
            )
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn(): string => Blade::render('
                    <script src="https://cdn.tailwindcss.com"></script>
                    <script>
                        tailwind.config = {
                            darkMode: "class",
                            corePlugins: {
                                preflight: false,
                            }
                        }
                    </script>
                '),
            )
            ->spa();
    }
}
