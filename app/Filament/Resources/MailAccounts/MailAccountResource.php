<?php

namespace App\Filament\Resources\MailAccounts;

use App\Filament\Resources\MailAccounts\Pages\ManageMailAccounts;
use App\Models\Hosting\MailAccount;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MailAccountResource extends Resource
{
    protected static ?string $model = MailAccount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static string|\UnitEnum|null $navigationGroup = 'Hosting';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Mailbox')
                ->columns(2)
                ->schema([
                    Select::make('mail_domain_id')
                        ->relationship('domain', 'domain')
                        ->required()
                        ->searchable()
                        ->preload(),
                    TextInput::make('local_part')
                        ->required()
                        ->regex('/^[a-zA-Z0-9._%+-]{1,64}$/'),
                    TextInput::make('quota_mb')
                        ->required()
                        ->integer()
                        ->minValue(1)
                        ->maxValue(1048576)
                        ->default(1024),
                    Select::make('status')
                        ->required()
                        ->default('draft')
                        ->options([
                            'draft' => 'Draft',
                            'active' => 'Active',
                            'suspended' => 'Suspended',
                            'failed' => 'Failed',
                        ]),
                    KeyValue::make('metadata')->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('local_part')->searchable()->sortable(),
                TextColumn::make('domain.domain')->label('Domain')->searchable()->sortable(),
                TextColumn::make('quota_mb')->sortable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('last_provisioned_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'draft' => 'Draft',
                    'active' => 'Active',
                    'suspended' => 'Suspended',
                    'failed' => 'Failed',
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
            'index' => ManageMailAccounts::route('/'),
        ];
    }
}
