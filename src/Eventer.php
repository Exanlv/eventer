<?php

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
    public function register(string $event)
    {
        $this->registerFor('listeners', $event);
    }

    /**
     * @var class-string $event
     * @throws InvalidEventException
     */
    public function registerOnce(string $event)
    {
        $this->registerFor('listenersOnce', $event);
    }

    /**
     * @var class-string $event
     * @throws InvalidEventException
     */
    public function before(string $event)
    {
        $this->registerFor('before', $event);
    }

    /**
     * @var class-string $event
     * @throws InvalidEventException
     */
    public function beforeOnce(string $event)
    {
        $this->registerFor('beforeOnce', $event);
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
        foreach ($this->{$type}[$eventName] ?? [] as $event) {
            $this->handleEvent($event, $args);

            $this->{$type}[$eventName] = [];
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
    protected function handleEvent(string $event, array $args)
    {
        /** @var EventInterface */
        $eventInstance = new $event(...$args);

        if ($eventInstance->filter()) {
            $eventInstance->execute();
        }
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
    protected function registerFor(string $type, string $event): void
    {
        $this->validateEvent($event);

        $eventName = self::getEventName($event);

        if (!isset($this->{$type}[$eventName])) {
            $this->{$type}[$eventName] = [];
        }

        $this->{$type}[$eventName][] = $event;
    }
}
