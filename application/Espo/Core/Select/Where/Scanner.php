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

use Espo\Core\Select\Where\Item\Type;
use Espo\ORM\Defs\Params\AttributeParam;
use Espo\ORM\EntityManager;
use Espo\ORM\Entity;
use Espo\ORM\BaseEntity;
use Espo\ORM\Query\SelectBuilder as QueryBuilder;
use Espo\ORM\QueryComposer\Util as QueryComposerUtil;
use Espo\ORM\Type\RelationType;
use RuntimeException;

/**
 * Scans where items.
 */
class Scanner
{
    /** @var array<string, Entity> */
    private array $seedHash = [];

    /** @var string[] */
    private $nestingTypeList = [
        Type::OR,
        Type::AND,
    ];

    /** @var string[] */
    private array $subQueryTypeList = [
        Type::SUBQUERY_IN,
        Type::SUBQUERY_NOT_IN,
        Type::NOT,
    ];

    public function __construct(private EntityManager $entityManager)
    {}

    /**
     * Check whether at least one has-many link appears in the where-clause.
     *
     * @since 9.0.0
     */
    public function hasRelatedMany(string $entityType, Item $item): bool
    {
        $type = $item->getType();
        $attribute = $item->getAttribute();

        if (in_array($type, $this->subQueryTypeList)) {
            return false;
        }

        if (in_array($type, $this->nestingTypeList)) {
            foreach ($item->getItemList() as $subItem) {
                if ($this->hasRelatedMany($entityType, $subItem)) {
                    return true;
                }
            }

            return false;
        }

        if (!$attribute) {
            return false;
        }

        $seed = $this->getSeed($entityType);


        foreach (QueryComposerUtil::getAllAttributesFromComplexExpression($attribute) as $expr) {
            if (!str_contains($expr, '.')) {
                continue;
            }

            [$link,] = explode('.', $expr);

            if (!$seed->hasRelation($link)) {
                continue;
            }

            $isMany = in_array($seed->getRelationType($link), [
                RelationType::HAS_MANY,
                RelationType::MANY_MANY,
                RelationType::HAS_CHILDREN,
            ]);

            if ($isMany) {
                return true;
            }
        }

        return false;
    }

    /**
     * Apply needed joins to a query builder.
     */
    public function apply(QueryBuilder $queryBuilder, Item $item): void
    {
        $entityType = $queryBuilder->build()->getFrom();

        if (!$entityType) {
            throw new RuntimeException("No entity type.");
        }

        $this->applyLeftJoinsFromItem($queryBuilder, $item, $entityType);
    }

    private function applyLeftJoinsFromItem(QueryBuilder $queryBuilder, Item $item, string $entityType): void
    {
        $type = $item->getType();
        $value = $item->getValue();
        $attribute = $item->getAttribute();

        if (in_array($type, $this->subQueryTypeList)) {
            return;
        }

        if (in_array($type, $this->nestingTypeList)) {
            if (!is_array($value)) {
                return;
            }

            foreach ($value as $subItem) {
                $this->applyLeftJoinsFromItem($queryBuilder, Item::fromRaw($subItem), $entityType);
            }

            return;
        }

        if (!$attribute) {
            return;
        }

        $this->applyLeftJoinsFromAttribute($queryBuilder, $attribute, $entityType);
    }

    private function applyLeftJoinsFromAttribute(
        QueryBuilder $queryBuilder,
        string $attribute,
        string $entityType
    ): void {

        if (str_contains($attribute, ':')) {
            $argumentList = QueryComposerUtil::getAllAttributesFromComplexExpression($attribute);

            foreach ($argumentList as $argument) {
                $this->applyLeftJoinsFromAttribute($queryBuilder, $argument, $entityType);
            }

            return;
        }

        $seed = $this->getSeed($entityType);

        if (str_contains($attribute, '.')) {
            [$link,] = explode('.', $attribute);

            if ($seed->hasRelation($link)) {
                $queryBuilder->leftJoin($link);
            }

            return;
        }

        $attributeType = $seed->getAttributeType($attribute);

        if ($attributeType === Entity::FOREIGN) {
            $relation = $this->getAttributeParam($seed, $attribute, AttributeParam::RELATION);

            if ($relation) {
                $queryBuilder->leftJoin($relation);
            }
        }
    }

    private function getSeed(string $entityType): Entity
    {
        if (!isset($this->seedHash[$entityType])) {
            $this->seedHash[$entityType] = $this->entityManager->getNewEntity($entityType);
        }

        return $this->seedHash[$entityType];
    }

    /**
     * @return mixed
     * @noinspection PhpSameParameterValueInspection
     */
    private function getAttributeParam(Entity $entity, string $attribute, string $param)
    {
        if ($entity instanceof BaseEntity) {
            return $entity->getAttributeParam($attribute, $param);
        }

        $entityDefs = $this->entityManager
            ->getDefs()
            ->getEntity($entity->getEntityType());

        if (!$entityDefs->hasAttribute($attribute)) {
            return null;
        }

        return $entityDefs->getAttribute($attribute)->getParam($param);
    }
}
