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

namespace Espo\ORM\Defs;

use Espo\ORM\Defs\Params\FieldParam;
use RuntimeException;

/**
 * Field definitions.
 */
class FieldDefs
{
    /** @var array<string, mixed> */
    private array $data;
    private string $name;

    private function __construct()
    {}

    /**
     * @param array<string, mixed> $raw
     */
    public static function fromRaw(array $raw, string $name): self
    {
        $obj = new self();
        $obj->data = $raw;
        $obj->name = $name;

        return $obj;
    }

    /**
     * Get a name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get a type.
     */
    public function getType(): string
    {
        $type = $this->data[FieldParam::TYPE] ?? null;

        if ($type === null) {
            throw new RuntimeException("Field '$this->name' has no type.");
        }

        return $type;
    }

    /**
     * Whether is not-storable.
     */
    public function isNotStorable(): bool
    {
        return $this->data[FieldParam::NOT_STORABLE] ?? false;
    }

    /**
     * Get a parameter value by a name.
     */
    public function getParam(string $name): mixed
    {
        return $this->data[$name] ?? null;
    }

    /**
     * Has a parameter value.
     */
    public function hasParam(string $name): bool
    {
        return array_key_exists($name, $this->data);
    }
}
