<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskLog;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TaskStreamController extends Controller
{
    public function __invoke(Request $request, Task $task): Response
    {
        abort_unless($task->user_id === null || $task->user_id === auth()->id(), 403);

        return response()->stream(function () use ($task): void {
            @set_time_limit(0);

            $lastId = max(0, (int) $request->query('after', 0));

            while (! connection_aborted()) {
                TaskLog::query()
                    ->where('task_id', $task->id)
                    ->where('id', '>', $lastId)
                    ->orderBy('id')
                    ->each(function (TaskLog $line) use (&$lastId): void {
                        $lastId = $line->id;

                        echo "event: output\n";
                        echo 'data: '.json_encode([
                            'type' => $line->type,
                            'output' => $line->output,
                        ], JSON_THROW_ON_ERROR)."\n\n";

                        @ob_flush();
                        flush();
                    });

                $status = $task->fresh()->status;

                if (! in_array($status, ['pending', 'running'], true)) {
                    echo "event: complete\n";
                    echo 'data: '.json_encode(['status' => $status], JSON_THROW_ON_ERROR)."\n\n";
                    @ob_flush();
                    flush();

                    break;
                }

                usleep(250000);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
