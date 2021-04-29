# Laravel Events

[![Latest Stable Version](https://poser.pugx.org/reedware/laravel-events/v/stable)](https://packagist.org/packages/reedware/laravel-events)
[![Total Downloads](https://poser.pugx.org/reedware/laravel-events/downloads)](https://packagist.org/packages/reedware/laravel-events)
[![Laravel Version](https://img.shields.io/badge/Laravel-6.x--8.x-blue)](https://laravel.com/)
[![Build Status](https://travis-ci.com/tylernathanreed/laravel-events.svg?branch=master)](https://travis-ci.com/tylernathanreed/laravel-events)

This package allows you to define all of your events in a simple configuration file.

## Introduction

Events in Laravel are a fantastic tool to use. However, once you start leaning into events heavily, managing everything through the service provider can be a bit cumbersome. While [event discovery](https://laravel.com/docs/5.8/events#event-discovery) exists (since Laravel 5.8), this doesn't always solve the entire problem. This package will allow you to define listeners, subscribers, and observers within a configuration file. Additionally, the implementation allows you to be a bit more lackadaisical with registration, with no performance cost after caching events.

## Installation

### Composer

First, start by installing this package.

```
composer require reedware/laravel-events
```

### Configuration

Next, you'll need to get your hands on the `events.php` configuration file. You can either copy it directly, or publish it.

```
php artisan vendor:publish
```

### Service Provider

Finally, you'll need to modify your service provider. You can either extend the example provider in the package, or you can utilize the trait.

#### Extending the Service Provider
```php
<?php

namespace App\Providers;

use Reedware\LaravelEvents\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{

}
```

#### Using the Trait instead
```php
<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Reedware\LaravelEvents\ConfiguredEvents;

class EventServiceProvider extends ServiceProvider
{
    use ConfiguredEvents;
}
```

If you've overridden the default `listens()` method, be sure to yield the configured events:

```php
/**
 * Returns the events and handlers.
 *
 * @return array
 */
public function listens()
{
    $myEventOverrides = [/* ... */];

    return array_merge_recursive(
        $myEventOverrides,
        $this->configuredEvents()
    );
}
```

After that, you're good to start configuring your events!

## Usage

This package offers four types of event registration:
* Event / Listener Mapping
* Subscriber / Event Mapping
* Observer / Model Mapping
* Model / Observer Mapping

As you dive in, you'll discover that there's more than one way to register an event binding. This package offers various alternatives so that you can do whatever makes the most since for you and your application.

### Event / Listener Mapping

This is the traditional "listens" array that is typically a property within your `EventServerProvider`. You can list each event, and the listeners that listen to them.

**Example:**
```php
'listen' => [

    // When a user has registered...
    Illuminate\Auth\Events\Registered::class => [

        // Send an email verification notification
        Illuminate\Auth\Listeners\SendEmailVerificationNotification::class

    ],

],
```

#### Explicit Bindings

If your listener doesn't use the traditional `handle` method, you can call out the method directly like so:

```php
'listen' => [

    // When a user has registered...
    Illuminate\Auth\Events\Registered::class => [

        [App\Listeners\MyCustomListener::class, 'handleRegistration'],

        // or

        'App\Listeners\MyCustomListener@handleRegistration'

    ],

],
```

#### Implicit Bindings

If your alternative method starts with the word `handle` (e.x. `handleRegistration`), and accepts the type-hinted event, you don't have do specify the custom method explicitly:

*Listener:*
```php
<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Registered;

class MyCustomListener
{
    /**
     * Handles the specified registration event.
     *
     * @param  \Illuminate\Auth\Events\Registered  $event
     *
     * @return void
     */
    public function handleRegistration(Registered $event)
    {
        //
    }
}
```

*Configuration:*
```php
'listen' => [

    // When a user has registered...
    Illuminate\Auth\Events\Registered::class => [

        // Handle the event
        App\Listeners\MyCustomListener::class,

    ],

],
```

#### Alternative Subscriber Bindings

Since you can utilize a non `handle` method through implicit or explicit binding, you could technically register your subscribers within the Event / Listener mapping. This can make sense if you want to list your subscribers under each respective event, rather than listing all events that are subscribed to.

*Example:*
```php
'listen' => [

    // When a user has registered...
    Illuminate\Auth\Events\Registered::class => [

        // Send a slack notification
        App\Listeners\SendSlackNotification::class,

    ],

    // When a user has logged out...
    Illuminate\Auth\Events\Logout::class => [

        // Send a slack notification
        App\Listeners\SendSlackNotification::class,

    ],

],
```

### Subscriber / Event Mapping

When working with subscribers, it often makes sense to list the subscribed events together. When listing subscribers in the configuration file, they do not need a `subscribe` method.

*Configuration:*
```php
'subscribe' => [

    // Log a security event...
    App\Listeners\LogSecurityEvent::class => [

        // When a user provided incorrect credentials
        Illuminate\Auth\Events\Failed::class,

        // When a user has been locked out
        Illuminate\Auth\Events\Lockout::class,

        // When a user has reset their password
        Illuminate\Auth\Events\PasswordReset::class,

    ],

],
```

*Subscriber:*
```php
<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\PasswordReset;

class LogSecurityEvent
{
    /**
     * Handles failed login attempts.
     */
    public function handleFailed(Failed $event)
    {
        //
    }

    /**
     * Handles too many login attempts.
     */
    public function handleLockout(Lockout $event)
    {
        //
    }

    /**
     * Handles successful password resets.
     */
    public function handlePasswordReset(PasswordReset $event)
    {
        //
    }
}
```

Remember that implicit bindings requires the method to start with the word `handle`, even when type-hinted.

*Pro-Tip:* If you plan to always use implicit bindings for subscribers, consider using [Event Discovery](https://laravel.com/docs/master/events#event-discovery)!

#### Explicit Bindings

Similar to the event / listener mapping, you may also use expicit binding:

```php
'subscribe' => [

    // Log a security event...
    App\Listeners\LogSecurityEvent::class => [

        // When a user provided incorrect credentials
        [Illuminate\Auth\Events\Failed::class, 'failed'],

        // When a user has been locked out
        [Illuminate\Auth\Events\Lockout::class, 'lockout'],

        // When a user has reset their password
        'Illuminate\Auth\Events\PasswordReset@reset',

    ],

],
```

### Observer / Model Mapping

When fully utilized, observers often observe more than one model. Listing the models under the observers make more sense in this case. Here's how you can configure observers:

```php
'observe' => [

    // Track the creator for each of the following models...
    App\Observers\UserObserver::class => [

        App\Models\Country::class,
        App\Models\Post::class,
        App\Models\State::class,
        App\Models\User::class,

    ]

],
```

This package fully supports custom observables, provided that they are registered either as a property or within the construction of the model.

### Model / Observer Mapping

You may prefer to instead list out models, and describe the observers beneath them. Both options are provided so that you can do what makes the most sense for you and your application. Here's how listing the model first would work:

```php
'models' => [

    // Observe the user
    App\Models\User::class => [
        App\Observers\UserObserver::class,
    ]

],
```

## Event Caching

This package fully supports [Event Caching](https://laravel.com/docs/master/events#event-discovery-in-production). In fact, given how observers are registered under the hood, you will likely see a significant performance boost if you're using a lot of observers within your application.

To cache events, run the `event:cache` artisan command. Should you need to clear the event cache, you can run the `event:clear` artisan command.

## Event Discovery

This package fully supports [Event Discovery](https://laravel.com/docs/master/events#event-discovery), and it does not conflict with the configuration file. Both configured events and discovered events are merged in the registration process.

Additionally, any section of the event configuration can be omitted entirely. If you decide to use event discovery for all of your events, and wish to only configure your observers, you can remove the "listen" and "subscribe" arrays without issue.