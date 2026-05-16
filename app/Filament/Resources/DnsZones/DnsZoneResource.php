<?php

namespace App\Filament\Resources\DnsZones;

use App\Filament\Resources\DnsZones\Pages\ManageDnsZones;
use App\Models\Hosting\DnsZone;
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

class DnsZoneResource extends Resource
{
    protected static ?string $model = DnsZone::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAsiaAustralia;

    protected static string|\UnitEnum|null $navigationGroup = 'DNS';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('DNS Zone')
                ->columns(2)
                ->schema([
                    TextInput::make('domain')
                        ->required()
                        ->regex('/^(?=.{1,253}$)([a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/'),
                    TextInput::make('status')->default('draft')->required(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('domain')->searchable()->sortable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('records_count')->counts('records')->label('Records'),
                TextColumn::make('updated_at')->dateTime()->sortable(),
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
            'index' => ManageDnsZones::route('/'),
        ];
    }
}
