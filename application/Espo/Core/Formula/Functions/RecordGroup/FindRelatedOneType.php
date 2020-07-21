<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2020 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

namespace Espo\Core\Formula\Functions\RecordGroup;

use Espo\Core\Formula\{
    Functions\BaseFunction,
    ArgumentList,
};

use Espo\Core\Di;

class FindRelatedOneType extends BaseFunction implements
    Di\EntityManagerAware,
    Di\SelectManagerFactoryAware,
    Di\MetadataAware
{
    use Di\EntityManagerSetter;
    use Di\SelectManagerFactorySetter;
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

        $entity = $entityManager->getEntity($entityType, $id);

        if (!$entity) return null;

        $metadata = $this->metadata;

        $relationType = $entity->getRelationParam($link, 'type');

        if (in_array($relationType, ['belongsTo', 'hasOne', 'belongsToParent'])) {
            $relatedEntity = $entityManager->getRepository($entityType)->findRelated($entity, $link, [
                'select' => ['id'],
            ]);
            if (!$relatedEntity) {
                return null;
            }
            return $relatedEntity->id;
        }

        if (!$orderBy) {
            $orderBy = $metadata->get(['entityDefs', $entityType, 'collection', 'orderBy']);
            if (is_null($order)) {
                $order = $metadata->get(['entityDefs', $entityType, 'collection', 'order']) ?? 'asc';
            }
        } else {
            $order = $order ?? 'asc';
        }

        $foreignEntityType = $entity->getRelationParam($link, 'entity');
        if (!$foreignEntityType) {
            $this->throwError("Bad or not supported link '{$link}'.");
        }

        $foreignLink = $entity->getRelationParam($link, 'foreign');
        if (!$foreignLink) {
            $this->throwError("Not supported link '{$link}'.");
        }

        $selectManager = $this->selectManagerFactory->create($foreignEntityType);
        $selectParams = $selectManager->getEmptySelectParams();

        if ($relationType === 'hasChildren') {
            $selectParams['whereClause'][] = [$foreignLink . 'Id' => $entity->id];
            $selectParams['whereClause'][] = [$foreignLink . 'Type' => $entity->getEntityType()];
        } else {
            $selectManager->addJoin($foreignLink, $selectParams);
            $selectParams['whereClause'][] = [$foreignLink . '.id' => $entity->id];
        }

        if (count($args) <= 6) {
            $filter = null;
            if (count($args) == 6) {
                $filter = $this->evaluate($args[5]);
            }
            if ($filter) {
                if (!is_string($filter)) {
                    $this->throwError("Bad filter.");
                }
                $selectManager->applyFilter($filter, $selectParams);
            }
        } else {
            $i = 5;
            while ($i < count($args) - 1) {
                $key = $this->evaluate($args[$i]);
                $value = $this->evaluate($args[$i + 1]);
                $selectParams['whereClause'][] = [$key => $value];
                $i = $i + 2;
            }
        }

        if ($orderBy) {
            $selectManager->applyOrder($orderBy, $order, $selectParams);
        }

        $e = $entityManager->getRepository($foreignEntityType)->select(['id'])->findOne($selectParams);

        if ($e) return $e->id;

        return null;
    }
}
