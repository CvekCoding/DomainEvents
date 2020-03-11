# Domain events bundle for Symfony
The bundle integrates with Symfony application to provide domain events support. This allows to be maximum doctrine-agnostic and raise any events during the business logic implementation.

Domain events are of two types:
- `preFlush`. Will be processed before persisting to DB.
- `onFlush`. Will be processed during persisting to DB in the same transaction.

To use this bundle simply implement `RaiseEventsInterface` interface. We recommend you to use `RaiseEventsTrait` to simplify this even more.

## Example
### Install
`composer req cvek/domain-events`

### Use
#### Create domain event
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
}
```
#### Catch event in listener
When `flush` operation will be invoked, all the events, raised in your entities, will be collected and dispatched. You can listen on them in a usual manner:
```php
use \Symfony\Component\EventDispatcher\EventSubscriberInterface;
use \Doctrine\ORM\Events;

final class ConfirmCourier  implements EventSubscriberInterface
{
    /**
     * @inheritDoc
     */
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
    }
}
```