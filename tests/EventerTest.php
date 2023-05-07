<?php

declare(strict_types=1);

namespace Tests;

use Exan\Eventer\Eventer;
use Exan\Eventer\EventInterface;
use Exan\Eventer\Exceptions\InvalidEventException;
use PHPUnit\Framework\TestCase;
use stdClass;

class EventerTest extends TestCase
{
    public function testItEmitsAnEvent()
    {
        $eventer = new Eventer();

        $state = (object) ['success' => false];

        $event = new class ($state) implements EventInterface {
            public function __construct(private stdClass &$state)
            {
            }

            public static function getEventName(): string
            {
                return '::event name::';
            }

            public function filter(): bool
            {
                return true;
            }

            public function execute(): void
            {
                $this->state->success = true;
            }
        };

        $eventer->register($event::class);

        $eventer->emit('::event name::', [$state]);

        $this->assertTrue($state->success);
    }

    public function testItFiltersEvent()
    {
        $eventer = new Eventer();

        $state = (object) ['runs' => []];

        $event = new class ($state, true) implements EventInterface {
            public function __construct(private stdClass &$state, private bool $shouldRun)
            {
            }

            public static function getEventName(): string
            {
                return '::event name::';
            }

            public function filter(): bool
            {
                return $this->shouldRun;
            }

            public function execute(): void
            {
                $this->state->runs[] = $this->shouldRun;
            }
        };

        $eventer->register($event::class);

        $eventer->emit('::event name::', [$state, true]);
        $eventer->emit('::event name::', [$state, false]);

        $this->assertEquals([true], $state->runs);
    }

    public function testItDoesNotEmitOnEventsWithADifferentName()
    {
        $eventer = new Eventer();

        $state = (object) ['runs' => []];

        $shouldRunEvent = new class ($state) implements EventInterface {
            public function __construct(private stdClass &$state)
            {
            }

            public static function getEventName(): string
            {
                return '::event name, should run::';
            }

            public function filter(): bool
            {
                return true;
            }

            public function execute(): void
            {
                $this->state->runs[] = self::getEventName();
            }
        };

        $shouldNotRunEvent = new class ($state) implements EventInterface {
            public function __construct(private stdClass &$state)
            {
            }

            public static function getEventName(): string
            {
                return '::event name, should NOT run::';
            }

            public function filter(): bool
            {
                return true;
            }

            public function execute(): void
            {
                $this->state->runs[] = self::getEventName();
            }
        };

        $eventer->register($shouldRunEvent::class);
        $eventer->register($shouldNotRunEvent::class);

        $eventer->emit($shouldRunEvent::getEventName(), [$state]);

        $this->assertEquals([$shouldRunEvent::getEventName()], $state->runs);
    }

    public function testBeforeRunsEarlierThanRegularListeners()
    {
        $eventer = new Eventer();

        $state = (object) ['runs' => []];

        $beforeEvent = new class ($state) implements EventInterface {
            public function __construct(private stdClass &$state)
            {
            }

            public static function getEventName(): string
            {
                return '::before event name::';
            }

            public function filter(): bool
            {
                return true;
            }

            public function execute(): void
            {
                $this->state->runs[] = 'before';
            }
        };

        $listenerEvent = new class ($state) implements EventInterface {
            public function __construct(private stdClass &$state)
            {
            }

            public static function getEventName(): string
            {
                return '::before event name::';
            }

            public function filter(): bool
            {
                return true;
            }

            public function execute(): void
            {
                $this->state->runs[] = 'listeners';
            }
        };

        $eventer->register($listenerEvent::class);
        $eventer->before($beforeEvent::class);

        $eventer->emit($beforeEvent::getEventName(), [$state]);

        $this->assertEquals(['before', 'listeners'], $state->runs);
    }

    /**
     * @dataProvider registerCombinationsProvider
     */
    public function testBeforeOnceRunsEarlierThanRegularListeners(string $first, string $second)
    {
        $eventer = new Eventer();

        $state = (object) ['runs' => []];

        $beforeEvent = new class ($state) implements EventInterface {
            public function __construct(private stdClass &$state)
            {
            }

            public static function getEventName(): string
            {
                return '::before event name::';
            }

            public function filter(): bool
            {
                return true;
            }

            public function execute(): void
            {
                $this->state->runs[] = 'before';
            }
        };

        $listenerEvent = new class ($state) implements EventInterface {
            public function __construct(private stdClass &$state)
            {
            }

            public static function getEventName(): string
            {
                return '::before event name::';
            }

            public function filter(): bool
            {
                return true;
            }

            public function execute(): void
            {
                $this->state->runs[] = 'listeners';
            }
        };

        call_user_func_array([$eventer, $second], [$listenerEvent::class]);
        call_user_func_array([$eventer, $first], [$beforeEvent::class]);

        $eventer->emit($beforeEvent::getEventName(), [$state]);

        $this->assertEquals(['before', 'listeners'], $state->runs);
    }

    public static function registerCombinationsProvider(): array
    {
        return [
            'Before, listeners' => [
                'first' => 'before',
                'second' => 'register',
            ],
            'BeforeOnce, listeners' => [
                'first' => 'beforeOnce',
                'second' => 'register',
            ],
            'BeforeOnce, listenersOnce' => [
                'first' => 'beforeOnce',
                'second' => 'registerOnce',
            ],
            'Before, listenersOnce' => [
                'first' => 'before',
                'second' => 'registerOnce',
            ],
        ];
    }

    /**
     * @dataProvider onceRegistrarsProvider
     */
    public function testItActivatesOnceListenersOneTime(string $registrar)
    {
        $eventer = new Eventer();

        $state = (object) ['runs' => 0];

        $event = new class ($state) implements EventInterface {
            public function __construct(private stdClass &$state)
            {
            }

            public static function getEventName(): string
            {
                return '::event name::';
            }

            public function filter(): bool
            {
                return true;
            }

            public function execute(): void
            {
                $this->state->runs++;
            }
        };

        call_user_func_array([$eventer, $registrar], [$event::class]);

        $eventer->emit('::event name::', [$state]);
        $eventer->emit('::event name::', [$state]);

        $this->assertEquals(1, $state->runs);
    }

    public static function onceRegistrarsProvider(): array
    {
        return [
            'Register' => ['registrar' => 'registerOnce'],
            'Before' => ['registrar' => 'beforeOnce'],
        ];
    }

    /**
     * @dataProvider registrarProvider
     */
    public function testItThrowsAnExceptionWithoutEventInterface(string $registrar)
    {
        $eventer = new Eventer();

        $event = new class {
        };

        $this->expectException(InvalidEventException::class);
        call_user_func_array([$eventer, $registrar], [$event::class]);
    }

    public static function registrarProvider(): array
    {
        return [
            'Register' => ['registrar' => 'register'],
            'Register once' => ['registrar' => 'registerOnce'],
            'Before' => ['registrar' => 'before'],
            'Before once' => ['registrar' => 'beforeOnce'],
        ];
    }
}
