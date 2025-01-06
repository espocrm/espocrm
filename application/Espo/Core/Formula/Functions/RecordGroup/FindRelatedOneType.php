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

namespace Espo\Core\Formula\Functions\RecordGroup;

use Espo\Core\ORM\Entity as CoreEntity;

use Espo\ORM\Defs\Params\RelationParam;
use Espo\ORM\Name\Attribute;
use Espo\Core\Formula\{
    Functions\BaseFunction,
    ArgumentList,
};

use Espo\Core\Di;

class FindRelatedOneType extends BaseFunction implements
    Di\EntityManagerAware,
    Di\SelectBuilderFactoryAware,
    Di\MetadataAware
{
    use Di\EntityManagerSetter;
    use Di\SelectBuilderFactorySetter;
    use Di\MetadataSetter;

    public function process(ArgumentList $args)
    {
        if (count($args) < 3) {
            $this->throwTooFewArguments(3);
        }

        $entityManager = $this->entityManager;

        $entityType = $this->evaluate($args[0]);
        $id = $this->evaluate($args[1]);
        $link = $this->evaluate($args[2]);

        $orderBy = null;
        $order = null;

        if (count($args) > 3) {
            $orderBy = $this->evaluate($args[3]);
        }

        if (count($args) > 4) {
            $order = $this->evaluate($args[4]) ?? null;
        }

        if (!$entityType) {
            $this->throwBadArgumentType(1, 'string');
        }

        if (!$id) {
            return null;
        }

        if (!$link) {
            $this->throwBadArgumentType(3, 'string');
        }

        $entity = $entityManager->getEntityById($entityType, $id);

        if (!$entity) {
            return null;
        }

        $metadata = $this->metadata;

        if (!$entity instanceof CoreEntity) {
            $this->throwError("Only core entities are supported.");
        }

        $relationType = $entity->getRelationParam($link, 'type');

        if (in_array($relationType, ['belongsTo', 'hasOne', 'belongsToParent'])) {
            $relatedEntity = $entityManager
                ->getRDBRepository($entityType)
                ->getRelation($entity, $link)
                ->select([Attribute::ID])
                ->findOne();

            if (!$relatedEntity) {
                return null;
            }

            return $relatedEntity->getId();
        }

        if (!$orderBy) {
            $orderBy = $metadata->get(['entityDefs', $entityType, 'collection', 'orderBy']);

            if (is_null($order)) {
                $order = $metadata->get(['entityDefs', $entityType, 'collection', 'order']) ?? 'ASC';
            }
        } else {
            $order = $order ?? 'ASC';
        }

        $foreignEntityType = $entity->getRelationParam($link, RelationParam::ENTITY);

        if (!$foreignEntityType) {
            $this->throwError("Bad or not supported link '$link'.");
        }

        $foreignLink = $entity->getRelationParam($link, RelationParam::FOREIGN);

        if (!$foreignLink) {
            $this->throwError("Not supported link '$link'.");
        }

        $builder = $this->selectBuilderFactory
            ->create()
            ->from($foreignEntityType);

        $whereClause = [];

        if (count($args) <= 6) {
            $filter = null;

            if (count($args) == 6) {
                $filter = $this->evaluate($args[5]);
            }

            if ($filter && !is_string($filter)) {
                $this->throwError("Bad filter.");
            }

            if ($filter) {
                $builder->withPrimaryFilter($filter);
            }
        } else {
            $i = 5;

            while ($i < count($args) - 1) {
                $key = $this->evaluate($args[$i]);
                $value = $this->evaluate($args[$i + 1]);

                $whereClause[] = [$key => $value];

                $i = $i + 2;
            }
        }

        $queryBuilder = $builder->buildQueryBuilder();

        if (!empty($whereClause)) {
            $queryBuilder->where($whereClause);
        }

        if ($relationType === 'hasChildren') {
            $queryBuilder->where([
                $foreignLink . 'Id' => $entity->getId(),
                $foreignLink . 'Type' => $entity->getEntityType(),
            ]);
        } else {
            $queryBuilder
                ->join($foreignLink)
                ->where([
                    $foreignLink . '.id' => $entity->getId(),
                ]);
        }

        if ($orderBy) {
            $queryBuilder->order($orderBy, $order);
        }

        $relatedEntity = $entityManager
            ->getRDBRepository($foreignEntityType)
            ->clone($queryBuilder->build())
            ->select([Attribute::ID])
            ->findOne();

        if ($relatedEntity) {
            return $relatedEntity->getId();
        }

        return null;
    }
}
