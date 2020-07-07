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

trait DomainEventTrait
{
    private ?string $lifecycleEvent = null;

    public function getLifecycleEvent(): ?string
    {
        return $this->lifecycleEvent;
    }

    public function setLifecycleEvent(string $lifecycleEvent): self
    {
        $this->lifecycleEvent = $lifecycleEvent;

        return $this;
    }
}
