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

namespace Espo\Core\FieldSanitize\Sanitizer;

use stdClass;

/**
 * Input data. No 'clear' method, as unsetting is not supposed to happen in sanitization.
 */
class Data
{
    public function __construct(private stdClass $data)
    {}

    /**
     * Get a value.
     */
    public function get(string $attribute): mixed
    {
        return $this->data->$attribute ?? null;
    }


    /**
     * Whether a value is set.
     */
    public function has(string $attribute): bool
    {
        return property_exists($this->data, $attribute);
    }

    /**
     * Update a value.
     */
    public function set(string $attribute, mixed $value): self
    {
        $this->data->$attribute = $value;

        return $this;
    }

    /**
     * Unset an attribute.
     */
    public function clear(string $attribute): self
    {
        unset($this->data->$attribute);

        return $this;
    }
}
