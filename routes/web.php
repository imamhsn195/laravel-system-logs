<?php

use Illuminate\Support\Facades\Route;
use ImamHasan\SystemLogs\Http\Controllers\SystemLogController;

Route::get('/', [SystemLogController::class, 'index'])
    ->name('index');

Route::delete('/', [SystemLogController::class, 'destroy'])
    ->name('destroy');

Route::delete('/bulk', [SystemLogController::class, 'bulkDelete'])
    ->name('bulk-delete');

Route::delete('/bulk-by-filters', [SystemLogController::class, 'bulkDeleteByFilters'])
    ->name('bulk-delete-by-filters');

