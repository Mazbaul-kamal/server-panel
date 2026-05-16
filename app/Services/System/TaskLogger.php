<?php

namespace App\Services\System;

use App\Events\TaskOutput;
use App\Models\Task;
use App\Models\TaskLog;

final class TaskLogger
{
    public function start(Task $task): void
    {
        $task->forceFill([
            'status' => 'running',
            'started_at' => now(),
        ])->save();
    }

    public function line(Task $task, string $type, string $output): void
    {
        TaskLog::create([
            'task_id' => $task->id,
            'type' => $type,
            'output' => $output,
        ]);

        broadcast(new TaskOutput($task->id, $type, $output));
    }

    public function finish(Task $task, int $exitCode = 0): void
    {
        $task->forceFill([
            'status' => 'finished',
            'exit_code' => $exitCode,
            'finished_at' => now(),
        ])->save();
    }

    public function fail(Task $task, string $message): void
    {
        $task->forceFill([
            'status' => 'failed',
            'error' => $message,
            'finished_at' => now(),
        ])->save();

        $this->line($task, 'stderr', $message.PHP_EOL);
    }
}
