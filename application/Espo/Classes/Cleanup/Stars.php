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

namespace Espo\Classes\Cleanup;

use Espo\Core\Cleanup\Cleanup;
use Espo\Core\Utils\Acl\UserAclManagerProvider;
use Espo\Entities\StarSubscription;
use Espo\Entities\User;
use Espo\ORM\EntityManager;
use Espo\ORM\Query\DeleteBuilder;
use Espo\Tools\Stars\StarService;

/**
 * @noinspection PhpUnused
 */
class Stars implements Cleanup
{
    public function __construct(
        private EntityManager $entityManager,
        private UserAclManagerProvider $userAclManagerProvider,
        private StarService $service
    ) {}

    public function process(): void
    {
        foreach ($this->getEntityTypeList() as $entityType) {
            $this->processEntityType($entityType);
        }
    }

    /**
     * @return string[]
     */
    private function getEntityTypeList(): array
    {
        $groups = $this->entityManager->getRDBRepositoryByClass(StarSubscription::class)
            ->group('entityType')
            ->select('entityType')
            ->find();

        $list = [];

        foreach ($groups as $group) {
            $list[] = $group->get('entityType');
        }

        return $list;
    }

    private function processEntityType(string $entityType): void
    {
        if (
            !$this->service->isEnabled($entityType) ||
            !$this->entityManager->hasRepository($entityType)
        ) {
            $deleteQuery = DeleteBuilder::create()
                ->from(StarSubscription::ENTITY_TYPE)
                ->where(['entityType' => $entityType])
                ->build();

            $this->entityManager->getQueryExecutor()->execute($deleteQuery);

            return;
        }

        $stars = $this->entityManager
            ->getRDBRepositoryByClass(StarSubscription::class)
            ->where(['entityType' => $entityType])
            ->sth()
            ->find();

        foreach ($stars as $star) {
            $entityId = $star->get('entityId');
            $userId = $star->get('userId');

            if ($userId === null || $entityId === null) {
                continue;
            }

            $entity = $this->entityManager->getEntityById($entityType, $entityId);
            $user = $this->entityManager->getRDBRepositoryByClass(User::class)->getById($userId);

            if (!$entity || !$user) {
                $this->unstar($userId, $entityType, $entityId);

                continue;
            }

            $aclManager = $this->userAclManagerProvider->get($user);

            if (!$aclManager->checkEntityRead($user, $entity)) {
                $this->unstar($userId, $entityType, $entityId);
            }
        }
    }

    private function unstar(string $userId, string $entityType, string $entityId): void
    {
        $deleteQuery = DeleteBuilder::create()
            ->from(StarSubscription::ENTITY_TYPE)
            ->where([
                'userId' => $userId,
                'entityType' => $entityType,
                'entityId' => $entityId,
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($deleteQuery);
    }
}
