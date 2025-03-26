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

namespace Espo\Core\Select\Where;

use Espo\Core\Select\Where\Item\Data;

use InvalidArgumentException;
use RuntimeException;

/**
 * A where item.
 *
 * Immutable.
 */
class Item
{
    public const TYPE_AND = Item\Type::AND;
    public const TYPE_OR = Item\Type::OR;

    private ?string $attribute = null;
    private mixed $value = null;
    private ?Data $data = null;

    /** @var string[] */
    private $noAttributeTypeList = [
        Item\Type::AND,
        Item\Type::OR,
        Item\Type::NOT,
        Item\Type::SUBQUERY_IN,
        Item\Type::SUBQUERY_NOT_IN,
    ];

    /** @var string[] */
    private $withNestedItemsTypeList = [
        Item\Type::AND,
        Item\Type::OR,
    ];

    private function __construct(private string $type)
    {}

    /**
     * @param array<string, mixed> $params
     * @internal
     */
    public static function fromRaw(array $params): self
    {
        $type = $params['type'] ?? null;

        if (!$type) {
            throw new InvalidArgumentException("No 'type' in where item.");
        }

        $obj = new self($type);

        $obj->attribute = $params['attribute'] ?? $params['field'] ?? null;
        $obj->value = $params['value'] ?? null;

        if ($params['dateTime'] ?? false) {
            $obj->data = Data\DateTime
                ::create()
                ->withTimeZone($params['timeZone'] ?? null);
        } else if ($params['date'] ?? null) {
            $obj->data = Data\Date
                ::create()
                ->withTimeZone($params['timeZone'] ?? null);
        }

        unset($params['field']);
        unset($params['dateTime']);
        unset($params['date']);
        unset($params['timeZone']);

        foreach (array_keys($params) as $key) {
            if (!property_exists($obj, $key)) {
                throw new InvalidArgumentException("Unknown parameter '$key'.");
            }
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

    /**
     * @param array<array<int|string, mixed>> $paramList
     * {@internal}
     */
    public static function fromRawAndGroup(array $paramList): self
    {
        return self::fromRaw([
            'type' => Item\Type::AND,
            'value' => $paramList,
        ]);
    }

    /**
     * @return array{
     *   type: string,
     *   value: mixed,
     *   attribute?: string,
     *   dateTime?: bool,
     *   timeZone?: string,
     * }
     * {@internal}
     */
    public function getRaw(): array
    {
        $type = $this->type;

        $raw = [
            'type' => $type,
            'value' => $this->value,
        ];

        if ($this->attribute) {
            $raw['attribute'] = $this->attribute;
        }

        if ($this->data instanceof Data\DateTime || $this->data instanceof Data\Date) {
            if ($this->data instanceof Data\DateTime) {
                $raw['dateTime'] = true;
            }

            $timeZone = $this->data->getTimeZone();

            if ($timeZone) {
                $raw['timeZone'] = $timeZone;
            }
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
     * @throws RuntimeException If a type does not support nested items.
     */
    public function getItemList(): array
    {
        if (!in_array($this->type, $this->withNestedItemsTypeList)) {
            throw new RuntimeException("Nested items not supported for '$this->type' type.");
        }

        $list = [];

        foreach ($this->value as $raw) {
            $list[] = Item::fromRaw($raw);
        }

        return $list;
    }

    /**
     * Get a data-object.
     */
    public function getData(): ?Data
    {
        return $this->data;
    }

    /**
     * Create a builder.
     */
    public static function createBuilder(): ItemBuilder
    {
        return new ItemBuilder();
    }

    /**
     * Clone with data.
     *
     * {@internal}
     */
    public function withData(?Data $data): self
    {
        $obj = clone $this;
        $obj->data = $data;

        return $obj;
    }
}
