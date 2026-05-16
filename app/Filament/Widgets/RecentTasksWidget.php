<?php

namespace App\Filament\Widgets;

use App\Models\Task;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentTasksWidget extends TableWidget
{
    protected static ?int $sort = 30;

    protected int|string|array $columnSpan = [
        'md' => 2,
        'xl' => 1,
    ];

    public function table(Table $table): Table
    {
        return $table
            ->heading('Recent task activity')
            ->query(Task::query()->latest()->limit(8))
            ->columns([
                TextColumn::make('label')->searchable()->wrap(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'finished' => 'success',
                        'failed' => 'danger',
                        'running' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('updated_at')->since()->sortable(),
            ])
            ->paginated(false)
            ->poll('10s');
    }
}
