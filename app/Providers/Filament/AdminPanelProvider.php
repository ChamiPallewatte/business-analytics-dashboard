<?php

namespace App\Providers\Filament;

use App\Models\Company;
use App\Models\Setting;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->tenant(Company::class, slugAttribute: 'slug')
            ->brandName(fn () => auth()->user()?->company?->name ?? 'SaaS Analytics Platform')
            ->profile(\App\Filament\Pages\Auth\EditProfile::class)
            ->colors([
                'primary' => Color::Blue,
                'gray' => Color::Slate,
                'success' => Color::Emerald,
                'danger' => Color::Rose,
                'warning' => Color::Amber,
            ])
            ->navigationGroups([
                'Platform Administration',
                'User & Access Management',
                'Operations & Clients',
                'Financial Management',
                'Analytics & Insights',
                'Settings & Logs',
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                \App\Filament\Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                \App\Filament\Widgets\SuperAdminPlatformStatsWidget::class,
                \App\Filament\Widgets\IndustryStatsWidget::class,
            ])
            ->renderHook(
                \Filament\View\PanelsRenderHook::HEAD_END,
                fn () => new \Illuminate\Support\HtmlString('
                    <style>
                        /* Modern Dark/Glassmorphic Sidebar styling */
                        .fi-sidebar {
                            background: linear-gradient(180deg, #0b132b 0%, #1c2541 100%) !important;
                            border-right: 1px solid #1e293b !important;
                        }
                        .fi-sidebar-item-active, 
                        .fi-sidebar-item-active a, 
                        .fi-sidebar-item-active span,
                        .fi-sidebar-item-active svg,
                        .fi-sidebar-group a.fi-active,
                        .fi-sidebar-group a.fi-active * {
                            background-color: #2563eb !important;
                            color: #ffffff !important;
                            border-radius: 0.5rem !important;
                        }
                        .fi-sidebar-item:not(.fi-sidebar-item-active) a,
                        .fi-sidebar-item:not(.fi-sidebar-item-active) button,
                        .fi-sidebar-item:not(.fi-sidebar-item-active) span,
                        .fi-sidebar-item:not(.fi-sidebar-item-active) svg {
                            color: #94a3b8 !important;
                        }
                        .fi-sidebar-item:not(.fi-sidebar-item-active) a:hover,
                        .fi-sidebar-item:not(.fi-sidebar-item-active) button:hover {
                            color: #ffffff !important;
                            background-color: #1e293b !important;
                            border-radius: 0.5rem !important;
                        }
                        .fi-sidebar-nav-label, .fi-sidebar-group-label {
                            color: #64748b !important;
                            font-weight: 600 !important;
                            text-transform: uppercase !important;
                            letter-spacing: 0.05em !important;
                        }
                        .fi-sidebar-header {
                            border-bottom: 1px solid #1e293b !important;
                        }
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
