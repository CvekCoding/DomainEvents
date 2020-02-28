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

use Symfony\Contracts\EventDispatcher\Event;

trait RaiseEventsTrait
{
    /**
     * @var Event[]
     */
    private array $events = [];

    /**
     * @return Event[]
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

    public function raise(Event $event): self
    {
        $this->events[] = $event;

        return $this;
    }

    /**
     * @param Event|string $needle
     *
     * @return bool
     */
    public function hasEvent($needle): bool
    {
        foreach ($this->events as $event) {
            if ($event instanceof $needle) {
                return true;
            }
        }

        return false;
    }
}
