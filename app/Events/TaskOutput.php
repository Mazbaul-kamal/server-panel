<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class TaskOutput implements ShouldBroadcastNow
{
    public function __construct(
        public int $taskId,
        public string $type,
        public string $output,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("tasks.{$this->taskId}");
    }

    public function broadcastAs(): string
    {
        return 'task.output';
    }
}
