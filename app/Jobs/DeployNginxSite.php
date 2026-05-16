<?php

namespace App\Jobs;

use App\Models\ServerSite;
use App\Models\Task;
use App\Services\System\NginxSiteService;
use App\Services\System\TaskLogger;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class DeployNginxSite implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 7200;

    public function __construct(
        public int $taskId,
        public int $siteId,
    ) {
        $this->onQueue('system');
    }

    public function handle(NginxSiteService $nginx, TaskLogger $logger): void
    {
        $task = Task::findOrFail($this->taskId);
        $site = ServerSite::findOrFail($this->siteId);

        $logger->start($task);
        $logger->line($task, 'stdout', "Deploying {$site->domain}\n");

        try {
            $nginx->deploy($site);
            $logger->line($task, 'stdout', "Nginx site deployed and reloaded.\n");
            $logger->finish($task);
        } catch (Throwable $e) {
            $logger->fail($task, $e->getMessage());

            throw $e;
        }
    }
}
