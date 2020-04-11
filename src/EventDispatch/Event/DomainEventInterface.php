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
    public function getLifecycleEvent(): string;

    public function setLifecycleEvent(string $lifecycleEvent): self;

    /**
     * Use bus to persist event and launch it later in bus handler
     */
    public function isAsync(): bool;
}
