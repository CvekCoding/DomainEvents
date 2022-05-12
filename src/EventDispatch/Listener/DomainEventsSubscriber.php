<?php
/**
 * This file is part of the Diningedge package.
 *
 * (c) Sergey Logachev <svlogachev@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cvek\DomainEventsBundle\EventDispatch\Listener;

use Cvek\DomainEventsBundle\Entity\RaiseEventsInterface;
use Cvek\DomainEventsBundle\EventDispatch\Event\AbstractAsyncDomainEvent;
use Cvek\DomainEventsBundle\EventDispatch\Event\AbstractSyncDomainEvent;
use Cvek\DomainEventsBundle\EventDispatch\Event\CustomDomainEventInterface;
use Cvek\DomainEventsBundle\EventDispatch\Event\DomainEventInterface;
use Doctrine\Common\EventArgs;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class DomainEventsSubscriber implements EventSubscriber
{
    private bool $preFlushAlreadyInvoked = false;
    private bool $onFlushAlreadyInvoked = false;
    private bool $postFlushAlreadyInvoked = false;

    private EventDispatcherInterface $eventDispatcher;
    private MessageBusInterface $bus;

    public function __construct(EventDispatcherInterface $eventDispatcher, MessageBusInterface $bus)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->bus = $bus;
    }

    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return array
     */
    public function getSubscribedEvents(): array
    {
        return [
            Events::preFlush,
            Events::onFlush,
            Events::postFlush,
        ];
    }

    public function preFlush(PreFlushEventArgs $eventArgs): void
    {
        if ($this->preFlushAlreadyInvoked) {
            return;
        }

        $this->preFlushAlreadyInvoked = true;
        $events = [];

        foreach ($this->fillEntities($eventArgs) as $entity) {
            if ($entity instanceof RaiseEventsInterface) {
                $events = array_merge($events, $entity->popEvents());
            }
        }

        foreach ($events as $event) {
            if ($event instanceof DomainEventInterface) {
                if (!$event instanceof AbstractSyncDomainEvent) {
                    return;
                }

                $eventName = $event instanceof CustomDomainEventInterface
                    ? $event->getEventName()
                    : \get_class($event);

                $this->eventDispatcher->dispatch($event->setLifecycleEvent(Events::preFlush), $eventName);
            }
        }

        $this->preFlushAlreadyInvoked = false;
    }

    public function onFlush(OnFlushEventArgs $eventArgs): void
    {
        if ($this->onFlushAlreadyInvoked) {
            return;
        }

        $this->onFlushAlreadyInvoked = true;
        $events = [];

        foreach ($this->fillEntities($eventArgs) as $entity) {
            if ($entity instanceof RaiseEventsInterface) {
                $events = array_merge($events, $entity->popEvents());
            }
        }

        foreach ($events as $event) {
            if ($event instanceof DomainEventInterface) {
                $eventName = $event instanceof CustomDomainEventInterface
                    ? $event->getEventName()
                    : \get_class($event);

                if ($event instanceof AbstractAsyncDomainEvent) {
                    $this->bus->dispatch($event->setLifecycleEvent(Events::onFlush));
                } else {
                    if ($event instanceof AbstractSyncDomainEvent) {
                        $this->eventDispatcher->dispatch($event->setLifecycleEvent(Events::onFlush), $eventName);
                    } else {
                        if (!$event->isAlreadyDispatched()) {
                            $this->bus->dispatch($event->setDispatched());
                        }
                    }
                }
            }
        }

        $this->onFlushAlreadyInvoked = false;
    }

    public function postFlush(PostFlushEventArgs $eventArgs): void
    {
        if ($this->postFlushAlreadyInvoked) {
            return;
        }

        $this->postFlushAlreadyInvoked = true;
        $events = [];

        foreach ($this->fillEntities($eventArgs) as $entity) {
            if ($entity instanceof RaiseEventsInterface) {
                $events = array_merge($events, $entity->popEvents());
            }
        }

        foreach ($events as $event) {
            if ($event instanceof DomainEventInterface) {
                $eventName = $event instanceof CustomDomainEventInterface
                    ? $event->getEventName()
                    : \get_class($event);

                if ($event instanceof AbstractSyncDomainEvent) {
                    $this->eventDispatcher->dispatch($event->setLifecycleEvent(Events::postFlush), $eventName);
                }
            }
        }

        $this->postFlushAlreadyInvoked = false;
    }

    /**
     * @param PreFlushEventArgs|OnFlushEventArgs|PostFlushEventArgs $eventArgs
     *
     * @return RaiseEventsInterface[]
     */
    private function fillEntities(EventArgs $eventArgs): array
    {
        $domainEventsEntities = [];
        foreach (
            $eventArgs->getEntityManager()
                ->getUnitOfWork()
                ->getIdentityMap() as $class => $entities
        ) {
            if (!\in_array(RaiseEventsInterface::class, \class_implements($class), true)) {
                continue;
            }

            $domainEventsEntities = array_merge($domainEventsEntities, $entities);
        }

        foreach (
            $eventArgs->getEntityManager()
                ->getUnitOfWork()
                ->getScheduledEntityDeletions() as $entityToDelete
        ) {
            if (!$entityToDelete instanceof RaiseEventsInterface) {
                continue;
            }

            $domainEventsEntities[] = $entityToDelete;
        }

        return $domainEventsEntities;
    }
}
