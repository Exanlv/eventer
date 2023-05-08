<?php

declare(strict_types=1);

namespace Exan\Eventer;

interface EventInterface
{
    public static function getEventName(): string;
    public function filter(): bool;
    public function execute();
}
