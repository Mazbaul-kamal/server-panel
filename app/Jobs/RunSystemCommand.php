<?php

namespace App\Jobs;

use App\Models\Task;
use App\Services\System\ServerAction;
use App\Services\System\TaskLogger;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class RunSystemCommand implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 7200;

    /**
     * @param  array<int, string>  $arguments
     */
    public function __construct(
        public int $taskId,
        public string $action,
        public array $arguments = [],
    ) {
        $this->onQueue('system');
    }

    public function handle(ServerAction $server, TaskLogger $logger): void
    {
        $task = Task::findOrFail($this->taskId);
        $logger->start($task);

        try {
            $result = $server->runWhitelisted($this->action, $this->arguments, function (string $type, string $output) use ($logger, $task): void {
                $logger->line($task, $type, $output);
            });

            $logger->finish($task, $result->exitCode());
        } catch (Throwable $e) {
            $logger->fail($task, $e->getMessage());

            throw $e;
        }
    }
}
