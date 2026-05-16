<?php

namespace App\Filament\Resources\Tasks\Pages;

use App\Filament\Resources\Tasks\TaskResource;
use App\Jobs\BackupSites;
use App\Jobs\RenewSslCertificates;
use App\Jobs\RotateSystemLogs;
use App\Jobs\RunSystemCommand;
use App\Models\Task;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;

class ManageTasks extends ManageRecords
{
    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->systemAction('aptUpdate', 'APT update', Heroicon::OutlinedArrowPath, 'apt.update', ['update']),
            Action::make('renewSsl')
                ->label('Renew SSL')
                ->icon(Heroicon::OutlinedCheckBadge)
                ->requiresConfirmation()
                ->action(function (): void {
                    $task = $this->createTask('Renew SSL certificates', 'certbot.renew', ['renew', '--quiet']);
                    RenewSslCertificates::dispatch($task->id);
                    $this->notifyQueued($task);
                }),
            Action::make('backupSites')
                ->label('Backup')
                ->icon(Heroicon::OutlinedArchiveBoxArrowDown)
                ->requiresConfirmation()
                ->action(function (): void {
                    $task = $this->createTask('Backup sites', 'backup.sites');
                    BackupSites::dispatch($task->id);
                    $this->notifyQueued($task);
                }),
            Action::make('rotateLogs')
                ->label('Rotate logs')
                ->icon(Heroicon::OutlinedArrowPathRoundedSquare)
                ->requiresConfirmation()
                ->action(function (): void {
                    $task = $this->createTask('Rotate logs', 'logs.rotate', ['/etc/logrotate.conf']);
                    RotateSystemLogs::dispatch($task->id);
                    $this->notifyQueued($task);
                }),
        ];
    }

    /**
     * @param  array<int, string>  $arguments
     */
    private function systemAction(string $name, string $label, Heroicon $icon, string $action, array $arguments = []): Action
    {
        return Action::make($name)
            ->label($label)
            ->icon($icon)
            ->requiresConfirmation()
            ->action(function () use ($label, $action, $arguments): void {
                $task = $this->createTask($label, $action, $arguments);
                RunSystemCommand::dispatch($task->id, $action, $arguments);
                $this->notifyQueued($task);
            });
    }

    /**
     * @param  array<int, string>  $arguments
     */
    private function createTask(string $label, string $action, array $arguments = []): Task
    {
        return Task::create([
            'user_id' => auth()->id(),
            'label' => $label,
            'action' => $action,
            'arguments' => $arguments,
            'status' => 'pending',
        ]);
    }

    private function notifyQueued(Task $task): void
    {
        Notification::make()
            ->title("Queued task #{$task->id}")
            ->success()
            ->send();
    }
}
