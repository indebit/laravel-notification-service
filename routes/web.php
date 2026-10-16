<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/docs');

Route::get('/docs', fn () => view('docs'))->name('docs');

// Local development only: a page that subscribes to status broadcasts over Reverb.
if (app()->environment('local')) {
    Route::get('/websocket-test', fn () => view('websocket-test'))->name('websocket.test');
}
