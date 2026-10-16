<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('channel-sms', fn () => Limit::perSecond(100));
        RateLimiter::for('channel-email', fn () => Limit::perSecond(100));
        RateLimiter::for('channel-push', fn () => Limit::perSecond(100));
    }
}
