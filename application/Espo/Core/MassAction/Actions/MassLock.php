<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

use Espo\Core\Acl;
use Espo\Core\Exceptions\ErrorSilent;
use Espo\Core\Exceptions\ForbiddenSilent;
use Espo\Core\MassAction\Data;
use Espo\Core\MassAction\MassAction;
use Espo\Core\MassAction\Params;
use Espo\Core\MassAction\QueryBuilder;
use Espo\Core\MassAction\Result;
use Espo\ORM\EntityManager;
use Espo\Tools\Lock\LockService;

/**
 * @noinspection PhpUnused
 */
class MassLock implements MassAction
{
    public function __construct(
        private QueryBuilder $queryBuilder,
        private LockService $lockService,
        private EntityManager $entityManager,
        private Acl $acl,
    ) {}

    public function process(Params $params, Data $data): Result
    {
        $entityType = $params->getEntityType();

        if (!$this->lockService->isEnabled($entityType)) {
            throw new ErrorSilent("Not enabled.");
        }

        if (!$this->lockService->isAllowed($entityType)) {
            throw new ForbiddenSilent("Not allowed.");
        }

        if ($this->acl->getPermissionLevel(Acl\Permission::MASS_UPDATE) !== Acl\Table::LEVEL_YES) {
            throw new ForbiddenSilent("No mass update permission.");
        }

        $query = $this->queryBuilder->build($params);

        $collection = $this->entityManager
            ->getRDBRepository($entityType)
            ->clone($query)
            ->sth()
            ->find();

        return $this->lockService->massLock($collection);
    }
}
