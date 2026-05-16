<?php

namespace App\Filament\Resources\BackupRuns;

use App\Filament\Resources\BackupRuns\Pages\ManageBackupRuns;
use App\Models\Hosting\BackupRun;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BackupRunResource extends Resource
{
    protected static ?string $model = BackupRun::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCloudArrowDown;

    protected static string|\UnitEnum|null $navigationGroup = 'Backups';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('plan.name')->label('Plan'),
            TextEntry::make('status')->badge(),
            TextEntry::make('archive_path')->placeholder('n/a')->columnSpanFull(),
            TextEntry::make('size_bytes')->numeric()->placeholder('n/a'),
            TextEntry::make('started_at')->dateTime()->placeholder('not started'),
            TextEntry::make('finished_at')->dateTime()->placeholder('not finished'),
            TextEntry::make('error')->placeholder('n/a')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('plan.name')->label('Plan')->searchable()->sortable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('archive_path')->wrap()->toggleable(),
                TextColumn::make('size_bytes')->numeric()->placeholder('n/a'),
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
            'index' => ManageBackupRuns::route('/'),
        ];
    }
}
