<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Select\Where\Item\Type;
use Espo\ORM\Query\Part\WhereClause;
use Espo\ORM\Query\Part\WhereItem;
use Espo\ORM\Query\SelectBuilder as QueryBuilder;
use InvalidArgumentException;

/**
 * Converts a search where (passed from front-end) to a where clause (for ORM).
 */
class Converter
{
    public function __construct(
        private ItemConverter $itemConverter,
        private Scanner $scanner
    ) {}

    /**
     * @throws BadRequest
     */
    public function convert(QueryBuilder $queryBuilder, Item $item): WhereItem
    {
        $whereClause = [];

        foreach ($this->itemToList($item) as $subItemRaw) {
            try {
                $subItem = Item::fromRaw($subItemRaw);
            }
            catch (InvalidArgumentException $e) {
                throw new BadRequest($e->getMessage());
            }

            $part = $this->processItem($queryBuilder, $subItem);

            if ($part === []) {
                continue;
            }

            $whereClause[] = $part;
        }

        $this->scanner->apply($queryBuilder, $item);

        return WhereClause::fromRaw($whereClause);
    }

    /**
     * @return array<int|string, mixed>
     * @throws BadRequest
     */
    private function itemToList(Item $item): array
    {
        if ($item->getType() !== Type::AND) {
            return [
                $item->getRaw(),
            ];
        }

        $list = $item->getValue();

        if (!is_array($list)) {
            throw new BadRequest("Bad where item value.");
        }

        return $list;
    }

    /**
     * @return array<int|string, mixed>
     * @throws BadRequest
     */
    private function processItem(QueryBuilder $queryBuilder, Item $item): array
    {
        return $this->itemConverter->convert($queryBuilder, $item)->getRaw();
    }
}
