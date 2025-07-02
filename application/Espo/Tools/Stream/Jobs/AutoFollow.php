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

namespace Espo\Tools\Stream\Jobs;

use Espo\Core\Job\Job;
use Espo\Core\Job\Job\Data;
use Espo\Core\AclManager;
use Espo\Core\Acl\Exceptions\NotImplemented as AclNotImplemented;
use Espo\Core\Utils\Util;
use Espo\Entities\Note;
use Espo\ORM\EntityManager;
use Espo\Tools\Notification\HookProcessor\Params;
use Espo\Tools\Stream\Service as Service;
use Espo\Tools\Notification\Service as NotificationService;
use Espo\Entities\User;

/**
 * Handles auto-follow.
 */
class AutoFollow implements Job
{
    public function __construct(
        private Service $service,
        private NotificationService $notificationService,
        private AclManager $aclManager,
        private EntityManager $entityManager,
    ) {}

    public function run(Data $data): void
    {
        /** @var string[] $userIdList */
        $userIdList = $data->get('userIdList') ?? [];
        $entityType = $data->get('entityType');
        $entityId = $data->get('entityId');

        if (!$entityId || !$entityType) {
            return;
        }

        $entity = $this->entityManager->getEntityById($entityType, $entityId);

        if (!$entity) {
            return;
        }

        foreach ($userIdList as $i => $userId) {
            $user = $this->entityManager->getRDBRepositoryByClass(User::class)->getById($userId);

            if (!$user) {
                unset($userIdList[$i]);

                continue;
            }

            try {
                $hasAccess = $this->aclManager->checkEntityStream($user, $entity);
            } catch (AclNotImplemented) {
                $hasAccess = false;
            }

            if (!$hasAccess) {
                unset($userIdList[$i]);
            }
        }

        $userIdList = array_values($userIdList);

        foreach ($userIdList as $i => $userId) {
            if ($this->service->checkIsFollowed($entity, $userId)) {
                unset($userIdList[$i]);
            }
        }

        $userIdList = array_values($userIdList);

        if (!count($userIdList)) {
            return;
        }

        $this->service->followEntityMass($entity, $userIdList);

        $notes = $this->entityManager
            ->getRDBRepositoryByClass(Note::class)
            ->where([
                'parentType' => $entityType,
                'parentId' => $entityId,
            ])
            ->order('number')
            ->find();

        // Group all notifications.
        $params = new Params(Util::generateId());

        foreach ($notes as $note) {
            $this->notificationService->notifyAboutNote($userIdList, $note, $params);
        }
    }
}
