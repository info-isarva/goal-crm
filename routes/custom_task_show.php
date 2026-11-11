<?php
// routes/web.php
use Illuminate\Support\Facades\Route;

Route::get('/tasks/{task}/show', [App\Http\Controllers\TaskController::class, 'show'])->name('tasks.show');
