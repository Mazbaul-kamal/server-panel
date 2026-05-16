<?php

namespace App\Filament\Resources\DnsRecords;

use App\Filament\Resources\DnsRecords\Pages\ManageDnsRecords;
use App\Models\Hosting\DnsRecord;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DnsRecordResource extends Resource
{
    protected static ?string $model = DnsRecord::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAtSymbol;

    protected static string|\UnitEnum|null $navigationGroup = 'Hosting';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('DNS record')
                ->columns(2)
                ->schema([
                    Select::make('dns_zone_id')
                        ->relationship('zone', 'domain')
                        ->required()
                        ->searchable()
                        ->preload(),
                    Select::make('type')
                        ->required()
                        ->default('A')
                        ->options([
                            'A' => 'A',
                            'AAAA' => 'AAAA',
                            'CNAME' => 'CNAME',
                            'MX' => 'MX',
                            'TXT' => 'TXT',
                            'SRV' => 'SRV',
                            'CAA' => 'CAA',
                            'NS' => 'NS',
                        ]),
                    TextInput::make('name')->required()->maxLength(255),
                    TextInput::make('ttl')
                        ->required()
                        ->integer()
                        ->minValue(60)
                        ->maxValue(86400)
                        ->default(3600),
                    TextInput::make('priority')
                        ->integer()
                        ->minValue(0)
                        ->maxValue(65535),
                    Textarea::make('content')->required()->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('zone.domain')->label('Zone')->searchable()->sortable(),
                TextColumn::make('name')->searchable(),
                TextColumn::make('type')->badge()->sortable(),
                TextColumn::make('content')->wrap()->searchable(),
                TextColumn::make('ttl')->sortable(),
                TextColumn::make('priority')->placeholder('n/a'),
            ])
            ->filters([
                SelectFilter::make('type')->options([
                    'A' => 'A',
                    'AAAA' => 'AAAA',
                    'CNAME' => 'CNAME',
                    'MX' => 'MX',
                    'TXT' => 'TXT',
                    'SRV' => 'SRV',
                    'CAA' => 'CAA',
                    'NS' => 'NS',
                ]),
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
            'index' => ManageDnsRecords::route('/'),
        ];
    }
}
