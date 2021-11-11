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

use Espo\{
    Core\Exceptions\Error,
    ORM\Query\SelectBuilder as QueryBuilder,
    ORM\Query\Part\WhereClause,
    ORM\Query\Part\WhereItem,
    ORM\Entity,
    ORM\Defs as ORMDefs,
    Entities\User,
    Core\Select\Helpers\RandomStringGenerator,
};

/**
 * Converts a search where (passed from front-end) to a where clause (for ORM).
 */
class Converter
{
    private $entityType;

    private $itemConverter;

    private $scanner;

    private $randomStringGenerator;

    private $ormDefs;

    public function __construct(
        string $entityType,
        ItemConverter $itemConverter,
        Scanner $scanner,
        RandomStringGenerator $randomStringGenerator,
        ORMDefs $ormDefs
    ) {
        $this->entityType = $entityType;
        $this->itemConverter = $itemConverter;
        $this->scanner = $scanner;
        $this->randomStringGenerator = $randomStringGenerator;
        $this->ormDefs = $ormDefs;
    }

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

        $this->scanner->applyLeftJoins($queryBuilder, $item);

        return WhereClause::fromRaw($whereClause);
    }

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

    private function processItem(QueryBuilder $queryBuilder, Item $item): ?array
    {
        $type = $item->getType();
        $attribute = $item->getAttribute();
        $value = $item->getValue();

        $methodName = 'apply' . ucfirst($type);

        if (method_exists($this, $methodName)) {
            // Processing special filters. Only at the top level of the tree.

            if (!$attribute) {
                throw new Error("Bad where definition. Missing attribute.");
            }

            if (!$value) {
                return null;
            }

            return $this->$methodName($queryBuilder, $attribute, $value);
        }

        return $this->itemConverter->convert($queryBuilder, $item)->getRaw();
    }

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

        if ($entityType !== 'User') {
            throw new Error("Not supported link '{$link}' in where item.");
        }

        if ($relationType === Entity::BELONGS_TO) {
            $key = $defs->getKey();

            $aliasName = $link . 'IsUserFromTeamsFilter' . $this->randomStringGenerator->generate();

            $queryBuilder->leftJoin(
                'TeamUser',
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
