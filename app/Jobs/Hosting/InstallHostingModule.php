<?php

namespace App\Jobs\Hosting;

use App\Models\Hosting\HostingModule;
use App\Models\Task;
use App\Services\System\ServerAction;
use App\Services\System\TaskLogger;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class InstallHostingModule implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 7200;

    public function __construct(
        public int $taskId,
        public int $moduleId,
    ) {
        $this->onQueue('system');
    }

    public function handle(ServerAction $server, TaskLogger $logger): void
    {
        $task = Task::findOrFail($this->taskId);
        $module = HostingModule::findOrFail($this->moduleId);

        $logger->start($task);

        try {
            $result = $server->panelSystem(['install-module', $module->key], fn (string $type, string $output) => $logger->line($task, $type, $output));

            $module->forceFill([
                'status' => 'installed',
                'enabled' => true,
                'installed_at' => now(),
            ])->save();

            $logger->finish($task, $result->exitCode());
        } catch (Throwable $e) {
            $module->forceFill(['status' => 'failed'])->save();
            $logger->fail($task, $e->getMessage());

            throw $e;
        }
    }
}
