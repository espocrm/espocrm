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

namespace Espo\Repositories;

use Espo\ORM\{
    Entity,
    QueryParams\Select as Query,
    Collection,
};

use Espo\Core\Repositories\Database;

class Import extends Database
{
    public function findResultRecords(Entity $entity, string $relationName, Query $query) : Collection
    {
        $entityType = $entity->get('entityType');

        $params = $params ?? [];


        $modifiedQuery = $this->addImportEntityJoin($entity, $relationName, $query);

        return $this->getEntityManager()
            ->getRepository($entityType)
            ->clone($modifiedQuery)
            ->find();
    }

    protected function addImportEntityJoin(Entity $entity, string $link, Query $query) : Query
    {
        $entityType = $entity->get('entityType');

        $param = null;

        switch ($link) {
            case 'imported':
                $param = 'isImported';

                break;

            case 'duplicates':
                $param = 'isDuplicate';

                break;

            case 'updated':
                $param = 'isUpdated';

                break;

            default:
                return $query;
        }

        $builder = $this->entityManager->getQueryBuilder()->clone($query);

        $builder->join(
            'ImportEntity',
            'importEntity',
            [
                'importEntity.importId' => $entity->id,
                'importEntity.entityType' => $entityType,
                'importEntity.entityId:' => 'id',
                'importEntity.' . $param => true,
            ]
        );

        return $builder->build();
    }

    public function countResultRecords(Entity $entity, string $relationName, ?Query $query = null) : int
    {
        $entityType = $entity->get('entityType');

        $query = $query ??
            $this->entityManager
            ->getQueryBuilder()
            ->select()
            ->from($entityType)
            ->build();

        $modifiedQuery = $this->addImportEntityJoin($entity, $relationName, $query);

        return $this->getEntityManager()
            ->getRepository($entityType)
            ->clone($modifiedQuery)
            ->count();
    }

    protected function afterRemove(Entity $entity, array $options = [])
    {
        if ($entity->get('fileId')) {
            $attachment = $this->getEntityManager()->getEntity('Attachment', $entity->get('fileId'));
            if ($attachment) {
                $this->getEntityManager()->removeEntity($attachment);
            }
        }

        $delete = $this->getEntityManager()
            ->getQueryBuilder()
            ->delete()
            ->from('ImportEntity')
            ->where([
                'importId' => $entity->id,
            ])
            ->build();

        $this->getEntityManager()->getQueryExecutor()->execute($delete);

        parent::afterRemove($entity, $options);
    }
}
