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

namespace Espo\Core\Select\Where;

use InvalidArgumentException;

class Item
{
    private $type = null;

    private $attribute = null;

    private $value = null;

    private $dateTime = null;

    private $timeZone = null;

    protected $noAttributeTypeList = [
        'or',
        'and',
        'having',
        'not',
        'subQueryNotIn',
        'subQueryIn',
    ];

    private function __construct()
    {
    }

    public static function fromRaw(array $params) : self
    {
        $object = new self();

        $object->type = $params['type'] ?? null;
        $object->attribute = $params['attribute'] ?? $params['field'] ?? null;
        $object->value = $params['value'] ?? null;
        $object->dateTime = $params['dateTime'] ?? false;
        $object->timeZone = $params['timeZone'] ?? null;

        unset($params['field']);

        foreach ($params as $key => $value) {
            if (!property_exists($object, $key)) {
                throw new InvalidArgumentException("Unknown parameter '{$key}'.");
            }
        }

        if (!$object->type) {
            throw new InvalidArgumentException("No 'type' in where item.");
        }

        if (
            !$object->attribute &&
            !in_array($object->type, $object->noAttributeTypeList)
        ) {
            throw new InvalidArgumentException("No 'attribute' in where item.");
        }

        return $object;
    }

    public static function fromRawAndGroup(array $paramList) : self
    {
        return self::fromRaw([
            'type' => 'and',
            'value' => $paramList,
        ]);
    }

    public function getRaw() : array
    {
        $raw = [
            'type' => $this->type,
            'value' => $this->value,
        ];

        if ($this->attribute) {
            $raw['attribute'] = $this->attribute;
        }

        if ($this->dateTime) {
            $raw['dateTime'] = $this->dateTime;
        }

        if ($this->timeZone) {
            $raw['timeZone'] = $this->timeZone;
        }

        return $raw;
    }

    public function getType() : string
    {
        return $this->type;
    }

    public function getAttribute() : ?string
    {
        return $this->attribute;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    public function isDateTime() : bool
    {
        return $this->dateTime;
    }

    public function getTimeZone() : ?string
    {
        return $this->timeZone;
    }
}
