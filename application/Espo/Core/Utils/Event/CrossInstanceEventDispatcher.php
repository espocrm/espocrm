<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
 * Website: https://www.espocrm.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Core\Utils\Event;

use Closure;
use RuntimeException;
use Throwable;

class CrossInstanceEventDispatcher
{
    /**
     * @var array<class-string<Event>, (Closure(CrossInstanceEvent, Context): void)[]>
     */
    private array $callbacks = [];

    private bool $isTransportSubscribed = false;

    public function __construct(
        private EventTransport $transport,
        private OriginProvider $originProvider,
    ) {}

    /**
     * @param class-string<CrossInstanceEvent> $className
     * @param Closure(CrossInstanceEvent, Context): void $callback
     */
    public function subscribe(string $className, Closure $callback): void
    {
        $this->ensureSubscribeTransport();

        $this->callbacks[$className] ??= [];
        $this->callbacks[$className][] = $callback;
    }

    /**
     * @param class-string<CrossInstanceEvent> $className
     * @param Closure(CrossInstanceEvent, Context): void $callback
     */
    public function unsubscribe(string $className, Closure $callback): void
    {
        if (!array_key_exists($className, $this->callbacks)) {
            return;
        }

        $list = &$this->callbacks[$className];

        $index = array_search($callback, $list);

        if ($index !== false) {
            unset($list[$index]);

            $list = array_values($list);
        }
    }

    public function dispatch(CrossInstanceEvent $event): void
    {
        $envelope = new Envelope(
            eventClassName: $event::class,
            payload: $event->toPayload(),
            origin: $this->originProvider->get(),
        );

        $this->transport->publish($envelope);
    }

    private function ensureSubscribeTransport(): void
    {
        if ($this->isTransportSubscribed) {
            return;
        }

        $this->transport->subscribe(fn (Envelope $envelope) => $this->transportCallback($envelope));

        $this->isTransportSubscribed = true;
    }

    private function transportCallback(Envelope $envelope): void
    {
        if ($envelope->origin === $this->originProvider->get()) {
            return;
        }

        $className = $envelope->eventClassName;

        if (!is_subclass_of($className, CrossInstanceEvent::class)) {
            throw new RuntimeException("Non-valid event class name.");
        }

        $callbacks = $this->callbacks[$className] ?? [];

        if ($callbacks === []) {
            return;
        }

        try {
            $event = $className::fromPayload($envelope->payload);
        } catch (Throwable $e) {
            throw new RuntimeException("Could not hydrate event '$className'.", previous: $e);
        }

        $context = new Context(
            isLocal: false,
            origin: $envelope->origin,
        );

        foreach ($callbacks as $callback) {
            $callback($event, $context);
        }
    }
}
