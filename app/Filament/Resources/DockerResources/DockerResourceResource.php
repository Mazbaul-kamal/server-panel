<?php

namespace App\Filament\Resources\DockerResources;

use App\Filament\Resources\DockerResources\Pages\ManageDockerResources;
use App\Models\Hosting\DockerResource;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\KeyValue;
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

class DockerResourceResource extends Resource
{
    protected static ?string $model = DockerResource::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static string|\UnitEnum|null $navigationGroup = 'Runtime';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Docker resource')
                ->columns(2)
                ->schema([
                    TextInput::make('name')->required()->maxLength(255),
                    Select::make('type')
                        ->required()
                        ->default('container')
                        ->options([
                            'container' => 'Container',
                            'image' => 'Image',
                            'volume' => 'Volume',
                            'network' => 'Network',
                            'compose' => 'Compose stack',
                        ]),
                    TextInput::make('image')->placeholder('nginx:stable'),
                    Select::make('status')
                        ->required()
                        ->default('unknown')
                        ->options([
                            'unknown' => 'Unknown',
                            'running' => 'Running',
                            'stopped' => 'Stopped',
                            'created' => 'Created',
                            'failed' => 'Failed',
                        ]),
                    TagsInput::make('ports')->placeholder('8080:80/tcp')->columnSpanFull(),
                    KeyValue::make('metadata')->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('type')->badge()->sortable(),
                TextColumn::make('image')->searchable()->placeholder('n/a'),
                TextColumn::make('status')->badge(),
                TextColumn::make('ports')->listWithLineBreaks()->limitList(3)->toggleable(),
                TextColumn::make('last_seen_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')->options([
                    'container' => 'Container',
                    'image' => 'Image',
                    'volume' => 'Volume',
                    'network' => 'Network',
                    'compose' => 'Compose stack',
                ]),
                SelectFilter::make('status')->options([
                    'unknown' => 'Unknown',
                    'running' => 'Running',
                    'stopped' => 'Stopped',
                    'created' => 'Created',
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
            'index' => ManageDockerResources::route('/'),
        ];
    }
}
