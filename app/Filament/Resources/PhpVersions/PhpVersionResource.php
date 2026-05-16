<?php

namespace App\Filament\Resources\PhpVersions;

use App\Filament\Resources\PhpVersions\Pages\ManagePhpVersions;
use App\Models\Hosting\PhpVersion;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PhpVersionResource extends Resource
{
    protected static ?string $model = PhpVersion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCodeBracket;

    protected static string|\UnitEnum|null $navigationGroup = 'Runtime';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('PHP runtime')
                ->columns(2)
                ->schema([
                    TextInput::make('version')
                        ->required()
                        ->regex('/^[0-9]+(\.[0-9]+){1,2}$/'),
                    Select::make('status')
                        ->required()
                        ->default('available')
                        ->options([
                            'available' => 'Available',
                            'installed' => 'Installed',
                            'default' => 'Default',
                            'disabled' => 'Disabled',
                        ]),
                    TextInput::make('fpm_unit')->placeholder('php8.3-fpm'),
                    TextInput::make('fpm_socket')->placeholder('/run/php/php8.3-fpm.sock')->columnSpanFull(),
                    TagsInput::make('extensions')->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('version')
            ->columns([
                TextColumn::make('version')->searchable()->sortable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('fpm_unit')->badge()->placeholder('n/a'),
                TextColumn::make('fpm_socket')->wrap()->toggleable(),
                TextColumn::make('extensions')->listWithLineBreaks()->limitList(4)->toggleable(),
                TextColumn::make('installed_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'available' => 'Available',
                    'installed' => 'Installed',
                    'default' => 'Default',
                    'disabled' => 'Disabled',
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
            'index' => ManagePhpVersions::route('/'),
        ];
    }
}
