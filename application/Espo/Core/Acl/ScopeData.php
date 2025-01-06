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

namespace Espo\Core\Acl;

use stdClass;
use InvalidArgumentException;
use RuntimeException;

/**
 * Scope data.
 */
class ScopeData
{
    /** @var stdClass|bool */
    private $raw;
    /** @var array<string, string> */
    private $actionData = [];
    private bool $isBoolean = false;

    private function __construct() {}

    /**
     * @return never
     */
    public function __get(string $name)
    {
        throw new RuntimeException("Accessing ScopeData properties is not allowed.");
    }

    /**
     * Is of boolean type.
     */
    public function isBoolean(): bool
    {
        return $this->isBoolean;
    }

    /**
     * Is true.
     */
    public function isTrue(): bool
    {
        if (!$this->isBoolean) {
            return false;
        }

        return $this->raw === true;
    }

    /**
     * Is false.
     */
    public function isFalse(): bool
    {
        if (!$this->isBoolean) {
            return false;
        }

        return $this->raw === false;
    }

    /**
     * Has any level other than 'no'.
     */
    public function hasNotNo(): bool
    {
        foreach ($this->actionData as $level) {
            if ($level !== Table::LEVEL_NO) {
                return true;
            }
        }

        return false;
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
     * Get a 'stream' level.
     */
    public function getStream(): string
    {
        return $this->get(Table::ACTION_STREAM);
    }

    /**
     * Get a 'create' level.
     */
    public function getCreate(): string
    {
        return $this->get(Table::ACTION_CREATE);
    }

    /**
     * Get an 'edit' level.
     */
    public function getEdit(): string
    {
        return $this->get(Table::ACTION_EDIT);
    }

    /**
     * Get a 'delete' level.
     */
    public function getDelete(): string
    {
        return $this->get(Table::ACTION_DELETE);
    }

    /**
     * Create from a raw table value.
     *
     * @param stdClass|bool $raw
     * @return self
     */
    public static function fromRaw($raw): self
    {
        /** @var mixed $raw */

        $obj = new self();

        if ($raw instanceof stdClass) {
            $obj->isBoolean = false;

            $obj->actionData = get_object_vars($raw);

            foreach ($obj->actionData as $item) {
                if (!is_string($item)) {
                    throw new RuntimeException("Bad raw scope data.");
                }
            }
        } else if (is_bool($raw)) {
            $obj->isBoolean = true;
        } else {
            throw new InvalidArgumentException();
        }

        $obj->raw = $raw;

        return $obj;
    }
}
