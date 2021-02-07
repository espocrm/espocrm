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

namespace Espo\Core\Select;

use InvalidArgumentException;

/**
 * Search parameters.
 */
class SearchParams
{
    protected $rawParams;

    const ORDER_ASC = 'ASC';
    const ORDER_DESC = 'DESC';

    private function __construct()
    {
    }

    public function getRaw() : array
    {
        return $this->rawParams;
    }

    public function getSelect() : ?array
    {
        return $this->rawParams['select'] ?? null;
    }

    public function getOrderBy() : ?string
    {
        return $this->rawParams['orderBy'] ?? null;
    }

    public function getOrder() : ?string
    {
        return $this->rawParams['order'];
    }

    public function getOffset() : ?int
    {
        return $this->rawParams['offset'] ?? null;
    }

    public function getMaxSize() : ?int
    {
        return $this->rawParams['maxSize'] ?? null;
    }

    public function getTextFilter() : ?string
    {
        return $this->rawParams['textFilter'] ?? null;
    }

    public function getPrimaryFilter() : ?string
    {
        return $this->rawParams['primaryFilter'] ?? null;
    }

    public function getBoolFilterList() : array
    {
        return $this->rawParams['boolFilterList'] ?? [];
    }

    public function getWhere() : ?array
    {
        return $this->rawParams['where'] ?? null;
    }

    public function noFullTextSearch() : bool
    {
        return $this->rawParams['noFullTextSearch'];
    }

    public function getMaxTextAttributeLength() : ?int
    {
        return $this->rawParams['maxTextAttributeLength'];
    }

    /**
     * Create an instance from a raw.
     */
    public static function fromRaw(array $params) : self
    {
        $object = new self();

        $rawParams = [];

        $select = $params['select'] ?? null;
        $orderBy = $params['orderBy'] ?? null;
        $order = $params['order'] ?? null;

        $offset = $params['offset'] ?? null;
        $maxSize = $params['maxSize'] ?? null;

        $boolFilterList = $params['boolFilterList'] ?? [];
        $primaryFilter = $params['primaryFilter'] ?? null;
        $textFilter = $params['textFilter'] ?? $params['q'] ?? null;

        $where = $params['where'] ?? null;

        $maxTextAttributeLength = $params['maxTextAttributeLength'] ?? null;

        if ($select && !is_array($select)) {
            throw new InvalidArgumentException("select should be array.");
        }
        else if (is_array($select)) {
            foreach ($select as $item) {
                if (!is_string($item)) {
                    throw new InvalidArgumentException("select has non-string item.");
                }
            }
        }

        if ($orderBy && !is_string($orderBy)) {
            throw new InvalidArgumentException("orderBy should be string.");
        }

        if ($order && !is_string($order)) {
            throw new InvalidArgumentException("order should be string.");
        }

        if (!is_array($boolFilterList)) {
            throw new InvalidArgumentException("boolFilterList should be array.");
        }
        else {
            foreach ($boolFilterList as $item) {
                if (!is_string($item)) {
                    throw new InvalidArgumentException("boolFilterList has non-string item.");
                }
            }
        }

        if ($primaryFilter && !is_string($primaryFilter)) {
            throw new InvalidArgumentException("primaryFilter should be string.");
        }

        if ($textFilter && !is_string($textFilter)) {
            throw new InvalidArgumentException("textFilter should be string.");
        }

        if ($where && !is_array($where)) {
            throw new InvalidArgumentException("where should be array.");
        }

        if ($offset && !is_int($offset)) {
            throw new InvalidArgumentException("offset should be int.");
        }

        if ($maxSize && !is_int($maxSize)) {
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

        $rawParams['noFullTextSearch'] = isset($params['q']);

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
    public static function merge(self $searchParams1, self $searchParams2) : self
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

        if ($leftParams['noFullTextSearch']) {
            $params['noFullTextSearch'] = true;
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
     */
    protected function adjustParams(array &$params) : void
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
            }
            else if ($type == 'textFilter' && $value) {
                $params['textFilter'] = $value;

                unset($where[$i]);
            }
            else if ($type == 'primary' && $value) {
                $params['primaryFilter'] = $value;

                unset($where[$i]);
            }
        }

        $params['where'] = array_values($where);
    }
}
