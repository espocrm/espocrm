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

use Espo\Core\Exceptions\Error;
use Espo\Core\Select\Helpers\RandomStringGenerator;
use Espo\Entities\Team;
use Espo\Entities\User;
use Espo\ORM\Defs as ORMDefs;
use Espo\ORM\Entity;
use Espo\ORM\Query\Part\WhereClause;
use Espo\ORM\Query\Part\WhereItem;
use Espo\ORM\Query\SelectBuilder as QueryBuilder;

/**
 * Converts a search where (passed from front-end) to a where clause (for ORM).
 */
class Converter
{
    private const TYPE_IN_CATEGORY = 'inCategory';
    private const TYPE_IS_USER_FROM_TEAMS = 'isUserFromTeams';

    public function __construct(
        private string $entityType,
        private ItemConverter $itemConverter,
        private Scanner $scanner,
        private RandomStringGenerator $randomStringGenerator,
        private ORMDefs $ormDefs
    ) {}

    /**
     * @throws Error
     */
    public function convert(QueryBuilder $queryBuilder, Item $item): WhereItem
    {
        $whereClause = [];

        $itemList = $this->itemToList($item);

        foreach ($itemList as $subItem) {
            $part = $this->processItem($queryBuilder, Item::fromRaw($subItem));

            if (empty($part)) {
                continue;
            }

            $whereClause[] = $part;
        }

        $this->scanner->apply($queryBuilder, $item);

        return WhereClause::fromRaw($whereClause);
    }

    /**
     * @return array<int|string, mixed>
     * @throws Error
     */
    private function itemToList(Item $item): array
    {
        if ($item->getType() !== 'and') {
            return [
                $item->getRaw(),
            ];
        }

        $list = $item->getValue();

        if (!is_array($list)) {
            throw new Error("Bad where item value.");
        }

        return $list;
    }

    /**
     * @return ?array<int|string, mixed>
     * @throws Error
     */
    private function processItem(QueryBuilder $queryBuilder, Item $item): ?array
    {
        $type = $item->getType();
        $attribute = $item->getAttribute();
        $value = $item->getValue();

        if (
            $type === self::TYPE_IN_CATEGORY ||
            $type === self::TYPE_IS_USER_FROM_TEAMS
        ) {
            // Processing special filters. Only at the top level of the tree.

            if (!$attribute) {
                throw new Error("Bad where definition. Missing attribute.");
            }

            if (!$value) {
                return null;
            }

            if ($type === self::TYPE_IN_CATEGORY) {
                return $this->applyInCategory($queryBuilder, $attribute, $value);
            }

            return $this->applyIsUserFromTeams($queryBuilder, $attribute, $value);
        }

        return $this->itemConverter->convert($queryBuilder, $item)->getRaw();
    }

    /**
     * @param mixed $value
     * @return array<int|string, mixed>
     * @throws Error
     */
    private function applyInCategory(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        $link = $attribute;

        $entityDefs = $this->ormDefs->getEntity($this->entityType);

        if (!$entityDefs->hasRelation($link)) {
            throw new Error("Not existing '{$link}' in where item.");
        }

        $defs = $entityDefs->getRelation($link);

        $foreignEntity = $defs->getForeignEntityType();

        $pathName = lcfirst($foreignEntity) . 'Path';

        $relationType = $defs->getType();

        if ($relationType === Entity::MANY_MANY) {
            $queryBuilder->distinct();

            $alias = $link . 'InCategoryFilter';

            $queryBuilder->join($link, $alias);

            $key = $defs->getForeignMidKey();

            $middleName = $alias . 'Middle';

            $queryBuilder->join(
                ucfirst($pathName),
                $pathName,
                [
                    "{$pathName}.descendorId:" => "{$middleName}.{$key}",
                ]
            );

            return [
                $pathName . '.ascendorId' => $value,
            ];
        }

        if ($relationType === Entity::BELONGS_TO) {
            $key = $defs->getKey();

            $queryBuilder->join(
                ucfirst($pathName),
                $pathName,
                [
                    "{$pathName}.descendorId:" => "{$key}",
                ]
            );

            return [
                $pathName . '.ascendorId' => $value,
            ];
        }

        throw new Error("Not supported link '{$link}' in where item.");
    }

    /**
     * @param mixed $value
     * @return array<int|string, mixed>
     * @throws Error
     */
    private function applyIsUserFromTeams(QueryBuilder $queryBuilder, string $attribute, $value): array
    {
        $link = $attribute;

        if (is_array($value) && count($value) == 1) {
            $value = $value[0];
        }

        $entityDefs = $this->ormDefs->getEntity($this->entityType);

        if (!$entityDefs->hasRelation($link)) {
            throw new Error("Not existing '{$link}' in where item.");
        }

        $defs = $entityDefs->getRelation($link);

        $relationType = $defs->getType();
        $entityType = $defs->getForeignEntityType();

        if ($entityType !== User::ENTITY_TYPE) {
            throw new Error("Not supported link '{$link}' in where item.");
        }

        if ($relationType === Entity::BELONGS_TO) {
            $key = $defs->getKey();

            $aliasName = $link . 'IsUserFromTeamsFilter' . $this->randomStringGenerator->generate();

            $queryBuilder->leftJoin(
                Team::RELATIONSHIP_TEAM_USER,
                $aliasName . 'Middle',
                [
                    $aliasName . 'Middle.userId:' => $key,
                    $aliasName . 'Middle.deleted' => false,
                ]
            );

            $queryBuilder->distinct();

            return [
                $aliasName . 'Middle.teamId' => $value,
            ];
        }

        throw new Error("Not supported link '{$link}' in where item.");
    }
}
