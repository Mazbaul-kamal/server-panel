<?php

namespace App\Jobs;

use App\Models\Task;
use App\Services\System\ServerAction;
use App\Services\System\TaskLogger;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class RenewSslCertificates implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 7200;

    public function __construct(public int $taskId)
    {
        $this->onQueue('system');
    }

    public function handle(ServerAction $server, TaskLogger $logger): void
    {
        $task = Task::findOrFail($this->taskId);
        $logger->start($task);

        try {
            $result = $server->renewCertificates(fn (string $type, string $output) => $logger->line($task, $type, $output));
            $logger->finish($task, $result->exitCode());
        } catch (Throwable $e) {
            $logger->fail($task, $e->getMessage());

            throw $e;
        }
    }
}
