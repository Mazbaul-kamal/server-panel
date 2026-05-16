<?php

use App\Http\Controllers\TaskStreamController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

Route::middleware('auth')->get('/tasks/{task}/stream', TaskStreamController::class)
    ->name('tasks.stream');
