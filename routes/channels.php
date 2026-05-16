<?php

use App\Models\Task;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('tasks.{taskId}', function ($user, int $taskId) {
    return Task::query()
        ->whereKey($taskId)
        ->where(function ($query) use ($user): void {
            $query->whereNull('user_id')->orWhere('user_id', $user->id);
        })
        ->exists();
});
