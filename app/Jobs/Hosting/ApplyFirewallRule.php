<?php

namespace App\Jobs\Hosting;

use App\Models\Hosting\FirewallRule;
use App\Models\Task;
use App\Services\System\ServerAction;
use App\Services\System\TaskLogger;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class ApplyFirewallRule implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 300;

    public function __construct(
        public int $taskId,
        public int $ruleId,
    ) {
        $this->onQueue('system');
    }

    public function handle(ServerAction $server, TaskLogger $logger): void
    {
        $task = Task::findOrFail($this->taskId);
        $rule = FirewallRule::findOrFail($this->ruleId);

        $logger->start($task);

        try {
            $result = $server->panelSystem([
                'firewall',
                $rule->action,
                (string) $rule->port,
                $rule->protocol,
                $rule->source,
            ], fn (string $type, string $output) => $logger->line($task, $type, $output));

            $rule->forceFill([
                'status' => 'applied',
                'last_applied_at' => now(),
            ])->save();

            $logger->finish($task, $result->exitCode());
        } catch (Throwable $e) {
            $rule->forceFill(['status' => 'failed'])->save();
            $logger->fail($task, $e->getMessage());

            throw $e;
        }
    }
}
