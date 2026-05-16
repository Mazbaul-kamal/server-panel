<?php

namespace App\Jobs;

use App\Models\ServerSite;
use App\Models\Task;
use App\Services\System\ServerAction;
use App\Services\System\TaskLogger;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Throwable;

class DeploySiteArchive implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 14400;

    public function __construct(
        public int $taskId,
        public int $siteId,
        public string $archivePath,
    ) {
        $this->onQueue('system');
    }

    public function handle(ServerAction $server, TaskLogger $logger): void
    {
        $task = Task::findOrFail($this->taskId);
        $site = ServerSite::findOrFail($this->siteId);

        $logger->start($task);

        try {
            $archive = Storage::disk('local')->path($this->archivePath);
            $destination = rtrim($site->document_root, '/').'/public';
            $owner = $site->system_user ?: 'www-data';

            $logger->line($task, 'stdout', "Uploading archive to {$destination}\n");
            $result = $server->uploadSiteArchive($archive, $destination, $owner, fn (string $type, string $output) => $logger->line($task, $type, $output));

            $site->forceFill([
                'last_file_uploaded_at' => now(),
                'status' => 'active',
            ])->save();

            $logger->finish($task, $result->exitCode());
        } catch (Throwable $e) {
            $logger->fail($task, $e->getMessage());

            throw $e;
        } finally {
            Storage::disk('local')->delete($this->archivePath);
        }
    }
}
