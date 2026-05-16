<?php

namespace App\Filament\Widgets;

use App\Models\Hosting\SystemService;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class ServiceHealthWidget extends TableWidget
{
    protected static ?int $sort = 20;

    protected int|string|array $columnSpan = [
        'md' => 2,
        'xl' => 1,
    ];

    public function table(Table $table): Table
    {
        return $table
            ->heading('Service health')
            ->query(SystemService::query()->latest('last_checked_at')->limit(8))
            ->columns([
                TextColumn::make('display_name')->label('Service')->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'running' => 'success',
                        'stopped' => 'warning',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
                IconColumn::make('enabled')->boolean(),
            ])
            ->paginated(false)
            ->poll('15s');
    }
}
