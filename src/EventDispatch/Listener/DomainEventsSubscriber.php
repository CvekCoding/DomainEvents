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
use Cvek\DomainEventsBundle\EventDispatch\Event\DomainEventInterface;
use Doctrine\Common\EventArgs;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Tightenco\Collect\Support\Collection;

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

        $this->fillEntities($eventArgs)
            ->flatMap(static function (RaiseEventsInterface $entity) {
                return $entity->popEvents();
            })
            ->each(function (DomainEventInterface $event) {
                if ($event instanceof AbstractSyncDomainEvent) {
                    $this->eventDispatcher->dispatch($event->setLifecycleEvent(Events::preFlush));
                }
            })
        ;

        $this->preFlushAlreadyInvoked = false;
    }

    public function onFlush(OnFlushEventArgs $eventArgs): void
    {
        if ($this->onFlushAlreadyInvoked) {
            return;
        }
        $this->onFlushAlreadyInvoked = true;

        $this->fillEntities($eventArgs)
            ->flatMap(static function (RaiseEventsInterface $entity) {
                return $entity->popEvents();
            })
            ->each(function (DomainEventInterface $event) {
                if ($event instanceof AbstractAsyncDomainEvent) {
                    $this->bus->dispatch($event->setLifecycleEvent(Events::onFlush));
                } else if ($event instanceof AbstractSyncDomainEvent) {
                    $this->eventDispatcher->dispatch($event->setLifecycleEvent(Events::onFlush));
                } else if (!$event->isAlreadyDispatched()) {
                    $this->bus->dispatch($event->setDispatched());
                }
            })
        ;

        $this->onFlushAlreadyInvoked = false;
    }

    public function postFlush(PostFlushEventArgs $eventArgs): void
    {
        if ($this->postFlushAlreadyInvoked) {
            return;
        }
        $this->postFlushAlreadyInvoked = true;

        $this->fillEntities($eventArgs)
            ->flatMap(static function (RaiseEventsInterface $entity) {
                return $entity->popEvents();
            })
            ->each(function (DomainEventInterface $event) {
                if ($event instanceof AbstractSyncDomainEvent) {
                    $this->eventDispatcher->dispatch($event->setLifecycleEvent(Events::postFlush));
                }
            })
        ;

        $this->postFlushAlreadyInvoked = false;
    }

    /**
     * @param PreFlushEventArgs|OnFlushEventArgs|PostFlushEventArgs $eventArgs
     *
     * @return RaiseEventsInterface[]
     */
    private function fillEntities(EventArgs $eventArgs): Collection
    {
        $domainEventsEntities = new Collection();
        foreach ($eventArgs->getEntityManager()->getUnitOfWork()->getIdentityMap() as $class => $entities) {
            if (!\in_array(RaiseEventsInterface::class, \class_implements($class), true)) {
                continue;
            }

            $domainEventsEntities = $domainEventsEntities->merge($entities);
        }

        foreach ($eventArgs->getEntityManager()->getUnitOfWork()->getScheduledEntityDeletions() as $entityToDelete) {
            if (!$entityToDelete instanceof RaiseEventsInterface) {
                continue;
            }

            $domainEventsEntities->add($entityToDelete);
        }

        return $domainEventsEntities;
    }
}
