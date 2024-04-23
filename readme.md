# Eventer

[![Eventer Code Quality Assurance](https://github.com/Exanlv/eventer/actions/workflows/code-quality.yml/badge.svg)](https://github.com/Exanlv/eventer/actions/workflows/code-quality.yml) [![Eventer Unit Tests](https://github.com/Exanlv/eventer/actions/workflows/unit-tests.yml/badge.svg)](https://github.com/Exanlv/eventer/actions/workflows/unit-tests.yml)

Object oriented event emitter for PHP

For the majority of projects, you should probably use `evenement/evenement`. This may in some instances produce hard to follow code, in which case this approach may be more desirable.

### Install

```bash
composer require exan/eventer
```

### Example usage

```php
class SomeEvent implements EventInterface {
    public function __construct($myFirstArg, $mySecondArg)
    {
    }

    public static function getEventName(): string
    {
        return 'Some Event';
    }

    public function filter(): bool
    {
        return true; // return false to skip execution
    }

    public function execute(): void
    {
        // Your event handling code
    }
}
```

```php
$eventer = new Eventer();

$eventer->register(SomeClass::class); // Listen to events regularly
$eventer->registerOnce(SomeClass::class); // Listen to a single event
$eventer->before(SomeClass::class); // Listen to events regularly, executed before events registered with `register`
$eventer->beforeOnce(SomeClass::class); // Listen to a single event, executed before events registered with `register`

$eventer->emit('Some Event', ['my first arg', 'my second arg']);
```
