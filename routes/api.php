<?php

use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ObservabilityController;
use App\Http\Controllers\TemplateController;
use Illuminate\Support\Facades\Route;

Route::get('/metrics', [ObservabilityController::class, 'metrics'])->name('observability.metrics');
Route::get('/health', [ObservabilityController::class, 'health'])->name('observability.health');

/** Debug-only: triggers a sample NotificationStatusChanged broadcast for WebSocket / Reverb verification (see README). */
Route::post('/test/broadcast', [NotificationController::class, 'testBroadcast'])->name('test.broadcast');

Route::group(['prefix' => 'notifications', 'as' => 'notifications.'], function (): void {
    Route::post('/', [NotificationController::class, 'store'])->name('store');
    Route::post('/batch', [NotificationController::class, 'storeBatch'])->name('store.batch');
    Route::get('/', [NotificationController::class, 'index'])->name('index');
    Route::get('/batch/{batchId}', [NotificationController::class, 'showBatch'])->name('show.batch');
    Route::get('/{notification}', [NotificationController::class, 'show'])->name('show');
    Route::patch('/{notification}/cancel', [NotificationController::class, 'cancel'])->name('cancel');
});

Route::group(['prefix' => 'templates', 'as' => 'templates.'], function (): void {
    Route::post('/', [TemplateController::class, 'store'])->name('templates.store');
    Route::get('/', [TemplateController::class, 'index'])->name('templates.index');
    Route::get('/{template}', [TemplateController::class, 'show'])->name('templates.show');
});
