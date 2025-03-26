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

namespace Espo\Core\Select;

use Espo\Core\Select\Where\Item as WhereItem;
use Espo\Core\Utils\Json;

use InvalidArgumentException;
use stdClass;

/**
 * Search parameters.
 *
 * Immutable.
 */
class SearchParams
{
    /** @var array<string, mixed> */
    private array $rawParams = [
        'select' => null,
        'boolFilterList' => [],
        'orderBy' => null,
        'order' => null,
        'maxSize' => null,
        'where' => null,
        'primaryFilter' => null,
        'textFilter' => null,
    ];

    public const ORDER_ASC = 'ASC';
    public const ORDER_DESC = 'DESC';

    private function __construct() {}

    /**
     * @return array<string, mixed>
     */
    public function getRaw(): array
    {
        return $this->rawParams;
    }

    /**
     * Attributes to be selected.
     *
     * @return ?string[]
     */
    public function getSelect(): ?array
    {
        return $this->rawParams['select'] ?? null;
    }

    /**
     * An order-by field.
     */
    public function getOrderBy(): ?string
    {
        return $this->rawParams['orderBy'] ?? null;
    }

    /**
     * An order direction.
     *
     * @return self::ORDER_ASC|self::ORDER_DESC
     */
    public function getOrder(): ?string
    {
        return $this->rawParams['order'] ?? null;
    }

    /**
     * An offset.
     */
    public function getOffset(): ?int
    {
        return $this->rawParams['offset'] ?? null;
    }

    /**
     * A max-size.
     */
    public function getMaxSize(): ?int
    {
        return $this->rawParams['maxSize'] ?? null;
    }

    /**
     * A text filter.
     */
    public function getTextFilter(): ?string
    {
        return $this->rawParams['textFilter'] ?? null;
    }

    /**
     * A primary filter.
     */
    public function getPrimaryFilter(): ?string
    {
        return $this->rawParams['primaryFilter'] ?? null;
    }

    /**
     * A bool filter list.
     *
     * @return string[]
     */
    public function getBoolFilterList(): array
    {
        return $this->rawParams['boolFilterList'] ?? [];
    }

    /**
     * A where.
     */
    public function getWhere(): ?WhereItem
    {
        $raw = $this->rawParams['where'] ?? null;

        if ($raw === null) {
            return null;
        }

        return WhereItem::fromRaw([
            'type' => 'and',
            'value' => $raw,
        ]);
    }

    /**
     * A max text attribute length.
     */
    public function getMaxTextAttributeLength(): ?int
    {
        return $this->rawParams['maxTextAttributeLength'] ?? null;
    }

    /**
     * With attributes to be selected. NULL means to select all attributes.
     *
     * @param string[]|null $select
     */
    public function withSelect(?array $select): self
    {
        $obj = clone $this;
        $obj->rawParams['select'] = $select;

        return $obj;
    }

    /**
     * With an order-by field.
     */
    public function withOrderBy(?string $orderBy): self
    {
        $obj = clone $this;
        $obj->rawParams['orderBy'] = $orderBy;

        return $obj;
    }

    /**
     * With an order direction.
     *
     * @param self::ORDER_ASC|self::ORDER_DESC|null $order
     */
    public function withOrder(?string $order): self
    {
        $obj = clone $this;
        $obj->rawParams['order'] = $order;

        if (
            $order !== null &&
            $order !== self::ORDER_ASC &&
            $order !== self::ORDER_DESC
        ) {
            throw new InvalidArgumentException("order value is bad.");
        }

        return $obj;
    }

    /**
     * With an offset.
     */
    public function withOffset(?int $offset): self
    {
        $obj = clone $this;
        $obj->rawParams['offset'] = $offset;

        return $obj;
    }

    /**
     * With a mix size.
     */
    public function withMaxSize(?int $maxSize): self
    {
        $obj = clone $this;
        $obj->rawParams['maxSize'] = $maxSize;

        return $obj;
    }

    /**
     * With a text filter.
     */
    public function withTextFilter(?string $filter): self
    {
        $obj = clone $this;
        $obj->rawParams['textFilter'] = $filter;

        return $obj;
    }

    /**
     * With a primary filter.
     *
     * @param string|null $primaryFilter
     * @return $this
     */
    public function withPrimaryFilter(?string $primaryFilter): self
    {
        $obj = clone $this;
        $obj->rawParams['primaryFilter'] = $primaryFilter;

        return $obj;
    }

    /**
     * With a bool filter list. Previously set bool filter will be unset.
     *
     * @param string[] $boolFilterList
     */
    public function withBoolFilterList(array $boolFilterList): self
    {
        $obj = clone $this;
        $obj->rawParams['boolFilterList'] = $boolFilterList;

        return $obj;
    }

    public function withBoolFilterAdded(string $boolFilter): self
    {
        $obj = clone $this;
        $obj->rawParams['boolFilterList'] ??= [];
        $obj->rawParams['boolFilterList'][] = $boolFilter;

        return $obj;
    }

    /**
     * With a where. The previously set where will be unset.
     */
    public function withWhere(WhereItem $where): self
    {
        $obj = clone $this;

        if ($where->getType() === WhereItem\Type::AND) {
            $obj->rawParams['where'] = $where->getValue() ?? [];

            return $obj;
        }

        $obj->rawParams['where'] = [$where->getRaw()];

        return $obj;
    }

    /**
     * With a where added.
     */
    public function withWhereAdded(WhereItem $whereItem): self
    {
        $obj = clone $this;

        $rawWhere = $obj->rawParams['where'] ?? [];

        $rawWhere[] = $whereItem->getRaw();

        $obj->rawParams['where'] = $rawWhere;

        return $obj;
    }

    /**
     * With max text attribute length (long texts will be cut to avoid fetching too much data).
     */
    public function withMaxTextAttributeLength(?int $value): self
    {
        $obj = clone $this;

        $obj->rawParams['maxTextAttributeLength'] = $value;

        return $obj;
    }

    /**
     * Create an empty instance.
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * Create an instance from a raw.
     *
     * @param stdClass|array<string, mixed> $params
     */
    public static function fromRaw($params): self
    {
        if (!is_array($params) && !$params instanceof stdClass) {
            throw new InvalidArgumentException();
        }

        if ($params instanceof stdClass) {
            $params = json_decode(Json::encode($params), true);
        }

        $object = new self();

        $rawParams = [];

        $select = $params['select'] ?? null;
        $orderBy = $params['orderBy'] ?? null;
        $order = $params['order'] ?? null;

        $offset = $params['offset'] ?? null;
        $maxSize = $params['maxSize'] ?? null;

        // For bc.
        if (is_string($offset) && is_numeric($offset)) {
            $offset = (int) $offset;
        }

        // For bc.
        if (is_string($maxSize) && is_numeric($maxSize)) {
            $maxSize = (int) $maxSize;
        }

        $boolFilterList = $params['boolFilterList'] ?? [];
        $primaryFilter = $params['primaryFilter'] ?? null;
        $textFilter = $params['textFilter'] ?? $params['q'] ?? null;

        $where = $params['where'] ?? null;

        $maxTextAttributeLength = $params['maxTextAttributeLength'] ?? null;

        if ($select !== null && !is_array($select)) {
            throw new InvalidArgumentException("select should be array.");
        }

        if (is_array($select)) {
            foreach ($select as $item) {
                if (!is_string($item)) {
                    throw new InvalidArgumentException("select has non-string item.");
                }
            }
        }

        if ($orderBy !== null && !is_string($orderBy)) {
            throw new InvalidArgumentException("orderBy should be string.");
        }

        if ($order !== null && !is_string($order)) {
            throw new InvalidArgumentException("order should be string.");
        }

        if (!is_array($boolFilterList)) {
            throw new InvalidArgumentException("boolFilterList should be array.");
        }

        foreach ($boolFilterList as $item) {
            if (!is_string($item)) {
                throw new InvalidArgumentException("boolFilterList has non-string item.");
            }
        }

        if ($primaryFilter !== null && !is_string($primaryFilter)) {
            throw new InvalidArgumentException("primaryFilter should be string.");
        }

        if ($textFilter !== null && !is_string($textFilter)) {
            throw new InvalidArgumentException("textFilter should be string.");
        }

        if ($where !== null && !is_array($where)) {
            throw new InvalidArgumentException("where should be array.");
        }

        if ($offset !== null && !is_int($offset)) {
            throw new InvalidArgumentException("offset should be int.");
        }

        if ($maxSize !== null && !is_int($maxSize)) {
            throw new InvalidArgumentException("maxSize should be int.");
        }

        if ($maxTextAttributeLength && !is_int($maxTextAttributeLength)) {
            throw new InvalidArgumentException("maxTextAttributeLength should be int.");
        }

        if ($order) {
            $order = strtoupper($order);

            if ($order !== self::ORDER_ASC && $order !== self::ORDER_DESC) {
                throw new InvalidArgumentException("order value is bad.");
            }
        }

        $rawParams['select'] = $select;
        $rawParams['orderBy'] = $orderBy;
        $rawParams['order'] = $order;
        $rawParams['offset'] = $offset;
        $rawParams['maxSize'] = $maxSize;
        $rawParams['boolFilterList'] = $boolFilterList;
        $rawParams['primaryFilter'] = $primaryFilter;
        $rawParams['textFilter'] = $textFilter;
        $rawParams['where'] = $where;
        $rawParams['maxTextAttributeLength'] = $maxTextAttributeLength;

        if ($where) {
            $object->adjustParams($rawParams);
        }

        $object->rawParams = $rawParams;

        return $object;
    }

    /**
     * Merge two SelectParams instances.
     */
    public static function merge(self $searchParams1, self $searchParams2): self
    {
        $paramList = [
            'select',
            'orderBy',
            'order',
            'maxSize',
            'primaryFilter',
            'textFilter',
        ];

        $params = $searchParams2->getRaw();

        $leftParams = $searchParams1->getRaw();

        foreach ($paramList as $name) {
            if (!is_null($leftParams[$name])) {
                $params[$name] = $leftParams[$name];
            }
        }

        foreach ($leftParams['boolFilterList'] as $item) {
            if (in_array($item, $params['boolFilterList'])) {
                continue;
            }

            $params['boolFilterList'][] = $item;
        }

        $params['where'] = $params['where'] ?? [];

        if (!is_null($leftParams['where'])) {
            foreach ($leftParams['where'] as $item) {
                $params['where'][] = $item;
            }
        }

        if (count($params['where']) === 0) {
            $params['where'] = null;
        }

        return self::fromRaw($params);
    }

    /**
     * For compatibility with the legacy definition.
     *
     * @param array<string, mixed> $params
     */
    private function adjustParams(array &$params): void
    {
        if (!$params['where']) {
            return;
        }

        $where = $params['where'];

        foreach ($where as $i => $item) {
            $type = $item['type'] ?? null;
            $value = $item['value'] ?? null;

            if ($type == 'bool' && !empty($value) && is_array($value)) {
                $params['boolFilterList'] = $value;

                unset($where[$i]);
            } else if ($type === 'textFilter') {
                $params['textFilter'] = $value;

                unset($where[$i]);
            } else if ($type == 'primary' && $value) {
                $params['primaryFilter'] = $value;

                unset($where[$i]);
            }
        }

        $params['where'] = array_values($where);
    }
}
