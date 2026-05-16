<?php

namespace App\Filament\Resources\MailDomains;

use App\Filament\Resources\MailDomains\Pages\ManageMailDomains;
use App\Models\Hosting\MailDomain;
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

class MailDomainResource extends Resource
{
    protected static ?string $model = MailDomain::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static string|\UnitEnum|null $navigationGroup = 'Email';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Mail Domain')
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
                TextColumn::make('accounts_count')->counts('accounts')->label('Accounts'),
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
            'index' => ManageMailDomains::route('/'),
        ];
    }
}
