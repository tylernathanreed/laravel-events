<?php

namespace Reedware\LaravelEvents;

use Illuminate\Support\ServiceProvider;

class EventConfigurationServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '../../config/events.php' => $this->app->configPath('events.php'),
        ]);
    }
}
