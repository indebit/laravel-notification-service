<?php

namespace App\Providers;

use App\Jobs\SendNotificationJob;
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
        RateLimiter::for('notification-channel', fn (SendNotificationJob $job) => Limit::perSecond(100)
            ->by($job->notification->channel->value));
    }
}
