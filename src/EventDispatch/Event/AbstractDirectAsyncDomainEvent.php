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

use Doctrine\ORM\Events;

abstract class AbstractDirectAsyncDomainEvent implements DomainEventInterface
{
    private bool $alreadyDispatched = false;

    public function getLifecycleEvent(): string
    {
        return Events::onFlush;
    }

    public function setLifecycleEvent(string $lifecycleEvent)
    {
    }

    public function isAlreadyDispatched(): bool
    {
        return $this->alreadyDispatched;
    }

    public function setDispatched(): self
    {
        $this->alreadyDispatched = true;

        return $this;
    }
}
