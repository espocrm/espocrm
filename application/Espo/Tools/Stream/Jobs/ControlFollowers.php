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

namespace Espo\Tools\Stream\Jobs;

use Espo\Core\Job\Job;
use Espo\Core\Job\Job\Data;

use Espo\Core\AclManager;
use Espo\Core\Acl\Exceptions\NotImplemented as AclNotImplemented;

use Espo\ORM\EntityManager;

use Espo\Services\Stream as Service;
use Espo\Entities\User;

/**
 * Unfollows users that don't have access.
 */
class ControlFollowers implements Job
{
    private $service;

    private $aclManager;

    private $entityManager;

    public function __construct(
        Service $service,
        AclManager $aclManager,
        EntityManager $entityManager
    ) {
        $this->service = $service;
        $this->aclManager = $aclManager;
        $this->entityManager = $entityManager;
    }

    public function run(Data $data): void
    {
        $entityType = $data->get('entityType');
        $entityId = $data->get('entityId');

        if (!$entityId || !$entityType) {
            return;
        }

        $entity = $this->entityManager->getEntity($entityType, $entityId);

        if (!$entity) {
            return;
        }

        $idList = $this->service->getEntityFolowerIdList($entity);

        /** @var iterable<User> $userList */
        $userList = $this->entityManager
            ->getRDBRepository('User')
            ->where([
                'id' => $idList,
            ])
            ->find();

        foreach ($userList as $user) {
            if (!$user->get('isActive')) {
                $this->service->unfollowEntity($entity, $user->getId());

                continue;
            }

            if ($user->isPortal()) {
                continue;
            }

            try {
                $hasAccess = $this->aclManager->checkEntityStream($user, $entity);
            }
            catch (AclNotImplemented $e) {
                $hasAccess = false;
            }

            if ($hasAccess) {
                continue;
            }

            $this->service->unfollowEntity($entity, $user->getId());
        }
    }
}
