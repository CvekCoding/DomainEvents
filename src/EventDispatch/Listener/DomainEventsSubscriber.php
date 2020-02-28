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

use Cvek\DomainEventsBundle\EventDispatch\Event\DomainEventInterface;
use Cvek\DomainEventsBundle\Entity\RaiseEventsInterface;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PreFlushEventArgs;
use Doctrine\ORM\Events;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Tightenco\Collect\Support\Collection;

final class DomainEventsSubscriber implements EventSubscriber
{
    private EventDispatcherInterface $eventDispatcher;
    private Collection $entities;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->entities = new Collection();
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
            Events::postFlush,
        ];
    }

    /**
     * @param PreFlushEventArgs $eventArgs
     */
    public function preFlush(PreFlushEventArgs $eventArgs): void
    {
        foreach ($eventArgs->getEntityManager()->getUnitOfWork()->getIdentityMap() as $class => $entities) {
            if (!\in_array(RaiseEventsInterface::class, \class_implements($class), true)) {
                continue;
            }

            $this->entities = $this->entities->merge($entities);
        }

        $eventDispatcher = $this->eventDispatcher;
        $this->entities
            ->flatMap(static function (RaiseEventsInterface $entity) {
                return $entity->popEvents();
            })
            ->each(static function (DomainEventInterface $event) use ($eventDispatcher) {
                $eventDispatcher->dispatch($event->setLifecycleEvent(Events::preFlush));
            })
        ;
    }

    public function postFlush(): void
    {
        $eventDispatcher = $this->eventDispatcher;
        $this->entities
            ->flatMap(static function (RaiseEventsInterface $entity) {
                return $entity->popEvents();
            })
            ->each(static function (DomainEventInterface $event) use ($eventDispatcher) {
                $eventDispatcher->dispatch($event->setLifecycleEvent(Events::postFlush));
            });

        $this->entities
            ->each(static function (RaiseEventsInterface $entity) {
                $entity->clearEvents();
            });
    }
}
