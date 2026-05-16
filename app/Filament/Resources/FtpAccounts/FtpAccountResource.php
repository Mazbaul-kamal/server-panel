<?php

namespace App\Filament\Resources\FtpAccounts;

use App\Filament\Resources\FtpAccounts\Pages\ManageFtpAccounts;
use App\Models\Hosting\FtpAccount;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FtpAccountResource extends Resource
{
    protected static ?string $model = FtpAccount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolderOpen;

    protected static string|\UnitEnum|null $navigationGroup = 'Web Hosting';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('FTP Account')
                ->columns(2)
                ->schema([
                    TextInput::make('username')->required()->regex('/^[a-z_][a-z0-9_-]{0,31}$/'),
                    TextInput::make('status')->default('draft')->required(),
                    TextInput::make('root_path')
                        ->required()
                        ->default(fn (): string => rtrim((string) config('server-panel.managed_root'), '/').'/example.com')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('username')->searchable()->sortable(),
                TextColumn::make('root_path')->wrap(),
                TextColumn::make('status')->badge(),
                TextColumn::make('last_provisioned_at')->dateTime()->sortable(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageFtpAccounts::route('/'),
        ];
    }
}
