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

namespace Espo\ORM\Query;

use Espo\ORM\Query\Part\Order;

use InvalidArgumentException;

class UnionBuilder implements Builder
{
    use BaseBuilderTrait;

    /**
     * Create an instance.
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * Build a UNION select query.
     */
    public function build(): Union
    {
        return Union::fromRaw($this->params);
    }

    /**
     * Clone an existing query for a subsequent modifying and building.
     */
    public function clone(Union $query): self
    {
        $this->cloneInternal($query);

        return $this;
    }

    /**
     * Use UNION ALL.
     */
    public function all(): self
    {
        $this->params['all'] = true;

        return $this;
    }

    public function query(Select $query): self
    {
        $this->params['queries'] = $this->params['queries'] ?? [];

        $this->params['queries'][] = $query;

        return $this;
    }

    /**
     * Apply OFFSET and LIMIT.
     */
    public function limit(?int $offset = null, ?int $limit = null): self
    {
        $this->params['offset'] = $offset;
        $this->params['limit'] = $limit;

        return $this;
    }

    /**
     * Apply ORDER.
     *
     * @param string|array $orderBy A select alias.
     * @param string|bool $direction OrderExpression::ASC|OrderExpression::DESC. TRUE for DESC order.
     */
    public function order($orderBy, $direction = Order::ASC): self
    {
        if (is_bool($direction)) {
            $direction = $direction ? Order::DESC : Order::ASC;
        }

        if (!$orderBy) {
            throw new InvalidArgumentException();
        }

        if (is_array($orderBy)) {
            foreach ($orderBy as $item) {
                if (count($item) == 2) {
                    $this->order($item[0], $item[1]);

                    continue;
                }

                if (count($item) == 1) {
                    $this->order($item[0]);

                    continue;
                }

                throw new InvalidArgumentException("Bad order.");
            }

            return $this;
        }

        /** @var object|scalar $orderBy */

        if (!is_string($orderBy) && !is_int($orderBy)) {
            throw new InvalidArgumentException("Bad order.");
        }

        $this->params['orderBy'] = $this->params['orderBy'] ?? [];

        $this->params['orderBy'][] = [$orderBy, $direction];

        return $this;
    }
}
