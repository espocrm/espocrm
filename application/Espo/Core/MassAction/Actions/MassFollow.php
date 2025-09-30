<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

namespace Espo\Core\MassAction\Actions;

use Espo\Tools\Stream\Service as StreamService;
use Espo\Core\Acl;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\MassAction\Data;
use Espo\Core\MassAction\MassAction;
use Espo\Core\MassAction\Params;
use Espo\Core\MassAction\QueryBuilder;
use Espo\Core\MassAction\Result;
use Espo\Core\ORM\EntityManager;
use Espo\Entities\User;

class MassFollow implements MassAction
{
    private QueryBuilder $queryBuilder;
    private Acl $acl;
    private StreamService $streamService;
    private EntityManager $entityManager;
    private User $user;

    public function __construct(
        QueryBuilder $queryBuilder,
        Acl $acl,
        StreamService $streamService,
        EntityManager $entityManager,
        User $user
    ) {
        $this->queryBuilder = $queryBuilder;
        $this->acl = $acl;
        $this->streamService = $streamService;
        $this->entityManager = $entityManager;
        $this->user = $user;
    }

    public function process(Params $params, Data $data): Result
    {
        $entityType = $params->getEntityType();

        $passedUserId = $data->get('userId');

        if ($passedUserId && !$this->user->isAdmin()) {
            throw new Forbidden();
        }

        $userId = $passedUserId ?? $this->user->getId();

        if (!$this->acl->check($entityType, Acl\Table::ACTION_STREAM)) {
            throw new Forbidden("No stream access for '{$entityType}'.");
        }

        $query = $this->queryBuilder->build($params);

        $collection = $this->entityManager
            ->getRDBRepository($entityType)
            ->clone($query)
            ->sth()
            ->find();

        $ids = [];

        $count = 0;

        foreach ($collection as $entity) {
            if (
                !$this->acl->checkEntityStream($entity) ||
                !$this->acl->checkEntityRead($entity)
            ) {
                continue;
            }

            $followResult = $this->streamService->followEntity($entity, $userId);

            if (!$followResult) {
                continue;
            }

            /** @var string $id */
            $id = $entity->getId();

            $ids[] = $id;
            $count++;
        }

        return new Result($count, $ids);
    }
}
