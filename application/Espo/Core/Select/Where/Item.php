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
use RuntimeException;

class Item
{
    public const TYPE_AND = 'and';

    public const TYPE_OR = 'or';

    private $type = null;

    private $attribute = null;

    private $value = null;

    private $dateTime = false;

    private $timeZone = null;

    protected $noAttributeTypeList = [
        self::TYPE_OR,
        self::TYPE_AND,
        'not',
        'subQueryNotIn',
        'subQueryIn',
        'having', // @todo Check usage. Maybe to be removed.
    ];

    protected $withNestedItemsTypeList = [
        self::TYPE_OR,
        self::TYPE_AND,
    ];

    private function __construct()
    {
    }

    public static function fromRaw(array $params): self
    {
        $obj = new self();

        $obj->type = $params['type'] ?? null;
        $obj->attribute = $params['attribute'] ?? $params['field'] ?? null;
        $obj->value = $params['value'] ?? null;
        $obj->dateTime = $params['dateTime'] ?? false;
        $obj->timeZone = $params['timeZone'] ?? null;

        unset($params['field']);

        foreach (array_keys($params) as $key) {
            if (!property_exists($obj, $key)) {
                throw new InvalidArgumentException("Unknown parameter '{$key}'.");
            }
        }

        if (!$obj->type) {
            throw new InvalidArgumentException("No 'type' in where item.");
        }

        if (
            !$obj->attribute &&
            !in_array($obj->type, $obj->noAttributeTypeList)
        ) {
            throw new InvalidArgumentException("No 'attribute' in where item.");
        }

        if (in_array($obj->type, $obj->withNestedItemsTypeList)) {
            $obj->value = $obj->value ?? [];

            if (
                !is_array($obj->value) ||
                count($obj->value) && array_keys($obj->value) !== range(0, count($obj->value) - 1)
            ) {
                throw new InvalidArgumentException("Bad 'value'.");
            }
        }

        return $obj;
    }

    public static function fromRawAndGroup(array $paramList): self
    {
        return self::fromRaw([
            'type' => self::TYPE_AND,
            'value' => $paramList,
        ]);
    }

    public function getRaw(): array
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

    /**
     * Get a type;
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Get an attribute.
     */
    public function getAttribute(): ?string
    {
        return $this->attribute;
    }

    /**
     * Get a value.
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Get nested where items (for 'and', 'or' types).
     *
     * @return Item[]
     *
     * @throws RuntimeException If a type does not support nested items.
     */
    public function getItemList(): array
    {
        if (!in_array($this->type, $this->withNestedItemsTypeList)) {
            throw new RuntimeException("Nested items not supported for '{$this->type}' type.");
        }

        $list = [];

        foreach ($this->value as $raw) {
            $list[] = Item::fromRaw($raw);
        }

        return $list;
    }

    /**
     * Whether is 'date-time'.
     */
    public function isDateTime(): bool
    {
        return $this->dateTime;
    }

    /**
     * Get a time zone. Actual only for 'date-time' items.
     */
    public function getTimeZone(): ?string
    {
        return $this->timeZone;
    }

    /**
     * Create a builder.
     */
    public static function createBuilder(): ItemBuilder
    {
        return new ItemBuilder();
    }
}
