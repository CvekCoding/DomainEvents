<?php
/*
 * This file is part of the Aqua Delivery package.
 *
 * (c) Sergey Logachev <svlogachev@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cvek\DomainEventsBundle\EventDispatch\Event;

interface DomainEventInterface
{
    /**
     * @return string
     */
    public function getLifecycleEvent(): string;

    /**
     * @param string $lifecycleEvent
     */
    public function setLifecycleEvent(string $lifecycleEvent): self;
}
