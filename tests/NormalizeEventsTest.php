<?php

namespace Reedware\LaravelEvents\Tests;

use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;
use Reedware\LaravelEvents\NormalizeEvents;

class NormalizeEventsTest extends TestCase
{
    public function testNormalizeListenerWithArray()
    {
        $listener = ['MyClass', 'myMethod'];

        $normalize = NormalizeEvents::normalizeListener($listener, 'MyEvent');

        $this->assertEquals($listener, $normalize);
    }

    public function testNormalizeListenerAtSyntax()
    {
        $listener = 'MyClass@myMethod';

        $normalize = NormalizeEvents::normalizeListener($listener, 'MyEvent');

        $this->assertEquals(['MyClass', 'myMethod'], $normalize);
    }

    public function testNormalizeListenerWithTypeHint()
    {
        $listener = ImplicitListener::class;

        $normalize = NormalizeEvents::normalizeListener($listener, CustomEvent::class);

        $this->assertEquals([$listener, 'handleCustomEvent'], $normalize);
    }

    public function testNormalizeListenerWithoutAnything()
    {
        $listener = GenericListener::class;

        $normalize = NormalizeEvents::normalizeListener($listener, CustomEvent::class);

        $this->assertEquals([$listener, 'handle'], $normalize);
    }

    public function testNormalizeListenerWithSubscriber()
    {
        $listener = Subscriber::class;

        $normalize1 = NormalizeEvents::normalizeListener($listener, CustomEvent::class);
        $normalize2 = NormalizeEvents::normalizeListener($listener, SecondEvent::class);

        $this->assertEquals([$listener, 'handleFirstEvent'], $normalize1);
        $this->assertEquals([$listener, 'handleSecondEvent'], $normalize2);
    }

    public function testNormalizeListenerWithBadTypeHint()
    {
        $listener = ImplicitListener::class;

        $normalize = NormalizeEvents::normalizeListener($listener, SecondEvent::class);

        $this->assertEquals([$listener, 'handle'], $normalize);
    }

    public function testObserveableEventsWithBasicScenario()
    {
        $events = NormalizeEvents::getObservableEvents(BasicObserver::class, BasicModel::class);

        $this->assertEquals(['creating', 'updating'], $events);
    }

    public function testObserveableEventsWithObservable()
    {
        $events = NormalizeEvents::getObservableEvents(BasicObserver::class, ObservableModel::class);

        $this->assertEquals(['creating', 'updating', 'foo'], $events);
    }

    public function testNormalizeListenerMapping()
    {
        $mapping = [
            CustomEvent::class => [
                GenericListener::class,
                ImplicitListener::class,
                [ExplicitListener::class, 'handleExplicitEvent'],
                Subscriber::class
            ],
            SecondEvent::class => [
                Subscriber::class
            ]
        ];

        $normalized = NormalizeEvents::normalizeListenerMapping($mapping);

        $this->assertEquals([
            CustomEvent::class => [
                [GenericListener::class, 'handle'],
                [ImplicitListener::class, 'handleCustomEvent'],
                [ExplicitListener::class, 'handleExplicitEvent'],
                [Subscriber::class, 'handleFirstEvent']
            ],
            SecondEvent::class => [
                [Subscriber::class, 'handleSecondEvent']
            ]
        ], $normalized);
    }

    public function testNormalizeSubscriberMapping()
    {
        $mapping = [
            Subscriber::class => [
                CustomEvent::class,
                SecondEvent::class,
                [ThirdEvent::class, 'handleThirdEvent']
            ]
        ];

        $normalized = NormalizeEvents::normalizeSubscriberMapping($mapping);

        $this->assertEquals([
            CustomEvent::class => [
                [Subscriber::class, 'handleFirstEvent']
            ],
            SecondEvent::class => [
                [Subscriber::class, 'handleSecondEvent']
            ],
            ThirdEvent::class => [
                [Subscriber::class, 'handleThirdEvent']
            ]
        ], $normalized);
    }

    public function testNormalizeObserverMapping()
    {
        $mapping = [
            BasicObserver::class => [
                BasicModel::class,
                ObservableModel::class
            ]
        ];

        $normalized = NormalizeEvents::normalizeObserverMapping($mapping);

        $this->assertEquals([
            'eloquent.creating: ' . BasicModel::class => [[BasicObserver::class, 'creating']],
            'eloquent.updating: ' . BasicModel::class => [[BasicObserver::class, 'updating']],
            'eloquent.creating: ' . ObservableModel::class => [[BasicObserver::class, 'creating']],
            'eloquent.updating: ' . ObservableModel::class => [[BasicObserver::class, 'updating']],
            'eloquent.foo: ' . ObservableModel::class => [[BasicObserver::class, 'foo']],
        ], $normalized);
    }

    public function testNormalizeModelMapping()
    {
        $mapping = [
            BasicModel::class => [
                BasicObserver::class
            ],
            ObservableModel::class => [
                BasicObserver::class
            ]
        ];

        $normalized = NormalizeEvents::normalizeModelMapping($mapping);

        $this->assertEquals([
            'eloquent.creating: ' . BasicModel::class => [[BasicObserver::class, 'creating']],
            'eloquent.updating: ' . BasicModel::class => [[BasicObserver::class, 'updating']],
            'eloquent.creating: ' . ObservableModel::class => [[BasicObserver::class, 'creating']],
            'eloquent.updating: ' . ObservableModel::class => [[BasicObserver::class, 'updating']],
            'eloquent.foo: ' . ObservableModel::class => [[BasicObserver::class, 'foo']],
        ], $normalized);
    }

    public function testNormalize()
    {
        $listen = [
            CustomEvent::class => [
                GenericListener::class,
                ImplicitListener::class,
                [ExplicitListener::class, 'handleExplicitEvent'],
            ],
            SecondEvent::class => [
                Subscriber::class
            ]
        ];

        $subscribe = [
            Subscriber::class => [
                CustomEvent::class,
                [ThirdEvent::class, 'handleThirdEvent']
            ]
        ];

        $observe = [
            BasicObserver::class => [
                ObservableModel::class
            ]
        ];

        $models = [
            BasicModel::class => [
                BasicObserver::class
            ]
        ];

        $normalized = NormalizeEvents::normalize($listen, $subscribe, $observe, $models);

        $expected = [
            CustomEvent::class => [
                [GenericListener::class, 'handle'],
                [ImplicitListener::class, 'handleCustomEvent'],
                [ExplicitListener::class, 'handleExplicitEvent'],
                [Subscriber::class, 'handleFirstEvent']
            ],
            SecondEvent::class => [
                [Subscriber::class, 'handleSecondEvent']
            ],
            ThirdEvent::class => [
                [Subscriber::class, 'handleThirdEvent']
            ],
            'eloquent.creating: ' . BasicModel::class => [[BasicObserver::class, 'creating']],
            'eloquent.creating: ' . ObservableModel::class => [[BasicObserver::class, 'creating']],
            'eloquent.foo: ' . ObservableModel::class => [[BasicObserver::class, 'foo']],
            'eloquent.updating: ' . BasicModel::class => [[BasicObserver::class, 'updating']],
            'eloquent.updating: ' . ObservableModel::class => [[BasicObserver::class, 'updating']]
        ];

        $this->assertEquals($expected, $normalized);
        $this->assertEquals(array_keys($expected), array_keys($normalized));
    }
}

class ImplicitListener
{
    public function handleCustomEvent(CustomEvent $event) {}
}

class GenericListener
{
    public function handle($event) {}
}

class ExplicitListener
{
    public function handleExplicitEvent($event) {}
}

class Subscriber
{
    public function handleFirstEvent(CustomEvent $event) {}
    public function handleSecondEvent(SecondEvent $event) {}
    public function handleThirdEvent($event) {}
}

class CustomEvent {}
class SecondEvent {}
class ThirdEvent {}

class BasicObserver
{
    public function creating() {}
    public function updating() {}
    public function foo() {}
}

class BasicModel extends Model {}

class ObservableModel extends Model
{
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->addObservableEvents(['foo']);
    }
}