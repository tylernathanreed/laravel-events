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

        Illuminate\Auth\Events\Registered::class => [
            Illuminate\Auth\Listeners\SendEmailVerificationNotification::class,
            App\Listeners\SendAnotherNotification::class,
        ],

        Illuminate\Auth\Events\Login::class => [
            App\Listeners\UserEventSubscriber::class,
        ],

        Illuminate\Auth\Events\Logout::class => [
            [App\Listeners\UserEventSubscriber::class, 'handleUserLogout']
        ],

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

        App\Subscribers\InverseUserEventSubscriber::class => [
            Illuminate\Auth\Events\Login::class,
            [Illuminate\Auth\Events\Logout::class, 'handleUserLogout']
        ]

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

        App\Observers\UserObserver::class => [
            App\Models\User::class
        ]

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

        App\Models\Post::class => [
            App\Observers\PostObserver::class
        ]

    ]

];