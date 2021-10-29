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

namespace Espo\Tools\Notification;

use Espo\Core\Notification\AssignmentNotificatorFactory;
use Espo\Core\Notification\AssignmentNotificator;
use Espo\Core\Notification\AssignmentNotificator\Params as AssignmentNotificatorParams;

use Espo\Core\Utils\Metadata;
use Espo\Core\Utils\Config;

use Espo\Services\Stream as StreamService;

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
    private $notifatorsHash = [];

    private $hasStreamCache = [];

    private $metadata;

    private $config;

    private $entityManager;

    private $streamService;

    private $notificatorFactory;

    private $user;

    public function __construct(
        Metadata $metadata,
        Config $config,
        EntityManager $entityManager,
        StreamService $streamService,
        AssignmentNotificatorFactory $notificatorFactory,
        User $user
    ) {
        $this->metadata = $metadata;
        $this->config = $config;
        $this->entityManager = $entityManager;
        $this->streamService = $streamService;
        $this->notificatorFactory = $notificatorFactory;
        $this->user = $user;
    }

    public function afterSave(Entity $entity, array $options): void
    {
        $entityType = $entity->getEntityType();

        assert($entity instanceof CoreEntity);

        /**
         * No need to process assignment notifications for entity types that have Stream enabled.
         * Users are notified via Stream notifications.
         */
        if ($this->checkHasStream($entityType) && !$entity->hasLinkMultipleField('assignedUsers')) {
            return;
        }

        $assignmentNotificationsEntityList = $this->config->get('assignmentNotificationsEntityList') ?? [];

        if (!in_array($entityType, $assignmentNotificationsEntityList)) {
            return;
        }

        $notificator = $this->getNotificator($entityType);

        if (!$notificator instanceof AssignmentNotificator) {
            // For backward compatiblity.
            $notificator->process($entity, $options);

            return;
        }

        $params = AssignmentNotificatorParams::create()->withRawOptions($options);

        $notificator->process($entity, $params);
    }

    public function beforeRemove(Entity $entity): void
    {
        $entityType = $entity->getEntityType();

        if (!$this->checkHasStream($entityType)) {
            return;
        }

        $followersData = $this->streamService->getEntityFollowers($entity);

        $userIdList = $followersData['idList'];

        foreach ($userIdList as $userId) {
            if ($userId === $this->user->getId()) {
                continue;
            }

            $this->entityManager->createEntity(Notification::ENTITY_TYPE, [
                'userId' => $userId,
                'type' => Notification::TYPE_ENTITY_REMOVED,
                'data' => [
                    'entityType' => $entity->getEntityType(),
                    'entityId' => $entity->getId(),
                    'entityName' => $entity->get('name'),
                    'userId' => $this->user->getId(),
                    'userName' => $this->user->get('name'),
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
     * @return AssignmentNotificator
     */
    private function getNotificator(string $entityType): object
    {
        if (empty($this->notifatorsHash[$entityType])) {
            $notificator = $this->notificatorFactory->create($entityType);

            $this->notifatorsHash[$entityType] = $notificator;
        }

        return $this->notifatorsHash[$entityType];
    }
}
