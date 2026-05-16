<?php

namespace App\Jobs\Hosting;

use App\Models\Hosting\BackupPlan;
use App\Models\Hosting\BackupRun;
use App\Models\Task;
use App\Services\System\ServerAction;
use App\Services\System\TaskLogger;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class RunBackupPlan implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 14400;

    public function __construct(
        public int $taskId,
        public int $planId,
    ) {
        $this->onQueue('system');
    }

    public function handle(ServerAction $server, TaskLogger $logger): void
    {
        $task = Task::findOrFail($this->taskId);
        $plan = BackupPlan::findOrFail($this->planId);
        $run = BackupRun::create([
            'backup_plan_id' => $plan->id,
            'status' => 'running',
            'started_at' => now(),
        ]);

        $logger->start($task);

        try {
            $result = $server->panelSystem([
                'backup-path',
                $plan->source_path,
                $plan->destination_path,
                (string) $plan->retention_days,
            ], fn (string $type, string $output) => $logger->line($task, $type, $output));

            $plan->forceFill(['last_run_at' => now(), 'status' => 'ready'])->save();
            $run->forceFill(['status' => 'finished', 'finished_at' => now()])->save();

            $logger->finish($task, $result->exitCode());
        } catch (Throwable $e) {
            $plan->forceFill(['status' => 'failed'])->save();
            $run->forceFill(['status' => 'failed', 'error' => $e->getMessage(), 'finished_at' => now()])->save();
            $logger->fail($task, $e->getMessage());

            throw $e;
        }
    }
}
