<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

use function Pest\Laravel\get;
use function Pest\Laravel\postJson;

it('does not expose the broadcast debug endpoints outside local', function (): void {
    postJson('/api/test/broadcast')->assertNotFound();
    get('/websocket-test')->assertNotFound();
});

it('names template routes once', function (string $name): void {
    expect(Route::has($name))->toBeTrue();
})->with(['templates.index', 'templates.store', 'templates.show']);
