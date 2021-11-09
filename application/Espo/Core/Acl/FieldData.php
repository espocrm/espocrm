<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Core\Acl;

use stdClass;
use RuntimeException;

/**
 * Field data.
 */
class FieldData
{
    /**
     * @phpstan-ignore-next-line
     */
    private $raw;

    private $actionData = [];

    private function __construct()
    {
    }

    public function __get(string $name)
    {
        throw new RuntimeException("Accessing ScopeData properties is not allowed.");
    }

    /**
     * Get a level for an action.
     */
    public function get(string $action): string
    {
        return $this->actionData[$action] ?? Table::LEVEL_NO;
    }

    /**
     * Get a 'read' level.
     */
    public function getRead(): string
    {
        return $this->get(Table::ACTION_READ);
    }

    /**
     * Get an 'edit' level.
     */
    public function getEdit(): string
    {
        return $this->get(Table::ACTION_EDIT);
    }

    /**
     * Create from a raw table value.
     */
    public static function fromRaw(stdClass $raw): self
    {
        $obj = new self();

        $obj->actionData = get_object_vars($raw);

        foreach ($obj->actionData as $item) {
            if (!is_string($item)) {
                throw new RuntimeException("Bad raw scope data.");
            }
        }

        $obj->raw = $raw;

        return $obj;
    }
}
