<?php

namespace App\Filament\Resources\ServerSites;

use App\Filament\Resources\ServerSites\Pages\ManageServerSites;
use App\Jobs\DeployNginxSite;
use App\Jobs\DeploySiteArchive;
use App\Jobs\DeploySiteRepository;
use App\Models\ServerSite;
use App\Models\Task;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ServerSiteResource extends Resource
{
    protected static ?string $model = ServerSite::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Site')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->regex('/^[a-z0-9][a-z0-9.-]{0,252}$/')
                            ->helperText('Used as the Nginx config filename.'),
                        TextInput::make('domain')
                            ->required()
                            ->regex('/^(?=.{1,253}$)([a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/'),
                        TagsInput::make('aliases')->placeholder('www.example.com'),
                        TextInput::make('document_root')
                            ->required()
                            ->default(fn (): string => rtrim((string) config('server-panel.managed_root'), '/').'/example.com')
                            ->columnSpanFull(),
                        TextInput::make('system_user')
                            ->default('www-data')
                            ->regex('/^[a-z_][a-z0-9_-]{0,31}$/'),
                        TextInput::make('php_fpm_socket')
                            ->default(fn (): string => (string) config('server-panel.nginx.php_fpm_socket')),
                        Toggle::make('ssl_enabled')->label('SSL enabled'),
                    ]),
                Section::make('Git deployment')
                    ->columns(2)
                    ->schema([
                        TextInput::make('git_repository')
                            ->label('Repository')
                            ->nullable()
                            ->placeholder('https://github.com/user/repo.git')
                            ->notRegex('/\.\./')
                            ->regex('#^(https://[A-Za-z0-9._:-]+/[A-Za-z0-9._/-]+(\.git)?|git@[A-Za-z0-9._-]+:[A-Za-z0-9._/-]+(\.git)?|ssh://git@[A-Za-z0-9._-]+/[A-Za-z0-9._/-]+(\.git)?)$#')
                            ->columnSpanFull(),
                        TextInput::make('git_branch')
                            ->default('main')
                            ->doesntStartWith('-')
                            ->notRegex('/\.\./')
                            ->regex('#^[A-Za-z0-9._/-]{1,128}$#'),
                        TextInput::make('git_deploy_key_path')
                            ->nullable()
                            ->placeholder('/etc/server-panel/deploy-keys/example.com')
                            ->notRegex('/\.\./')
                            ->regex('#^/etc/server-panel/deploy-keys/[A-Za-z0-9._/-]+$#'),
                    ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('domain'),
                TextEntry::make('document_root'),
                TextEntry::make('git_repository')->placeholder('not connected')->columnSpanFull(),
                TextEntry::make('git_branch')->placeholder('main'),
                TextEntry::make('status')->badge(),
                TextEntry::make('last_deployed_at')->dateTime()->placeholder('not deployed'),
                TextEntry::make('last_git_deployed_at')->dateTime()->placeholder('not deployed'),
                TextEntry::make('last_file_uploaded_at')->dateTime()->placeholder('not uploaded'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('domain')->searchable(),
                TextColumn::make('document_root')->wrap()->toggleable(),
                TextColumn::make('git_repository')->label('Git')->wrap()->toggleable(),
                TextColumn::make('status')->badge(),
                IconColumn::make('ssl_enabled')->boolean(),
                TextColumn::make('last_deployed_at')->dateTime()->sortable(),
                TextColumn::make('last_git_deployed_at')->dateTime()->sortable()->toggleable(),
                TextColumn::make('last_file_uploaded_at')->dateTime()->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'draft' => 'Draft',
                    'active' => 'Active',
                    'failed' => 'Failed',
                ]),
            ])
            ->recordActions([
                Action::make('deploy')
                    ->icon(Heroicon::OutlinedRocketLaunch)
                    ->requiresConfirmation()
                    ->action(function (ServerSite $record): void {
                        $task = Task::create([
                            'user_id' => auth()->id(),
                            'label' => "Deploy {$record->domain}",
                            'action' => 'nginx.site',
                            'arguments' => ['deploy', $record->id],
                            'status' => 'pending',
                        ]);

                        DeployNginxSite::dispatch($task->id, $record->id);

                        Notification::make()
                            ->title("Queued deploy task #{$task->id}")
                            ->success()
                            ->send();
                    }),
                Action::make('uploadArchive')
                    ->label('Upload ZIP')
                    ->icon(Heroicon::OutlinedCloudArrowUp)
                    ->form([
                        FileUpload::make('archive')
                            ->label('Website ZIP')
                            ->disk('local')
                            ->directory(fn (ServerSite $record): string => "site-uploads/{$record->id}")
                            ->acceptedFileTypes([
                                'application/zip',
                                'application/x-zip',
                                'application/x-zip-compressed',
                                'multipart/x-zip',
                            ])
                            ->maxSize(102400)
                            ->required(),
                    ])
                    ->action(function (ServerSite $record, array $data): void {
                        $archive = is_array($data['archive']) ? (string) reset($data['archive']) : (string) $data['archive'];

                        $task = Task::create([
                            'user_id' => auth()->id(),
                            'label' => "Upload files for {$record->domain}",
                            'action' => 'panel.system',
                            'arguments' => ['site-upload', $archive, "{$record->document_root}/public"],
                            'status' => 'pending',
                        ]);

                        DeploySiteArchive::dispatch($task->id, $record->id, $archive);

                        Notification::make()
                            ->title("Queued upload task #{$task->id}")
                            ->success()
                            ->send();
                    }),
                Action::make('deployGit')
                    ->label('Deploy Git')
                    ->icon(Heroicon::OutlinedCodeBracket)
                    ->requiresConfirmation()
                    ->disabled(fn (ServerSite $record): bool => blank($record->git_repository))
                    ->action(function (ServerSite $record): void {
                        $task = Task::create([
                            'user_id' => auth()->id(),
                            'label' => "Deploy Git for {$record->domain}",
                            'action' => 'panel.system',
                            'arguments' => ['git-deploy', $record->git_repository, $record->git_branch ?: 'main', "{$record->document_root}/public"],
                            'status' => 'pending',
                        ]);

                        DeploySiteRepository::dispatch($task->id, $record->id);

                        Notification::make()
                            ->title("Queued Git deploy task #{$task->id}")
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
            'index' => ManageServerSites::route('/'),
        ];
    }
}
