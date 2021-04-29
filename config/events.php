<?php

return [

    /*
     |--------------------------------------------------------------------------
     | Event / Listener Mapping
     |--------------------------------------------------------------------------
     |
     | An event must be explictly listened to by a listener in order for that
     | listener to be able to handle its event. You can register events &
     | listeners by mapping events their respective listeners below.
     |
     */

    'listen' => [

        // Illuminate\Auth\Events\Registered::class => [
        //     Illuminate\Auth\Listeners\SendEmailVerificationNotification::class
        // ],

        // Illuminate\Auth\Events\Login::class => [
        //     App\Listeners\UserEventSubscriber::class // Implicit
        //     [App\Listeners\UserEventSubscriber::class, 'handleUserLogin'] // Explicit
        // ],

        // Illuminate\Auth\Events\Logout::class => [
        //     App\Listeners\UserEventSubscriber::class // Implicit
        //     [App\Listeners\UserEventSubscriber::class, 'handleUserLogout'] // Explicit
        // ],

    ],

    /*
     |--------------------------------------------------------------------------
     | Subscriber / Event Mapping
     |--------------------------------------------------------------------------
     |
     | If you prefer to list out subscribers with their explicit events, rather
     | than listing the subscriber under each event like above, you instead
     | can list them here as an alternative. Do what makes sense to you.
     |
     */

    'subscribe' => [

        // App\Subscribers\UserEventSubscriber::class => [
        //     Illuminate\Auth\Events\Login::class, // Implicit
        //     Illuminate\Auth\Events\Logout::class // Implicit
        // ]

        // App\Subscribers\UserEventSubscriber::class => [
        //     [Illuminate\Auth\Events\Login::class::class, 'handleUserLogin'] // Explicit
        //     [Illuminate\Auth\Events\Logout::class, 'handleUserLogout'] // Explicit
        // ]

    ],

    /*
     |--------------------------------------------------------------------------
     | Observer / Model Mapping
     |--------------------------------------------------------------------------
     |
     | In certain cases, it may be fitting to listen to one or many events used
     | by eloquent models. This can also be done using the observer design
     | pattern. Observers can have many models, simply list them below.
     |
     */

    'observe' => [

        // App\Observers\UserObserver::class => [
        //     App\Models\User::class
        // ]

    ],

    /*
     |--------------------------------------------------------------------------
     | Model / Observer Mapping
     |--------------------------------------------------------------------------
     |
     | If you prefer to list out models with their explicit observers, rather
     | than listing each model under each observer like above, you instead
     | can list them here as an alternative. Do what makes sense to you.
     |
     */

    'models' => [

        // App\Models\User::class => [
        //     App\Observers\UserObserver::class
        // ]

    ]

];