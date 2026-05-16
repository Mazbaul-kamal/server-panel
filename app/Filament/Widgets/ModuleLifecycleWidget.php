<?php

namespace App\Filament\Widgets;

use App\Models\Hosting\HostingModule;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class ModuleLifecycleWidget extends TableWidget
{
    protected static ?int $sort = 40;

    protected int|string|array $columnSpan = [
        'md' => 2,
        'xl' => 1,
    ];

    public function table(Table $table): Table
    {
        return $table
            ->heading('Module lifecycle')
            ->query(HostingModule::query()
                ->orderByRaw("case status when 'failed' then 0 when 'installing' then 1 when 'available' then 2 else 3 end")
                ->limit(8))
            ->columns([
                TextColumn::make('name')->searchable()->wrap(),
                TextColumn::make('category')->badge(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'installed' => 'success',
                        'installing' => 'warning',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
                IconColumn::make('enabled')->boolean(),
            ])
            ->paginated(false);
    }
}
