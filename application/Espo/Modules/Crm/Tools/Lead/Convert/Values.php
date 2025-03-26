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

namespace Espo\Modules\Crm\Tools\Lead\Convert;

use Espo\Core\Utils\ObjectUtil;
use stdClass;
use UnexpectedValueException;

/**
 * Raw attribute values of multiple records.
 *
 * Immutable.
 */
class Values
{
    /** @var array<string, stdClass> */
    private array $data = [];

    public static function create(): self
    {
        return new self();
    }

    public function has(string $entityType): bool
    {
        return array_key_exists($entityType, $this->data);
    }

    public function get(string $entityType): stdClass
    {
        $data = $this->data[$entityType] ?? null;

        if ($data === null) {
            throw new UnexpectedValueException();
        }

        return ObjectUtil::clone($data);
    }

    public function with(string $entityType, stdClass $data): self
    {
        $obj = clone $this;
        $obj->data[$entityType] = ObjectUtil::clone($data);

        return $obj;
    }

    public function getRaw(): stdClass
    {
        $data = (object) [];

        foreach ($this->data as $entityType => $item) {
            $data->$entityType = ObjectUtil::clone($item);
        }

        return $data;
    }
}
