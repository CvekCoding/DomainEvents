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

use Symfony\Contracts\EventDispatcher\Event;

interface RaiseEventsInterface
{
    /**
     * @return Event[]
     */
    public function popEvents(): array;

    public function raise(Event $event);

    public function clearEvents();

    public function hasEvent(Event $event): bool;
}
