<?php

namespace App\Filament\Widgets;

use App\Models\Hosting\HostingModule;
use App\Models\Hosting\SystemService;
use App\Models\ServerSite;
use App\Models\Task;
use App\Services\System\ServerMetrics;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class EnterpriseCommandCenter extends Widget
{
    protected static ?int $sort = -10;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.enterprise-command-center';

    protected function getViewData(): array
    {
        $metrics = app(ServerMetrics::class)->snapshot();

        $openTasks = Task::whereIn('status', ['pending', 'running'])->count();
        $failedTasks = Task::where('status', 'failed')->where('updated_at', '>=', now()->subDay())->count();
        $failedSites = ServerSite::where('status', 'failed')->count();
        $servicesNeedingAttention = SystemService::whereIn('status', ['failed', 'stopped'])->count();
        $installedModules = HostingModule::where('status', 'installed')->count();
        $totalModules = HostingModule::count();

        $healthScore = max(0, 100 - ($failedTasks * 6) - ($failedSites * 10) - ($servicesNeedingAttention * 4) - ($openTasks * 2));

        return [
            'healthScore' => $healthScore,
            'metrics' => $metrics,
            'cards' => [
                ['label' => 'Active sites', 'value' => ServerSite::where('status', 'active')->count(), 'meta' => ServerSite::count().' total'],
                ['label' => 'Open tasks', 'value' => $openTasks, 'meta' => "{$failedTasks} failed in 24h"],
                ['label' => 'Modules', 'value' => $installedModules, 'meta' => "{$totalModules} catalog items"],
                ['label' => 'Services', 'value' => SystemService::where('status', 'running')->count(), 'meta' => "{$servicesNeedingAttention} attention"],
            ],
            'incidents' => $this->incidents($failedTasks, $failedSites, $servicesNeedingAttention),
        ];
    }

    /**
     * @return Collection<int, array{label: string, detail: string, tone: string}>
     */
    private function incidents(int $failedTasks, int $failedSites, int $servicesNeedingAttention): Collection
    {
        return collect([
            [
                'label' => 'Task failures',
                'detail' => "{$failedTasks} in the last 24 hours",
                'tone' => $failedTasks > 0 ? 'danger' : 'success',
            ],
            [
                'label' => 'Site deployment',
                'detail' => "{$failedSites} failed sites",
                'tone' => $failedSites > 0 ? 'danger' : 'success',
            ],
            [
                'label' => 'Service health',
                'detail' => "{$servicesNeedingAttention} stopped or failed",
                'tone' => $servicesNeedingAttention > 0 ? 'warning' : 'success',
            ],
        ]);
    }
}
