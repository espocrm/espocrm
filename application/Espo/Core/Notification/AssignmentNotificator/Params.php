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

namespace Espo\Core\Notification\AssignmentNotificator;

/**
 * Immutable.
 */
class Params
{
    /** @var array<string, mixed> */
    private $options = [];

    private ?string $actionId = null;

    /**
     * Whether an option is set.
     */
    public function hasOption(string $option): bool
    {
        return array_key_exists($option, $this->options);
    }

    /**
     * Get an option.
     *
     * @return mixed
     */
    public function getOption(string $option)
    {
        return $this->options[$option] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public function getRawOptions(): array
    {
        return $this->options;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function withRawOptions(array $options): self
    {
        $obj = clone $this;

        $obj->options = $options;

        return $obj;
    }

    /**
     * Clone with an option.
     *
     * @since 9.0.0
     */
    public function withOption(string $option, mixed $value): self
    {
        $obj = clone $this;
        $obj->options[$option] = $value;

        return $obj;
    }

    /**
     * Create an instance.
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * @since 9.2.0
     */
    public function withActionId(?string $actionId): self
    {
        $obj = clone $this;
        $obj->actionId = $actionId;

        return $obj;
    }

    /**
     * @since 9.2.0
     */
    public function getActionId(): ?string
    {
        return $this->actionId;
    }
}
