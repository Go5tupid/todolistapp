<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TaskController;

Route::get('/', [TaskController::class, 'index']);

Route::resource('tasks', TaskController::class);
Route::delete('tasks/bulk-delete', [TaskController::class, 'bulkDelete'])->name('tasks.bulk-delete');
Route::post('tasks/update-order', [TaskController::class, 'updateOrder'])->name('tasks.update-order');
Route::delete('attachments/{attachment}', [TaskController::class, 'deleteAttachment'])->name('attachments.destroy');
