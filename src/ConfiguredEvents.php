<?php

namespace Reedware\LaravelEvents;

trait ConfiguredEvents
{
    /**
     * Register the application's event listeners.
     *
     * @return void
     */
    public function register()
    {
        $this->booting(function () {
            $this->registerEvents();
        });
    }

    /**
     * Register the application's event listeners.
     *
     * @return void
     */
    protected function registerEvents()
    {
        $dispatcher = $this->app->make('events');

        $events = $this->getEvents();

        foreach ($events as $event => $listeners) {
            foreach ($listeners as $listener) {
                $dispatcher->listen($event, $listener);
            }
        }

        foreach ($this->subscribe as $subscriber) {
            $dispatcher->subscribe($subscriber);
        }
    }

    /**
     * Returns the events and handlers.
     *
     * @return array
     */
    public function listens()
    {
        return $this->configuredEvents();
    }

    /**
     * Returns the configured events for the application.
     *
     * @return array
     */
    public function configuredEvents()
    {
        // Determine the event configuration
        $config = $this->app->config->get('events');

        // Otherwise, return the normalized event configuration
        return NormalizeEvents::normalize(
            $config['listen'] ?? [],
            $config['subscribe'] ?? [],
            $config['observe'] ?? [],
            $config['models'] ?? []
        );
    }
}