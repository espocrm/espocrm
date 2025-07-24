<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Core\ORM\Repository\Option;

use Closure;
use Espo\Core\Utils\Util;
use Espo\ORM\Repository\Option\SaveOptions;

/**
 * A save context.
 *
 * If a save invokes another save, the context instance should not be re-used.
 * If a save invokes a relate action, the context can be passed to that action.
 *
 * @since 9.1.0
 */
class SaveContext
{
    public const NAME = 'context';

    private string $actionId;
    private bool $linkUpdated = false;

    /** @var Closure[] */
    private array $deferredActions = [];

    /**
     * @param ?string $actionId An action ID.
     */
    public function __construct(
        ?string $actionId = null,
    ) {
        $this->actionId = $actionId ?? Util::generateId();
    }

    /**
     * An action ID. Used to group notifications. If a save invokes another save, the same ID can be re-used,
     * but the context instance should not be re-used. Create a derived context for this.
     *
     * @since 9.2.0
     */
    public function getActionId(): string
    {
        return $this->actionId;
    }

    /**
     * @deprecated Since v9.2.0. Use `getActionId`.
     */
    public function getId(): string
    {
        return $this->getActionId();
    }

    public function setLinkUpdated(): self
    {
        $this->linkUpdated = true;

        return $this;
    }

    public function isLinkUpdated(): bool
    {
        return $this->linkUpdated;
    }

    /**
     * Obtain from save options.
     *
     * @return ?self
     * @since 9.2.0.
     */
    public static function obtainFromOptions(SaveOptions $options): ?self
    {
        $saveContext = $options->get(self::NAME);

        if (!$saveContext instanceof self) {
            return null;
        }

        return $saveContext;
    }

    /**
     * Obtain from raw save options.
     *
     * @param array<string, mixed> $options
     * @return ?self
     * @since 9.2.0.
     */
    public static function obtainFromRawOptions(array $options): ?self
    {
        $saveContext = $options[self::NAME] ?? null;

        if (!$saveContext instanceof self) {
            return null;
        }

        return $saveContext;
    }

    /**
     * Add a deferred action.
     *
     * @param Closure $callback A callback.
     * @since 9.2.0.
     */
    public function addDeferredAction(Closure $callback): void
    {
        $this->deferredActions[] = $callback;
    }

    /**
     * @internal
     * @since 9.2.0.
     */
    public function callDeferredActions(): void
    {
        foreach ($this->deferredActions as $callback) {
            $callback();
        }

        $this->deferredActions = [];
    }

    /**
     * Create a derived context. To be used for nested saves.
     *
     * @since 9.2.0
     */
    public function createDerived(): self
    {
        return new self($this->actionId);
    }
}
