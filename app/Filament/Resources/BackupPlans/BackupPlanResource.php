<?php

namespace App\Filament\Resources\BackupPlans;

use App\Filament\Resources\BackupPlans\Pages\ManageBackupPlans;
use App\Jobs\Hosting\RunBackupPlan;
use App\Models\Hosting\BackupPlan;
use App\Models\Task;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BackupPlanResource extends Resource
{
    protected static ?string $model = BackupPlan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    protected static string|\UnitEnum|null $navigationGroup = 'Hosting';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Backup plan')
                ->columns(2)
                ->schema([
                    TextInput::make('name')->required()->maxLength(255),
                    Select::make('schedule')
                        ->required()
                        ->default('daily')
                        ->options([
                            'hourly' => 'Hourly',
                            'daily' => 'Daily',
                            'weekly' => 'Weekly',
                            'monthly' => 'Monthly',
                        ]),
                    TextInput::make('source_path')
                        ->required()
                        ->default(fn (): string => rtrim((string) config('server-panel.managed_root'), '/').'/example.com')
                        ->columnSpanFull(),
                    TextInput::make('destination_path')
                        ->required()
                        ->default('/var/backups/server-panel/sites')
                        ->columnSpanFull(),
                    TextInput::make('retention_days')
                        ->required()
                        ->integer()
                        ->minValue(1)
                        ->maxValue(3650)
                        ->default(14),
                    Select::make('status')
                        ->required()
                        ->default('ready')
                        ->options([
                            'ready' => 'Ready',
                            'running' => 'Running',
                            'failed' => 'Failed',
                        ]),
                    Toggle::make('enabled')->default(true),
                    KeyValue::make('metadata')->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('schedule')->badge(),
                TextColumn::make('source_path')->wrap()->toggleable(),
                TextColumn::make('destination_path')->wrap()->toggleable(),
                TextColumn::make('retention_days')->sortable(),
                IconColumn::make('enabled')->boolean(),
                TextColumn::make('status')->badge(),
                TextColumn::make('last_run_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('schedule')->options([
                    'hourly' => 'Hourly',
                    'daily' => 'Daily',
                    'weekly' => 'Weekly',
                    'monthly' => 'Monthly',
                ]),
                SelectFilter::make('status')->options([
                    'ready' => 'Ready',
                    'running' => 'Running',
                    'failed' => 'Failed',
                ]),
            ])
            ->recordActions([
                Action::make('run')
                    ->icon(Heroicon::OutlinedArchiveBoxArrowDown)
                    ->requiresConfirmation()
                    ->action(function (BackupPlan $record): void {
                        $task = Task::create([
                            'user_id' => auth()->id(),
                            'label' => "Run backup {$record->name}",
                            'action' => 'panel.system',
                            'arguments' => ['backup-path', $record->source_path, $record->destination_path, (string) $record->retention_days],
                            'status' => 'pending',
                        ]);

                        $record->forceFill(['status' => 'running'])->save();
                        RunBackupPlan::dispatch($task->id, $record->id);

                        Notification::make()->title("Queued backup task #{$task->id}")->success()->send();
                    }),
                ViewAction::make(),
                EditAction::make(),
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
            'index' => ManageBackupPlans::route('/'),
        ];
    }
}
