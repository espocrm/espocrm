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

namespace Espo\Repositories;

use Espo\Entities\Import as ImportEntity;
use Espo\Entities\ImportEntity as ImportEntityEntity;
use Espo\ORM\Collection;
use Espo\ORM\Entity;
use Espo\ORM\Query\Select as Query;
use Espo\ORM\Query\SelectBuilder;
use Espo\Entities\Attachment as AttachmentEntity;
use Espo\Core\Repositories\Database;
use Espo\Entities\ImportError;

use LogicException;

/**
 * @extends Database<ImportEntity>
 */
class Import extends Database
{
    /**
     * @return Collection<Entity>
     */
    public function findResultRecords(ImportEntity $entity, string $relationName, Query $query): Collection
    {
        $entityType = $entity->getTargetEntityType();

        if (!$entityType) {
            throw new LogicException();
        }

        $modifiedQuery = $this->addImportEntityJoin($entity, $relationName, $query);

        return $this->entityManager
            ->getRDBRepository($entityType)
            ->clone($modifiedQuery)
            ->find();
    }

    protected function addImportEntityJoin(ImportEntity $entity, string $link, Query $query): Query
    {
        $entityType = $entity->getTargetEntityType();

        if (!$entityType) {
            throw new LogicException();
        }

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

        $builder = SelectBuilder::create()->clone($query);

        $builder->join(
            'ImportEntity',
            'importEntity',
            [
                'importEntity.importId' => $entity->getId(),
                'importEntity.entityType' => $entityType,
                'importEntity.entityId:' => 'id',
                'importEntity.' . $param => true,
            ]
        );

        return $builder->build();
    }

    public function countResultRecords(ImportEntity $entity, string $relationName, ?Query $query = null): int
    {
        $entityType = $entity->getTargetEntityType();

        if (!$entityType) {
            throw new LogicException();
        }

        $query = $query ??
            $this->entityManager
            ->getQueryBuilder()
            ->select()
            ->from($entityType)
            ->build();

        $modifiedQuery = $this->addImportEntityJoin($entity, $relationName, $query);

        return $this->entityManager
            ->getRDBRepository($entityType)
            ->clone($modifiedQuery)
            ->count();
    }

    /**
     * @param ImportEntity $entity
     */
    protected function afterRemove(Entity $entity, array $options = [])
    {
        $fileId = $entity->getFileId();

        if ($fileId) {
            $attachment = $this->entityManager->getEntityById(AttachmentEntity::ENTITY_TYPE, $fileId);

            if ($attachment) {
                $this->entityManager->removeEntity($attachment);
            }
        }

        $delete1 = $this->entityManager
            ->getQueryBuilder()
            ->delete()
            ->from(ImportEntityEntity::ENTITY_TYPE)
            ->where([
                'importId' => $entity->getId(),
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($delete1);

        $delete2 = $this->entityManager
            ->getQueryBuilder()
            ->delete()
            ->from(ImportError::ENTITY_TYPE)
            ->where([
                'importId' => $entity->getId(),
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($delete2);

        parent::afterRemove($entity, $options);
    }
}
