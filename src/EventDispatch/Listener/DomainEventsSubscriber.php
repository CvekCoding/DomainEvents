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
use Cvek\DomainEventsBundle\EventDispatch\Event\DomainEventInterface;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Symfony\Component\Messenger\MessageBusInterface;
use Tightenco\Collect\Support\Collection;

final class DomainEventsSubscriber implements EventSubscriber
{
    private bool $alreadyInvoked = false;

    private MessageBusInterface $bus;

    public function __construct(MessageBusInterface $bus)
    {
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
            Events::onFlush,
        ];
    }

    public function onFlush(OnFlushEventArgs $eventArgs): void
    {
        if ($this->alreadyInvoked) {
            return;
        }
        $this->alreadyInvoked = true;

        $this->fillEntities($eventArgs)
             ->flatMap(static function (RaiseEventsInterface $entity) {
                 return $entity->popEvents();
             })
             ->each(function (DomainEventInterface $event) {
                 $this->bus->dispatch($event->setLifecycleEvent(Events::onFlush));
             })
        ;
    }

    /**
     * @param iterable|RaiseEventsInterface[] $entities
     *
     * @return RaiseEventsInterface[]
     */
    private function fillEntities(OnFlushEventArgs $eventArgs): Collection
    {
        $domainEventsEntities = new Collection();
        foreach ($eventArgs->getEntityManager()->getUnitOfWork()->getIdentityMap() as $class => $entities) {
            if (!\in_array(RaiseEventsInterface::class, \class_implements($class), true)) {
                continue;
            }

            $domainEventsEntities = $domainEventsEntities->merge($entities);
        }

        return $domainEventsEntities;
    }
}
