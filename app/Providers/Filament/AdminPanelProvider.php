<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

use App\Models\Setting;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName(fn () => Setting::get('company_name', 'AIWA AGENCY'))
            ->profile(\App\Filament\Pages\Auth\EditProfile::class)
            ->colors([
                'primary' => Color::Blue,
                'gray' => Color::Slate,
                'success' => Color::Emerald,
                'danger' => Color::Rose,
                'warning' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                \App\Filament\Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
            ->renderHook(
                \Filament\View\PanelsRenderHook::HEAD_END,
                fn () => new \Illuminate\Support\HtmlString('
                    <style>
                        /* Custom Sidebar Navy Blue Style */
                        .fi-sidebar {
                            background-color: #0a162c !important;
                            border-right: 1px solid #1e293b !important;
                        }
                        /* Active sidebar items */
                        .fi-sidebar-item-active, 
                        .fi-sidebar-item-active a, 
                        .fi-sidebar-item-active span,
                        .fi-sidebar-item-active svg,
                        .fi-sidebar-group a.fi-active,
                        .fi-sidebar-group a.fi-active * {
                            background-color: #2563eb !important;
                            color: #ffffff !important;
                        }
                        /* Inactive sidebar items - visibility fix */
                        .fi-sidebar-item:not(.fi-sidebar-item-active) a,
                        .fi-sidebar-item:not(.fi-sidebar-item-active) button,
                        .fi-sidebar-item:not(.fi-sidebar-item-active) span,
                        .fi-sidebar-item:not(.fi-sidebar-item-active) svg {
                            color: #94a3b8 !important;
                        }
                        /* Inactive items hover states */
                        .fi-sidebar-item:not(.fi-sidebar-item-active) a:hover,
                        .fi-sidebar-item:not(.fi-sidebar-item-active) button:hover,
                        .fi-sidebar-item:not(.fi-sidebar-item-active) a:hover *,
                        .fi-sidebar-item:not(.fi-sidebar-item-active) button:hover * {
                            color: #ffffff !important;
                            background-color: #162a45 !important;
                        }
                        .fi-sidebar-nav-label, .fi-sidebar-group-label {
                            color: #94a3b8 !important;
                        }
                        .fi-sidebar-header {
                            border-bottom: 1px solid #1e293b !important;
                        }
                        /* Global status colors overrides for status badges to match mockup */
                        .fi-badge-color-success {
                            background-color: #10b981 !important;
                            color: #ffffff !important;
                        }
                        .fi-badge-color-danger {
                            background-color: #ef4444 !important;
                            color: #ffffff !important;
                        }
                        .fi-badge-color-warning {
                            background-color: #f59e0b !important;
                            color: #ffffff !important;
                        }
                    </style>
                ')
            )
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
