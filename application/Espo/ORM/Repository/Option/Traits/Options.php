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

namespace Espo\ORM\Repository\Option\Traits;

trait Options
{
    /** @var array<string, mixed> */
    private array $options;

    /**
     * @param array<string, mixed> $options
     */
    private function __construct(array $options)
    {
        $this->options = $options;
    }

    /**
     * Create from an associative array.
     *
     * @param array<string, mixed> $options
     */
    public static function fromAssoc(array $options): self
    {
        return new self($options);
    }

    /**
     * Get an option value. Returns `null` if not set.
     */
    public function get(string $option): mixed
    {
        return $this->options[$option] ?? null;
    }

    /**
     * Whether an option is set.
     */
    public function has(string $option): bool
    {
        return array_key_exists($option, $this->options);
    }

    /**
     * Clone with an option value.
     */
    public function with(string $option, mixed $value): self
    {
        $obj = clone $this;
        $obj->options[$option] = $value;

        return $obj;
    }

    /**
     * Clone with an option removed.
     */
    public function without(string $option): self
    {
        $obj = clone $this;
        unset($obj->options[$option]);

        return $obj;
    }

    /**
     * @return array<string, mixed>
     */
    public function toAssoc(): array
    {
        return $this->options;
    }
}
