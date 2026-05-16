<?php

namespace App\Filament\Resources\HostingModules;

use App\Filament\Resources\HostingModules\Pages\ManageHostingModules;
use App\Jobs\Hosting\InstallHostingModule;
use App\Models\Hosting\HostingModule;
use App\Models\Task;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Textarea;
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

class HostingModuleResource extends Resource
{
    protected static ?string $model = HostingModule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|\UnitEnum|null $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Module')
                ->columns(2)
                ->schema([
                    TextInput::make('key')->required()->disabled(),
                    TextInput::make('name')->required(),
                    TextInput::make('category')->required(),
                    TextInput::make('status')->required(),
                    Toggle::make('enabled'),
                    Textarea::make('description')->columnSpanFull(),
                    KeyValue::make('metadata')->columnSpanFull(),
                ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('category')
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('category')->badge()->sortable(),
                TextColumn::make('status')->badge(),
                IconColumn::make('enabled')->boolean(),
                TextColumn::make('packages')->listWithLineBreaks()->limitList(3),
                TextColumn::make('services')->listWithLineBreaks()->limitList(3),
                TextColumn::make('installed_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('category')->options(fn (): array => HostingModule::query()
                    ->distinct()
                    ->pluck('category', 'category')
                    ->all()),
                SelectFilter::make('status')->options([
                    'available' => 'Available',
                    'installing' => 'Installing',
                    'installed' => 'Installed',
                    'failed' => 'Failed',
                ]),
            ])
            ->recordActions([
                Action::make('install')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->requiresConfirmation()
                    ->action(function (HostingModule $record): void {
                        $task = Task::create([
                            'user_id' => auth()->id(),
                            'label' => "Install {$record->name}",
                            'action' => 'panel.system',
                            'arguments' => ['install-module', $record->key],
                            'status' => 'pending',
                        ]);

                        $record->forceFill(['status' => 'installing'])->save();
                        InstallHostingModule::dispatch($task->id, $record->id);

                        Notification::make()->title("Queued module install #{$task->id}")->success()->send();
                    }),
                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageHostingModules::route('/'),
        ];
    }
}
