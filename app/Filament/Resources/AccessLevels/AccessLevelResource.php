<?php

namespace App\Filament\Resources\AccessLevels;

use App\Filament\Resources\AccessLevels\Pages\ManageAccessLevels;
use App\Models\Hosting\AccessLevel;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class AccessLevelResource extends Resource
{
    protected static ?string $model = AccessLevel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static string|\UnitEnum|null $navigationGroup = 'Hosting';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Access level')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Set $set, ?string $state): mixed => $set('slug', Str::slug($state ?? ''))),
                    TextInput::make('slug')
                        ->required()
                        ->regex('/^[a-z0-9][a-z0-9-]{0,63}$/')
                        ->unique(ignoreRecord: true),
                    CheckboxList::make('permissions')
                        ->required()
                        ->columns(3)
                        ->options([
                            'websites.manage' => 'Websites',
                            'ssl.manage' => 'SSL',
                            'dns.manage' => 'DNS',
                            'databases.manage' => 'Databases',
                            'ftp.manage' => 'FTP',
                            'mail.manage' => 'Mail',
                            'file-manager.manage' => 'File manager',
                            'php.manage' => 'PHP',
                            'firewall.manage' => 'Firewall',
                            'backups.manage' => 'Backups',
                            'docker.manage' => 'Docker',
                            'monitoring.view' => 'Monitoring',
                            'system-services.manage' => 'Services',
                            'users.manage' => 'Users',
                            'tasks.manage' => 'Tasks',
                        ])
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('slug')->badge()->searchable(),
                TextColumn::make('permissions')->listWithLineBreaks()->limitList(5),
                TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->recordActions([
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
            'index' => ManageAccessLevels::route('/'),
        ];
    }
}
