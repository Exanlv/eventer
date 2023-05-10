<?php

declare(strict_types=1);

namespace Exan\Eventer;

use Exan\Eventer\Exceptions\InvalidEventException;

class Eventer
{
    /** @var array<string, EventInterface[]> */
    protected array $listeners = [];
    /** @var array<string, EventInterface[]> */
    protected array $listenersOnce = [];

    /** @var array<string, EventInterface[]> */
    protected array $before = [];
    /** @var array<string, EventInterface[]> */
    protected array $beforeOnce = [];

    /**
     * @var class-string $event
     * @throws InvalidEventException
     */
    public function register(string ...$events)
    {
        $this->registerFor('listeners', $events);
    }

    /**
     * @var class-string $event
     * @throws InvalidEventException
     */
    public function registerOnce(string ...$events)
    {
        $this->registerFor('listenersOnce', $events);
    }

    /**
     * @var class-string $event
     * @throws InvalidEventException
     */
    public function before(string ...$events)
    {
        $this->registerFor('before', $events);
    }

    /**
     * @var class-string $event
     * @throws InvalidEventException
     */
    public function beforeOnce(string ...$events)
    {
        $this->registerFor('beforeOnce', $events);
    }

    public function emit(string $eventName, array $args)
    {
        $this->handleOnce('beforeOnce', $eventName, $args);
        $this->handle('before', $eventName, $args);

        $this->handleOnce('listenersOnce', $eventName, $args);
        $this->handle('listeners', $eventName, $args);
    }

    protected function handleOnce(string $type, string $eventName, array $args)
    {
        foreach ($this->{$type}[$eventName] ?? [] as $key => $event) {
            $hasRun = $this->handleEvent($event, $args);

            if ($hasRun) {
                unset($this->{$type}[$eventName][$key]);
            }
        }
    }

    protected function handle(string $type, string $eventName, array $args)
    {
        foreach ($this->{$type}[$eventName] ?? [] as $event) {
            $this->handleEvent($event, $args);
        }
    }

    /**
     * @var class-string $event
     */
    protected function handleEvent(string $event, array $args): bool
    {
        /** @var EventInterface */
        $eventInstance = new $event(...$args);

        if ($eventInstance->filter()) {
            $eventInstance->execute();
            return true;
        }

        return false;
    }

    /**
     * @var class-string $event
     * @throws InvalidEventException
     */
    protected function validateEvent(string $event): void
    {
        if (!in_array(EventInterface::class, class_implements($event))) {
            throw new InvalidEventException(
                sprintf('%s does not implement %s', $event, EventInterface::class)
            );
        }
    }

    /**
     * @var class-string $event
     */
    protected function getEventName(string $event): string
    {
        return $event::getEventName();
    }

    /**
     * @var class-string $event
     * @throws InvalidEventException
     */
    protected function registerFor(string $type, array $events): void
    {
        foreach ($events as $event) {
            $this->validateEvent($event);

            $eventName = self::getEventName($event);

            if (!isset($this->{$type}[$eventName])) {
                $this->{$type}[$eventName] = [];
            }

            $this->{$type}[$eventName][] = $event;
        }
    }
}
