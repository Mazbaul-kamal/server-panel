<?php

namespace App\Filament\Widgets;

use App\Models\DatabaseAccount;
use App\Models\ServerSite;
use App\Models\Task;
use App\Services\System\ServerMetrics;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SystemStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 0;

    protected function getStats(): array
    {
        $metrics = app(ServerMetrics::class)->snapshot();
        $openTasks = Task::whereIn('status', ['pending', 'running'])->count();
        $failedTasks = Task::where('status', 'failed')->where('updated_at', '>=', now()->subDay())->count();

        return [
            Stat::make('Load', $metrics['load'])
                ->description('1 minute average')
                ->icon(Heroicon::OutlinedCpuChip)
                ->chart([1, 3, 2, 5, 4, 6, 5])
                ->chartColor('primary'),
            Stat::make('Memory', $metrics['memory'])
                ->description('used memory')
                ->icon(Heroicon::OutlinedServerStack)
                ->chart([3, 4, 4, 5, 5, 6, 6])
                ->chartColor('warning'),
            Stat::make('Disk', $metrics['disk'])
                ->description('root filesystem')
                ->icon(Heroicon::OutlinedCircleStack)
                ->chart([2, 2, 3, 3, 4, 4, 5])
                ->chartColor('success'),
            Stat::make('Sites', ServerSite::count())
                ->description(ServerSite::where('status', 'active')->count().' active')
                ->descriptionColor('success')
                ->icon(Heroicon::OutlinedGlobeAlt),
            Stat::make('Databases', DatabaseAccount::count())
                ->description(DatabaseAccount::where('status', 'active')->count().' provisioned')
                ->descriptionColor('primary')
                ->icon(Heroicon::OutlinedCircleStack),
            Stat::make('Tasks', $openTasks)
                ->description("{$failedTasks} failed in 24h")
                ->descriptionColor($failedTasks > 0 ? 'danger' : 'success')
                ->icon(Heroicon::OutlinedCommandLine),
        ];
    }
}
