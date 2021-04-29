<?php

namespace Reedware\LaravelEvents;

use Illuminate\Support\Reflector;
use Illuminate\Support\Str;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionMethod;

class NormalizeEvents
{
    /**
     * Normalizes the four types of event registration into a single event / listener mapping.
     *
     * @param  array  $listen
     * @param  array  $subscribe
     * @param  array  $observe
     * @param  array  $models
     *
     * @return array
     */
    public static function normalize(array $listen, array $subscribe, array $observe, array $models)
    {
        // Normalize each mapping type
        $normalize = [
            static::normalizeListenerMapping($listen),
            static::normalizeSubscriberMapping($subscribe),
            static::normalizeObserverMapping($observe),
            static::normalizeModelMapping($models)
        ];

        // Each normalized mapping will contain a keyed list of events
        // and their respective listeners. We will merge each list
        // into one final giant list and remove any duplicates.

        // Merge the lists
        $normalized = array_merge_recursive(...$normalize);

        // Sort the list
        ksort($normalized);

        // Return the list
        return $normalized;
    }

    /**
     * Normalizes the specified event / listener mapping.
     *
     * @param  array  $listen
     *
     * @return array
     */
    public static function normalizeListenerMapping(array $listen)
    {
        // The event / listener mapping is very close to a normalized
        // mapping. All we have to deal with here is explicit and
        // implicit mappings. This should be easy enough to do.

        // Initialize the normalized list
        $normalized = [];

        // Add each normalized listener to the event
        foreach($listen as $event => $listeners) {
            foreach($listeners as $listener) {
                $normalized[$event][] = static::normalizeListener($listener, $event);
            }
        }

        // Return the normalized list
        return $normalized;
    }

    /**
     * Normalizes the specified subscriber / event mapping.
     *
     * @param  array  $subscribe
     *
     * @return array
     */
    public static function normalizeSubscriberMapping(array $subscribe)
    {
        // The subscriber / event mapping is essentially inverted. We
        // can normalize it like it's an event / listener mapping
        // by swapping the event and listener position. Easy.

        // Initialize the normalized list
        $normalized = [];

        // Iterate through each subscriber mapping
        foreach($subscribe as $subscriber => $events) {

            // Iterate through each event
            foreach($events as $event) {

                // If the event is an array, swap things around
                if(is_array($event)) {

                    // Make the subscriber an array
                    $subscriber = [$subscriber, $event[1]];

                    // Make the event a string
                    $event = $event[0];

                }

                // Add the normalized event
                $normalized[$event][] = static::normalizeListener($subscriber, $event);

            }

        }

        // Return the normalized list
        return $normalized;
    }

    /**
     * Normalizes the specified listener.
     *
     * @param  string|array  $listener
     * @param  string        $event
     *
     * @return array  [Listener::class, "method"]
     *
     * @throws \InvalidArgumentException
     */
    public static function normalizeListener($listener, $event)
    {
        // If the listener is an array, it's already normalized
        if(is_array($listener)) {
            return $listener;
        }

        // If the listener is a string, check for class@method syntax
        if(is_string($listener) && strpos($listener, '@') !== false) {
            return Str::parseCallback($listener, 'handle');
        }

        // At this point, the listener should be a class string. If that
        // isn't the case, we're going to bail, as we don't know how
        // to handle what the developer has provided to us. Fun.

        // Make sure the listener is valid
        if(!is_string($listener) || !class_exists($listener)) {
            throw new InvalidArgumentException('Argument #1 passed to normalizeListener() must be a valid listener.');
        }

        // Reflect the listener
        $listener = new ReflectionClass($listener);

        // Make sure the listener can be instantiated
        if(!$listener->isInstantiable()) {
            throw new InvalidArgumentException('Argument #1 passed to normalizeListener() must be a valid listener.');
        }

        // At this point, we need to determine which method to use. We
        // will spin through a number of acceptable practices, and
        // use whichever one is best suited for this occasion.

        // Iterate through each of the listener's public methods
        foreach($listener->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {

            // We're only going to consider methods starting with "handle" and accepting a single parameter
            if(!Str::is('handle*', $method->name) || !isset($method->getParameters()[0])) {
                continue;
            }

            // Determine the parameter class name
            $type = Reflector::getParameterClassName($method->getParameters()[0]);

            // If no type-hint exists, skip it
            if(is_null($type)) {
                continue;
            }

            // Since subscribers can be listed multiple times, we have to
            // confirm that the parameter type is the contextual event.
            // Otherwise, we might bind the listener incorrectly.

            // Make sure the type matches the event
            if($type !== $event) {
                continue;
            }

            // Return the matched listener
            return [$listener->name, $method->name];

        }

        // If all else fails, assume the "handle" method
        return [$listener->name, 'handle'];
    }

    /**
     * Normalizes the specified observer / model mapping.
     *
     * @param  array  $observe
     *
     * @return array
     */
    public static function normalizeObserverMapping(array $observe)
    {
        // Unlike an event / listener mapping, the events are implicit.
        // We're going to explicitly call them out so that we don't
        // have to unpack the observer for each request. Fast!

        // Initialize the normalized list
        $normalized = [];

        // Iterate through each observer mapping
        foreach($observe as $observer => $models) {

            // Iterate through each model
            foreach($models as $model) {

                // Determine the methods to observe
                $methods = static::getObservableEvents($observer, $model);

                // Add each method to the normalized list
                foreach($methods as $method) {
                    $normalized["eloquent.{$method}: {$model}"][] = [$observer, $method];
                }

            }

        }

        // Return the normalized list
        return $normalized;
    }

    /**
     * Normalizes the specified model / observer mapping.
     *
     * @param  array  $models
     *
     * @return array
     */
    public static function normalizeModelMapping(array $models)
    {
        // This is basically the same as an observer / model mapping,
        // but we have got the mapping flipped. Once we're inside
        // of a nested loop, the actionable code is the same.

        // Initialize the normalized list
        $normalized = [];

        // Iterate through each model mapping
        foreach($models as $model => $observers) {

            // Iterate through each observer
            foreach($observers as $observer) {

                // Determine the methods to observe
                $methods = static::getObservableEvents($observer, $model);

                // Add each method to the normalized list
                foreach($methods as $method) {
                    $normalized["eloquent.{$method}: {$model}"][] = [$observer, $method];
                }

            }

        }

        // Return the normalized list
        return $normalized;
    }

    /**
     * Returns the observable events for the specified observer and model.
     *
     * @param  string  $observer
     * @param  string  $model
     *
     * @return array
     */
    public static function getObservableEvents(string $observer, string $model)
    {
        return array_values(array_filter((new $model)->getObservableEvents(), function($event) use ($observer) {
            return method_exists($observer, $event);
        }));
    }
}