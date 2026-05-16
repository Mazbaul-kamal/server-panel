<?php

namespace App\Filament\Resources\Tasks;

use App\Filament\Resources\Tasks\Pages\ManageTasks;
use App\Models\Task;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCommandLine;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('label'),
                TextEntry::make('action'),
                TextEntry::make('status')->badge(),
                TextEntry::make('exit_code')->placeholder('n/a'),
                TextEntry::make('started_at')->dateTime()->placeholder('not started'),
                TextEntry::make('finished_at')->dateTime()->placeholder('not finished'),
                ViewEntry::make('console')
                    ->label('Console')
                    ->view('filament.infolists.task-console')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('label')->searchable()->wrap(),
                TextColumn::make('action')->badge()->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'finished' => 'success',
                        'failed' => 'danger',
                        'running' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('exit_code')->placeholder('n/a'),
                TextColumn::make('started_at')->dateTime()->sortable(),
                TextColumn::make('finished_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'pending' => 'Pending',
                    'running' => 'Running',
                    'finished' => 'Finished',
                    'failed' => 'Failed',
                ]),
            ])
            ->recordActions([
                ViewAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageTasks::route('/'),
        ];
    }
}
