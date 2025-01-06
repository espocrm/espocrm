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

namespace Espo\Tools\CategoryTree;

use Espo\Core\Exceptions\Error;
use Espo\ORM\Entity;
use Espo\Core\Repositories\CategoryTree;
use Espo\ORM\EntityManager;

/**
 * Rebuild category tree paths.
 */
class RebuildPaths
{
    private EntityManager $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @throws Error
     */
    public function run(string $entityType): void
    {
        if (
            !$this->entityManager->hasRepository($entityType) ||
            !$this->entityManager->getRepository($entityType) instanceof CategoryTree
        ) {
            throw new Error("Bad entity type.");
        }

        $this->clearTable($entityType);

        $this->processBranch($entityType, null);
    }

    private function clearTable(string $entityType): void
    {
        $query = $this->entityManager
            ->getQueryBuilder()
            ->delete()
            ->from($entityType . 'Path')
            ->build();

        $this->entityManager->getQueryExecutor()->execute($query);
    }

    private function processBranch(string $entityType, ?string $parentId): void
    {
        $collection = $this->entityManager
            ->getRDBRepository($entityType)
            ->sth()
            ->where(['parentId' => $parentId])
            ->find();

        foreach ($collection as $entity) {
            $this->processEntity($entity);
        }
    }

    private function processEntity(Entity $entity): void
    {
        $parentId = $entity->get('parentId');
        $pathEntityType = $entity->getEntityType() . 'Path';

        if ($parentId) {
            $subSelect1 = $this->entityManager
                ->getQueryBuilder()
                ->select()
                ->from($pathEntityType)
                ->select(['ascendorId', "'" . $entity->getId() . "'"])
                ->where([
                    'descendorId' => $parentId,
                ])
                ->build();

            $insert = $this->entityManager
                ->getQueryBuilder()
                ->insert()
                ->into($pathEntityType)
                ->columns(['ascendorId', 'descendorId'])
                ->valuesQuery($subSelect1)
                ->build();

            $this->entityManager->getQueryExecutor()->execute($insert);
        }

        $insert = $this->entityManager
            ->getQueryBuilder()
            ->insert()
            ->into($pathEntityType)
            ->columns(['ascendorId', 'descendorId'])
            ->values([
                'ascendorId' => $entity->getId(),
                'descendorId' => $entity->getId(),
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($insert);

        $this->processBranch($entity->getEntityType(), $entity->getId());
    }
}
