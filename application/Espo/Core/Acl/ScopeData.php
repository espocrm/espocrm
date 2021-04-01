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

use StdClass;
use InvalidArgumentException;

/**
 * A scope data.
 */
class ScopeData
{
    private $raw;

    private $actionData = [];

    private $isBoolean = false;

    private function __construct()
    {
    }

    /**
     * Get a raw value.
     *
     * @return StdClass|boo
     */
    public function getRaw()
    {
        if (!$this->isBoolean()) {
            return clone $this->raw;
        }

        return $this->raw;
    }

    public function isBoolean() : bool
    {
        return $this->isBoolean;
    }

    public function isTrue() : bool
    {
        if (!$this->isBoolean) {
            return false;
        }

        return $this->raw === true;
    }

    public function isFalse() : bool
    {
        if (!$this->isBoolean) {
            return false;
        }

        return $this->raw === false;
    }

    public function get(string $action) : string
    {
        return $this->actionData[$action] ?? Table::LEVEL_NO;
    }

    public function getRead() : string
    {
        return $this->get(Table::ACTION_READ);
    }

    public function getStream() : string
    {
        return $this->get(Table::ACTION_STREAM);
    }

    public function getCreate() : string
    {
        return $this->get(Table::ACTION_CREATE);
    }

    public function getEdit() : string
    {
        return $this->get(Table::ACTION_EDIT);
    }

    public function getDelete() : string
    {
        return $this->get(Table::ACTION_DELETE);
    }

    /**
     * Create from a raw table value.
     *
     * @param StdClass|bool $raw
     * @return self
     */
    public static function fromRaw($raw) : self
    {
        $obj = new self();

        if ($raw instanceof StdClass) {
            $obj->isBoolean = false;

            $obj->actionData = get_object_vars($raw);
        }
        else if (is_bool($raw)) {
            $obj->isBoolean = true;
        }
        else {
            throw new InvalidArgumentException();
        }

        $obj->raw = $raw;

        return $obj;
    }
}
