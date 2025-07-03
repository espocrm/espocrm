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

namespace Espo\Tools\Notification;

use Espo\Core\Name\Field;
use Espo\Core\Notification\AssignmentNotificatorFactory;
use Espo\Core\Notification\AssignmentNotificator;
use Espo\Core\Notification\AssignmentNotificator\Params as AssignmentNotificatorParams;
use Espo\Core\ORM\Repository\Option\SaveContext;
use Espo\Core\Utils\Metadata;
use Espo\Core\Utils\Config;
use Espo\Tools\Stream\Service as StreamService;
use Espo\ORM\EntityManager;
use Espo\ORM\Entity;
use Espo\Entities\User;
use Espo\Entities\Notification;
use Espo\Core\ORM\Entity as CoreEntity;

/**
 * Handles operations with entities.
 */
class HookProcessor
{
    /** @var array<string, AssignmentNotificator<Entity>> */
    private $notificatorsHash = [];
    /** @var array<string, bool> */
    private $hasStreamCache = [];
    /** @var array<string, string> */
    private $userNameHash = [];

    public function __construct(
        private Metadata $metadata,
        private Config $config,
        private EntityManager $entityManager,
        private StreamService $streamService,
        private AssignmentNotificatorFactory $notificatorFactory,
        private User $user
    ) {}

    /**
     * @param array<string, mixed> $options
     */
    public function afterSave(Entity $entity, array $options): void
    {
        $entityType = $entity->getEntityType();

        if (!$entity instanceof CoreEntity) {
            return;
        }

        $hasStream = $this->checkHasStream($entityType);
        $force = $this->forceAssignmentNotificator($entityType);

        /**
         * No need to process assignment notifications for entity types that have Stream enabled.
         * Users are notified via Stream notifications.
         */
        if ($hasStream && !$force) {
            return;
        }

        $assignmentNotificationsEntityList = $this->config->get('assignmentNotificationsEntityList') ?? [];

        if (
            (!$force || !$hasStream) &&
            !in_array($entityType, $assignmentNotificationsEntityList)
        ) {
            return;
        }

        $notificator = $this->getNotificator($entityType);

        $params = AssignmentNotificatorParams::create()->withRawOptions($options);

        $saveContext = SaveContext::obtainFromRawOptions($options);

        if ($saveContext) {
            $params = $params->withActionId($saveContext->getActionId());
        }

        $notificator->process($entity, $params);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function beforeRemove(Entity $entity, array $options): void
    {
        $entityType = $entity->getEntityType();

        if (!$this->checkHasStream($entityType)) {
            return;
        }

        $followersData = $this->streamService->getEntityFollowers($entity);

        $userIdList = $followersData['idList'];

        $removedById = $options['modifiedById'] ?? $this->user->getId();
        $removedByName = $this->getUserNameById($removedById);

        foreach ($userIdList as $userId) {
            if ($userId === $removedById) {
                continue;
            }

            $this->entityManager->createEntity(Notification::ENTITY_TYPE, [
                'userId' => $userId,
                'type' => Notification::TYPE_ENTITY_REMOVED,
                'data' => [
                    'entityType' => $entity->getEntityType(),
                    'entityId' => $entity->getId(),
                    'entityName' => $entity->get(Field::NAME),
                    'userId' => $removedById,
                    'userName' => $removedByName,
                ],
            ]);
        }
    }

    public function afterRemove(Entity $entity): void
    {
        $query = $this->entityManager
            ->getQueryBuilder()
            ->delete()
            ->from(Notification::ENTITY_TYPE)
            ->where([
                'OR' => [
                    [
                        'relatedId' => $entity->getId(),
                        'relatedType' => $entity->getEntityType(),
                    ],
                    [
                        'relatedParentId' => $entity->getId(),
                        'relatedParentType' => $entity->getEntityType(),
                    ],
                ],
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($query);
    }

    private function checkHasStream(string $entityType): bool
    {
        if (!array_key_exists($entityType, $this->hasStreamCache)) {
            $this->hasStreamCache[$entityType] =
                (bool) $this->metadata->get(['scopes', $entityType, 'stream']);
        }

        return $this->hasStreamCache[$entityType];
    }

    /**
     * @return AssignmentNotificator<Entity>
     */
    private function getNotificator(string $entityType): AssignmentNotificator
    {
        if (empty($this->notificatorsHash[$entityType])) {
            $notificator = $this->notificatorFactory->create($entityType);

            $this->notificatorsHash[$entityType] = $notificator;
        }

        return $this->notificatorsHash[$entityType];
    }

    private function getUserNameById(string $id): string
    {
        if ($id === $this->user->getId()) {
            return $this->user->get(Field::NAME);
        }

        if (!array_key_exists($id, $this->userNameHash)) {
            /** @var ?User $user */
            $user = $this->entityManager->getEntityById(User::ENTITY_TYPE, $id);

            if ($user) {
                $this->userNameHash[$id] = $user->getName() ?? $id;
            }
        }

        return $this->userNameHash[$id];
    }

    private function forceAssignmentNotificator(string $entityType): bool
    {
        return (bool) $this->metadata->get(['notificationDefs', $entityType, 'forceAssignmentNotificator']);
    }
}
