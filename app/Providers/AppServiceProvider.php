<?php

namespace App\Providers;

use Illuminate\Console\Scheduling\Schedule;
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
        // Register monitoring scheduled tasks
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);

            // Check for offline servers every 5 minutes
            $schedule->command('monitor:check-offline')
                     ->everyFiveMinutes()
                     ->withoutOverlapping()
                     ->runInBackground();
        });
    }
}
