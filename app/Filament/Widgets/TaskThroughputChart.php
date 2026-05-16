<?php

namespace App\Filament\Widgets;

use App\Models\Task;
use Filament\Widgets\ChartWidget;

class TaskThroughputChart extends ChartWidget
{
    protected static ?int $sort = 10;

    protected ?string $heading = 'Task throughput';

    protected ?string $description = 'Last 14 days';

    protected string $color = 'primary';

    protected int|string|array $columnSpan = [
        'md' => 2,
        'xl' => 1,
    ];

    protected function getData(): array
    {
        $labels = [];
        $finished = [];
        $failed = [];

        foreach (range(13, 0) as $daysAgo) {
            $day = now()->subDays($daysAgo);
            $labels[] = $day->format('M j');

            $finished[] = Task::where('status', 'finished')
                ->whereBetween('created_at', [$day->copy()->startOfDay(), $day->copy()->endOfDay()])
                ->count();

            $failed[] = Task::where('status', 'failed')
                ->whereBetween('created_at', [$day->copy()->startOfDay(), $day->copy()->endOfDay()])
                ->count();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Finished',
                    'data' => $finished,
                    'borderColor' => '#34d399',
                    'backgroundColor' => 'rgba(52, 211, 153, 0.12)',
                ],
                [
                    'label' => 'Failed',
                    'data' => $failed,
                    'borderColor' => '#fb7185',
                    'backgroundColor' => 'rgba(251, 113, 133, 0.12)',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
