<?php

namespace App\Filament\Resources\FirewallRules;

use App\Filament\Resources\FirewallRules\Pages\ManageFirewallRules;
use App\Jobs\Hosting\ApplyFirewallRule;
use App\Models\Hosting\FirewallRule;
use App\Models\Task;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FirewallRuleResource extends Resource
{
    protected static ?string $model = FirewallRule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static string|\UnitEnum|null $navigationGroup = 'Hosting';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Rule')
                ->columns(2)
                ->schema([
                    Select::make('action')
                        ->required()
                        ->options([
                            'allow' => 'Allow',
                            'deny' => 'Deny',
                            'delete-allow' => 'Delete allow',
                            'delete-deny' => 'Delete deny',
                        ]),
                    Select::make('protocol')
                        ->required()
                        ->default('tcp')
                        ->options([
                            'tcp' => 'TCP',
                            'udp' => 'UDP',
                        ]),
                    TextInput::make('port')
                        ->required()
                        ->integer()
                        ->minValue(1)
                        ->maxValue(65535),
                    TextInput::make('source')
                        ->required()
                        ->default('any')
                        ->regex('/^(any|[0-9a-fA-F:.\/]{3,64})$/'),
                    Select::make('status')
                        ->required()
                        ->default('draft')
                        ->options([
                            'draft' => 'Draft',
                            'applying' => 'Applying',
                            'applied' => 'Applied',
                            'failed' => 'Failed',
                        ]),
                    Textarea::make('description')->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('action')->badge()->sortable(),
                TextColumn::make('protocol')->badge(),
                TextColumn::make('port')->sortable(),
                TextColumn::make('source')->searchable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('last_applied_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('action')->options([
                    'allow' => 'Allow',
                    'deny' => 'Deny',
                    'delete-allow' => 'Delete allow',
                    'delete-deny' => 'Delete deny',
                ]),
                SelectFilter::make('status')->options([
                    'draft' => 'Draft',
                    'applying' => 'Applying',
                    'applied' => 'Applied',
                    'failed' => 'Failed',
                ]),
            ])
            ->recordActions([
                Action::make('apply')
                    ->icon(Heroicon::OutlinedShieldCheck)
                    ->requiresConfirmation()
                    ->action(function (FirewallRule $record): void {
                        $task = Task::create([
                            'user_id' => auth()->id(),
                            'label' => "Apply firewall {$record->action} {$record->port}/{$record->protocol}",
                            'action' => 'panel.system',
                            'arguments' => ['firewall', $record->action, (string) $record->port, $record->protocol, $record->source],
                            'status' => 'pending',
                        ]);

                        $record->forceFill(['status' => 'applying'])->save();
                        ApplyFirewallRule::dispatch($task->id, $record->id);

                        Notification::make()->title("Queued firewall task #{$task->id}")->success()->send();
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
            'index' => ManageFirewallRules::route('/'),
        ];
    }
}
