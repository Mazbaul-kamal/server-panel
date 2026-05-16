<?php

namespace App\Jobs;

use App\Models\DatabaseAccount;
use App\Models\Task;
use App\Services\System\MysqlAdmin;
use App\Services\System\TaskLogger;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class CreateDatabaseAccount implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 600;

    public function __construct(
        public int $taskId,
        public int $accountId,
        public string $password,
    ) {
        $this->onQueue('system');
    }

    public function handle(MysqlAdmin $mysql, TaskLogger $logger): void
    {
        $task = Task::findOrFail($this->taskId);
        $account = DatabaseAccount::findOrFail($this->accountId);

        $logger->start($task);

        try {
            $mysql->createDatabaseUser(
                $account->database,
                $account->username,
                $this->password,
                $account->privileges,
                $account->host,
            );

            $account->forceFill([
                'status' => 'active',
                'last_provisioned_at' => now(),
            ])->save();

            $logger->line($task, 'stdout', "Database and user provisioned.\n");
            $logger->finish($task);
        } catch (Throwable $e) {
            $account->forceFill(['status' => 'failed'])->save();
            $logger->fail($task, $e->getMessage());

            throw $e;
        }
    }
}
