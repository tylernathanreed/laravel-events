<?php

namespace Reedware\LaravelEvents\Tests;

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Reedware\LaravelEvents\EventServiceProvider;
use Reedware\LaravelEvents\NormalizeEvents;
use ReflectionFunction;

class ConfiguredEventsTest extends TestCase
{
    protected $app;
    protected $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app = new Application(__DIR__);
        $this->app->instance('config', $config = new Repository);

        $config->set('events', require realpath(__DIR__ . DIRECTORY_SEPARATOR . 'events.php'));

        $this->app->singleton('files', function() {
            return tap(m::mock(Filesystem::class), function($mock) {
                $mock->shouldReceive('exists')
                    ->withAnyArgs()
                    ->andReturn(false);
            });
        });

        $this->provider = $this->app->register(EventServiceProvider::class);
        $this->app->boot();
    }

    public function testConfiguredEvents()
    {
        $config = $this->app->make('config')->get('events');
        $provided = $this->provider->configuredEvents();

        foreach($config['listen'] as $event => $listeners) {
            foreach($listeners as $listener) {
                $normalized = NormalizeEvents::normalizeListener($listener, $event);
                $this->assertTrue(in_array($normalized, $provided[$event]));
            }
        }

        foreach($config['subscribe'] as $subscriber => $events) {
            foreach($events as $event) {
                [$event, $method] = is_array($event) ? $event : [$event, 'handle'];
                $normalized = NormalizeEvents::normalizeListener([$subscriber, $method], $event);
                $this->assertTrue(in_array($normalized, $provided[$event]));
            }
        }

        foreach($config['observe'] as $observer => $models) {
            foreach($models as $model) {
                $observes = NormalizeEvents::getObservableEvents($observer, $model);
                foreach($observes as $method) {
                    $this->assertTrue(in_array([$observer, $method], $provided["eloquent.{$method}: {$model}"]));
                }
            }
        }

        foreach($config['models'] as $model => $observers) {
            foreach($observers as $observer) {
                $observes = NormalizeEvents::getObservableEvents($observer, $model);
                foreach($observes as $method) {
                    $this->assertTrue(in_array([$observer, $method], $provided["eloquent.{$method}: {$model}"]));
                }
            }
        }
    }

    public function testEventRegistration()
    {
        $events = $this->provider->configuredEvents();
        $dispatcher = $this->app->make('events');

        foreach($events as $event => $configured) {

            $registered = array_map(function($listener) {
                return $this->unmakeListener($listener);
            }, $dispatcher->getListeners($event));

            foreach($configured as $listener) {
                $this->assertTrue(
                    in_array($listener, $registered),
                    sprintf('Listener [%s] cannot be found within [%s]',
                        json_encode($listener),
                        json_encode($registered)
                    )
                );
            }

        }
    }

    protected function unmakeListener($listener)
    {
        $r = new ReflectionFunction($listener);

        $use = $r->getStaticVariables();

        return $use['listener'];
    }
}

namespace App\Listeners;
class SendAnotherNotification {}
class UserEventSubscriber {}

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Post extends Model {}
class User extends Model {}

namespace App\Observers;
class UserObserver { function created(){} }
class PostObserver { function created(){} }

namespace App\Subscribers;
class InverseUserEventSubscriber {}