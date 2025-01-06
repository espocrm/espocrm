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

namespace Espo\Core\Record\Input;

use stdClass;

class Data
{
    public function __construct(private stdClass $raw) {}

    /**
     * Get all attributes.
     *
     * @return string[]
     */
    public function getAttributeList(): array
    {
        return array_keys(get_object_vars($this->raw));
    }

    /**
     * Unset an attribute.
     *
     * @param string $name An attribute name.
     */
    public function clear(string $name): self
    {
        unset($this->raw->$name);

        return $this;
    }

    /**
     * Whether an attribute is set.
     *
     * @param string $name An attribute name.
     */
    public function has(string $name): bool
    {
        return property_exists($this->raw, $name);
    }

    /**
     * Get an attribute value.
     *
     * @param string $name An attribute name.
     */
    public function get(string $name): mixed
    {
        return $this->raw->$name ?? null;
    }

    /**
     * Set an attribute value.
     *
     * @param string $name An attribute name.
     * @param mixed $value A value
     */
    public function set(string $name, mixed $value): mixed
    {
        return $this->raw->$name = $value;
    }
}
