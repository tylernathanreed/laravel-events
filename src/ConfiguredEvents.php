<?php

namespace Reedware\LaravelEvents;

trait ConfiguredEvents
{
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
    protected function configuredEvents()
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