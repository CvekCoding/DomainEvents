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

namespace Cvek\DomainEventsBundle\Entity;

use Cvek\DomainEventsBundle\EventDispatch\Event\DomainEventInterface;

trait RaiseEventsTrait
{
    /**
     * @var DomainEventInterface[]
     */
    private array $events = [];

    /**
     * @return DomainEventInterface[]
     */
    public function popEvents(): array
    {
        return $this->events;
    }

    public function clearEvents(): self
    {
        $this->events = [];

        return $this;
    }

    public function raise(DomainEventInterface $event): self
    {
        $this->events[] = $event;

        return $this;
    }

    /**
     * @param DomainEventInterface|string $needle
     *
     * @return bool
     */
    public function hasEvent($needle = null): bool
    {
        if (!isset($needle)) {
            return false;
        }

        foreach ($this->events as $event) {
            if ($event instanceof $needle) {
                return true;
            }
        }

        return false;
    }
}
