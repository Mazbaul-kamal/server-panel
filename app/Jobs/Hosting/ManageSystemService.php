<?php

namespace App\Jobs\Hosting;

use App\Models\Hosting\SystemService;
use App\Models\Task;
use App\Services\System\ServerAction;
use App\Services\System\TaskLogger;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ManageSystemService implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 900;

    public function __construct(
        public int $taskId,
        public int $serviceId,
        public string $operation,
    ) {
        $this->onQueue('system');
    }

    public function handle(ServerAction $server, TaskLogger $logger): void
    {
        $task = Task::findOrFail($this->taskId);
        $service = SystemService::findOrFail($this->serviceId);

        $logger->start($task);

        try {
            $result = $server->panelSystem(['service', $this->operation, $service->unit], fn (string $type, string $output) => $logger->line($task, $type, $output));

            $service->forceFill([
                'status' => match ($this->operation) {
                    'start', 'restart', 'reload' => 'running',
                    'stop' => 'stopped',
                    default => $service->status,
                },
                'enabled' => $this->operation === 'enable' ? true : ($this->operation === 'disable' ? false : $service->enabled),
                'last_checked_at' => now(),
            ])->save();

            $logger->finish($task, $result->exitCode());
        } catch (Throwable $e) {
            $logger->fail($task, $e->getMessage());

            throw $e;
        }
    }
}
