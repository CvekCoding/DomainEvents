<?php
/**
 * This file is part of the Diningedge package.
 *
 * (c) Sergey Logachev <svlogachev@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cvek\DomainEventsBundle\Entity;

use Cvek\DomainEventsBundle\EventDispatch\Event\DomainEventInterface;

interface RaiseEventsInterface
{
    /**
     * @return DomainEventInterface[]
     */
    public function popEvents(): array;

    public function raise(DomainEventInterface $event);

    public function clearEvents();

    public function hasEvent(DomainEventInterface $event): bool;
}
