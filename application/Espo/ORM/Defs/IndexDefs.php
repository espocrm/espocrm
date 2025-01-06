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

use Espo\ORM\Defs\Params\IndexParam;

/**
 * Index definitions.
 */
class IndexDefs
{
    /** @var array<string, mixed> */
    private $data;
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
     * Get a key.
     */
    public function getKey(): string
    {
        return $this->data[IndexParam::KEY] ?? '';
    }

    /**
     * Whether is unique.
     */
    public function isUnique(): bool
    {
        // For bc.
        if (($this->data['unique'] ?? false)) {
            return true;
        }

        $type = $this->data[IndexParam::TYPE] ?? null;

        return $type === 'unique';
    }

    /**
     * Get a column list.
     *
     * @return string[]
     */
    public function getColumnList(): array
    {
        return $this->data[IndexParam::COLUMNS] ?? [];
    }

    /**
     * Get a flag list.
     *
     * @return string[]
     */
    public function getFlagList(): array
    {
        return $this->data[IndexParam::FLAGS] ?? [];
    }
}
