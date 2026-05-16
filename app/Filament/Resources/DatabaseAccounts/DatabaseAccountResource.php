<?php

namespace App\Filament\Resources\DatabaseAccounts;

use App\Filament\Resources\DatabaseAccounts\Pages\ManageDatabaseAccounts;
use App\Jobs\CreateDatabaseAccount;
use App\Models\DatabaseAccount;
use App\Models\Task;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DatabaseAccountResource extends Resource
{
    protected static ?string $model = DatabaseAccount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Database')
                    ->columns(2)
                    ->schema([
                        TextInput::make('database')
                            ->required()
                            ->regex('/^[a-zA-Z0-9_]{1,64}$/'),
                        TextInput::make('username')
                            ->required()
                            ->regex('/^[a-zA-Z0-9_]{1,64}$/'),
                        TextInput::make('host')
                            ->required()
                            ->default('localhost')
                            ->regex('/^[a-zA-Z0-9_.:%-]{1,255}$/'),
                        CheckboxList::make('privileges')
                            ->required()
                            ->columns(3)
                            ->options([
                                'SELECT' => 'SELECT',
                                'INSERT' => 'INSERT',
                                'UPDATE' => 'UPDATE',
                                'DELETE' => 'DELETE',
                                'CREATE' => 'CREATE',
                                'DROP' => 'DROP',
                                'INDEX' => 'INDEX',
                                'ALTER' => 'ALTER',
                                'REFERENCES' => 'REFERENCES',
                                'CREATE TEMPORARY TABLES' => 'CREATE TEMPORARY TABLES',
                                'LOCK TABLES' => 'LOCK TABLES',
                            ])
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('database'),
                TextEntry::make('username'),
                TextEntry::make('host'),
                TextEntry::make('status')->badge(),
                KeyValueEntry::make('privileges')->state(fn (DatabaseAccount $record): array => array_fill_keys($record->privileges ?? [], 'allowed')),
                TextEntry::make('last_provisioned_at')->dateTime()->placeholder('not provisioned'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('database')->searchable()->sortable(),
                TextColumn::make('username')->searchable(),
                TextColumn::make('host'),
                TextColumn::make('status')->badge(),
                TextColumn::make('last_provisioned_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'draft' => 'Draft',
                    'active' => 'Active',
                    'failed' => 'Failed',
                ]),
            ])
            ->recordActions([
                Action::make('provision')
                    ->icon(Heroicon::OutlinedCircleStack)
                    ->requiresConfirmation()
                    ->form([
                        TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->required()
                            ->minLength(16),
                    ])
                    ->action(function (DatabaseAccount $record, array $data): void {
                        $task = Task::create([
                            'user_id' => auth()->id(),
                            'label' => "Provision database {$record->database}",
                            'action' => 'database.provision',
                            'arguments' => [$record->id],
                            'status' => 'pending',
                        ]);

                        CreateDatabaseAccount::dispatch($task->id, $record->id, $data['password']);

                        Notification::make()
                            ->title("Queued database task #{$task->id}")
                            ->success()
                            ->send();
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
            'index' => ManageDatabaseAccounts::route('/'),
        ];
    }
}
