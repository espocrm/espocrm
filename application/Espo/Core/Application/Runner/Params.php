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

namespace Espo\Core\Application\Runner;

/**
 * Parameters for an application runner.
 */
class Params
{
    /** @var array<string, mixed> */
    private $data = [];

    public function __construct() {}

    /**
     * Get a parameter value.
     */
    public function get(string $name): mixed
    {
        return $this->data[$name] ?? null;
    }

    /**
     * Whether a parameter is set.
     */
    public function has(string $name): bool
    {
        return array_key_exists($name, $this->data);
    }

    /**
     * Clone with a parameter value.
     */
    public function with(string $name, mixed $value): self
    {
        $obj = clone $this;
        $obj->data[$name] = $value;

        return $obj;
    }

    /**
     * Create from an associative array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $obj = new self();
        $obj->data = $data;

        return $obj;
    }

    /**
     * Create an empty instance.
     */
    public static function create(): self
    {
        return new self();
    }
}
