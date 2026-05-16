<?php

namespace App\Filament\Resources\SystemServices;

use App\Filament\Resources\SystemServices\Pages\ManageSystemServices;
use App\Jobs\Hosting\ManageSystemService;
use App\Models\Hosting\SystemService;
use App\Models\Task;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SystemServiceResource extends Resource
{
    protected static ?string $model = SystemService::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedServerStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Hosting';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Service')
                ->columns(2)
                ->schema([
                    TextInput::make('display_name')->required(),
                    TextInput::make('unit')->required(),
                    TextInput::make('package'),
                    TextInput::make('status'),
                    Toggle::make('enabled'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('display_name')->searchable()->sortable(),
                TextColumn::make('unit')->badge()->searchable(),
                TextColumn::make('package')->toggleable(),
                TextColumn::make('status')->badge(),
                IconColumn::make('enabled')->boolean(),
                TextColumn::make('last_checked_at')->dateTime()->sortable(),
            ])
            ->recordActions([
                ...collect(['start', 'stop', 'restart', 'enable', 'disable'])->map(fn (string $operation) => Action::make($operation)
                    ->icon(match ($operation) {
                        'start' => Heroicon::OutlinedPlay,
                        'stop' => Heroicon::OutlinedStop,
                        'restart' => Heroicon::OutlinedArrowPath,
                        'enable' => Heroicon::OutlinedCheck,
                        default => Heroicon::OutlinedXMark,
                    })
                    ->requiresConfirmation()
                    ->action(function (SystemService $record) use ($operation): void {
                        $task = Task::create([
                            'user_id' => auth()->id(),
                            'label' => ucfirst($operation)." {$record->display_name}",
                            'action' => 'panel.system',
                            'arguments' => ['service', $operation, $record->unit],
                            'status' => 'pending',
                        ]);

                        ManageSystemService::dispatch($task->id, $record->id, $operation);
                        Notification::make()->title("Queued service task #{$task->id}")->success()->send();
                    }))->all(),
                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageSystemServices::route('/'),
        ];
    }
}
