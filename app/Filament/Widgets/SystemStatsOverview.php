<?php

namespace App\Filament\Widgets;

use App\Models\DatabaseAccount;
use App\Models\ServerSite;
use App\Models\Task;
use App\Services\System\ServerMetrics;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SystemStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $metrics = app(ServerMetrics::class)->snapshot();

        return [
            Stat::make('Load', $metrics['load'])->description('1 minute average'),
            Stat::make('Memory', $metrics['memory'])->description('Used memory'),
            Stat::make('Disk', $metrics['disk'])->description('Root filesystem'),
            Stat::make('Sites', ServerSite::count())->description(ServerSite::where('status', 'active')->count().' active'),
            Stat::make('Databases', DatabaseAccount::count())->description(DatabaseAccount::where('status', 'active')->count().' provisioned'),
            Stat::make('Tasks', Task::whereIn('status', ['pending', 'running'])->count())->description('pending or running'),
        ];
    }
}
