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

namespace Espo\Core\Select\Where\ItemConverters;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Select\Where\Item;
use Espo\Core\Select\Where\ItemConverter;
use Espo\ORM\Defs;
use Espo\ORM\Entity;
use Espo\ORM\Query\Part\Condition as Cond;
use Espo\ORM\Query\Part\Expression as Expr;
use Espo\ORM\Query\Part\WhereClause;
use Espo\ORM\Query\Part\WhereItem as WhereClauseItem;
use Espo\ORM\Query\SelectBuilder as QueryBuilder;

/**
 * @noinspection PhpUnused
 */
class InCategory implements ItemConverter
{
    public function __construct(
        private string $entityType,
        private Defs $ormDefs
    ) {}

    public function convert(QueryBuilder $queryBuilder, Item $item): WhereClauseItem
    {
        $link = $item->getAttribute();
        $value = $item->getValue();

        if (!$link) {
            throw new BadRequest("No attribute.");
        }

        if ($value === null) {
            return WhereClause::create();
        }

        if (!is_array($value)) {
            $value = [$value];
        }

        foreach ($value as $it) {
            if (!is_string($it) && !is_int($it)) {
                throw new BadRequest("Bad where item. Bad array item.");
            }
        }

        $entityDefs = $this->ormDefs->getEntity($this->entityType);

        if (!$entityDefs->hasRelation($link)) {
            throw new BadRequest("Not existing '$link' in where item.");
        }

        $defs = $entityDefs->getRelation($link);

        $path = lcfirst($defs->getForeignEntityType()) . 'Path';

        if ($defs->getType() === Entity::MANY_MANY) {
            $middle = ucfirst($defs->getRelationshipName());

            return Cond::in(
                Expr::column('id'),
                QueryBuilder::create()
                    ->from($middle, 'm')
                    ->select($defs->getMidKey())
                    ->join(
                        ucfirst($path),
                        $path,
                        ["$path.descendorId:" => "m.{$defs->getForeignMidKey()}"]
                    )
                    ->where(["$path.ascendorId" => $value])
                    ->build()
            );
        }

        if ($defs->getType() === Entity::BELONGS_TO) {
            $queryBuilder->join(
                ucfirst($path),
                $path,
                ["$path.descendorId:" => $defs->getKey()]
            );

            return WhereClause::fromRaw(["$path.ascendorId" => $value]);
        }

        throw new BadRequest("Not supported link '$link' in where item.");
    }
}
