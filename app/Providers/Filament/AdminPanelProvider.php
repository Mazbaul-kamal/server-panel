<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\EnterpriseCommandCenter;
use App\Filament\Widgets\GettingStartedWidget;
use App\Filament\Widgets\ModuleLifecycleWidget;
use App\Filament\Widgets\RecentTasksWidget;
use App\Filament\Widgets\ServiceHealthWidget;
use App\Filament\Widgets\SystemStatsOverview;
use App\Filament\Widgets\TaskThroughputChart;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
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
            ->brandName('Server Panel')
            ->darkMode(isForced: true)
            ->spa()
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth(Width::Full)
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->colors([
                'primary' => Color::Cyan,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
                'danger' => Color::Rose,
                'gray' => Color::Zinc,
            ])
            ->navigationGroups([
                NavigationGroup::make('Operations')->icon(Heroicon::OutlinedCommandLine),
                NavigationGroup::make('Web Hosting')->icon(Heroicon::OutlinedGlobeAlt),
                NavigationGroup::make('Data & DNS')->icon(Heroicon::OutlinedCircleStack),
                NavigationGroup::make('Messaging')->icon(Heroicon::OutlinedEnvelope),
                NavigationGroup::make('Runtime')->icon(Heroicon::OutlinedCube),
                NavigationGroup::make('Backups')->icon(Heroicon::OutlinedArchiveBox),
                NavigationGroup::make('Security')->icon(Heroicon::OutlinedShieldCheck),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->widgets([
                GettingStartedWidget::class,
                EnterpriseCommandCenter::class,
                SystemStatsOverview::class,
                TaskThroughputChart::class,
                ServiceHealthWidget::class,
                RecentTasksWidget::class,
                ModuleLifecycleWidget::class,
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
