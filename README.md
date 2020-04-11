# Domain events bundle for Symfony
The bundle integrates with Symfony application to provide domain events support. This allows to be doctrine-agnostic and raise events during the business logic implementation.

Domain events are of three types:
- `preFlush`. Will be processed synchronously before persisting to DB;
- `onFlush`. Will be processed during persisting to DB (sync or async - see below);
- `postFlush`. Will be processed after persisting to DB (sync or async - see below).

To use this bundle implement `RaiseEventsInterface` interface in your entity class and create your custom domain events. We recommend you to use `RaiseEventsTrait` to simplify this even more.

## Sync/Async messages
Any domain event can be executed in a `sync` or an `async` way during `onFlush` and `postFlush` Doctrine events. `DomainEventInterface` will make you make a choice:).

Async way is a very powerful approach and must be used in the following cases:
* you want to postpone some time-consuming or remote tasks;
* you want to avoid restrictions of Doctrine event system (e.g. see [this](https://www.doctrine-project.org/projects/doctrine-orm/en/2.6/reference/events.html#onflush) and [this](https://www.doctrine-project.org/projects/doctrine-orm/en/2.6/reference/events.html#postflush)).

Thanks to `db` transport with doctrine storage (will be created automatically) - all the async domain events will be routed and persisted to db automatically.

To consume them we recommend to use the following supervisor config:
```
[program:db]
command=/srv/api/bin/console messenger:consume db --memory-limit=128M --time-limit=3600 --limit=100
process_name=%(program_name)s_%(process_num)02d
numprocs=1
autostart=true
autorestart=true
startsecs=0
redirect_stderr=true
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
```
> :warning: **Dont forget to add it to your application!**

## Example
### Install
`composer req cvek/domain-events`

### Use
#### Create domain event
##### Sync event
```php
use \Cvek\DomainEventsBundle\EventDispatch\Event\AbstractDomainEvent;

final class FooNameChanged extends AbstractDomainEvent
{
    private Foo $foo;
    private string $oldName;
    private string $newName;

    public function __construct(Foo $foo, string $oldName, string $newName)
    {
        $this->foo = $foo;
        $this->oldName = $oldName;
        $this->newName = $newName;
    }
   
    public function getFoo(): Foo
    {
        return $this->foo;
    }
    
    public function getOldName(): string
    {
        return $this->oldName;
    }
    
    public function getNewName(): string
    {
        return $this->newName;
    }

    public function isAsync() : bool
    {
        return false;
    }
}
```
##### Async event
```php
use \Cvek\DomainEventsBundle\EventDispatch\Event\AbstractDomainEvent;

final class FooPasswordChanged extends AbstractDomainEvent
{
    private Foo $foo;
    private string $password;

    public function __construct(Foo $foo, string $password)
    {
        $this->foo = $foo;
        $this->password = $password;
    }
   
    public function getFoo(): Foo
    {
        return $this->foo;
    }
    
    public function getPassword(): string
    {
        return $this->password;
    }

    public function isAsync() : bool
    {
        return true;
    }
}
```

#### Raise event in your business layer
```php
use \Cvek\DomainEventsBundle\Entity\RaiseEventsInterface;
use \Cvek\DomainEventsBundle\Entity\RaiseEventsTrait;

class Foo implements RaiseEventsInterface
{
    use RaiseEventsTrait;

    private string $name;

    public function setName(string $name): self
    {
        $this->raise(new FooNameChanged($this, $this->name, $name));
        $this->name = $name;

        return $this;
    }

    public function setPassword(string $password): self
    {
        $this->raise(new FooPasswordChanged($this, $password));

        return $this;
    }
}
```
#### Catch event in listener
When `flush` operation will be invoked, all the events, raised in your entities, will be collected and dispatched. You can listen on them in a usual manner.
##### Listen on sync message
```php
use \Symfony\Component\EventDispatcher\EventSubscriberInterface;
use \Doctrine\ORM\Events;

final class FooNameListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            FooNameChanged::class => 'onNameChange'
        ];
    }

    public function onNameChange(FooNameChanged $event): void
    {
        if ($event->getLifecycleEvent() === Events::preFlush) {
            // your custom logic on preFlush moment: logging, validation etc...        
        }

        if ($event->getLifecycleEvent() === Events::onFlush) {
            // your custom logic on onFlush moment        
        }

        if ($event->getLifecycleEvent() === Events::postFlush) {
            // your custom logic on onFlush moment        
        }
    }
}
```
##### Listen on async message
```php
use \Doctrine\ORM\Events;
use \Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class FooPasswordHandler implements MessageHandlerInterface
{
    public function __invoke(FooPasswordChanged $event)
    {
        if ($event->getLifecycleEvent() === Events::preFlush) {
            // your custom logic on preFlush moment: logging, validation etc...        
        }

        if ($event->getLifecycleEvent() === Events::onFlush) {
            // your custom logic on postFlush moment        
        }

        if ($event->getLifecycleEvent() === Events::postFlush) {
            // your custom logic on postFlush moment        
        }
    }
}
```