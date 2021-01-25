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

namespace Espo\Hooks\Common;

use Espo\ORM\Entity;

use Espo\Core\{
    Utils\Metadata,
    Utils\Config,
    ORM\EntityManager,
    ServiceFactory,
    NotificatorFactory,
};

use Espo\Entities\User;

class Notifications
{
    public static $order = 10;

    protected $notifatorsHash = [];

    private $streamService;

    private $hasStreamCache = [];

    protected $metadata;
    protected $config;
    protected $entityManager;
    protected $serviceFactory;
    protected $notificatorFactory;
    protected $user;

    public function __construct(
        Metadata $metadata,
        Config $config,
        EntityManager $entityManager,
        ServiceFactory $serviceFactory,
        NotificatorFactory $notificatorFactory,
        User $user
    ) {
        $this->metadata = $metadata;
        $this->config = $config;
        $this->entityManager = $entityManager;
        $this->serviceFactory = $serviceFactory;
        $this->notificatorFactory = $notificatorFactory;
        $this->user = $user;
    }

    protected function checkHasStream($entityType)
    {
        if (!array_key_exists($entityType, $this->hasStreamCache)) {
            $this->hasStreamCache[$entityType] = $this->metadata->get("scopes.{$entityType}.stream");
        }
        return $this->hasStreamCache[$entityType];
    }

    protected function getNotificator($entityType)
    {
        if (empty($this->notifatorsHash[$entityType])) {
            $notificator = $this->notificatorFactory->create($entityType);
            $this->notifatorsHash[$entityType] = $notificator;
        }
        return $this->notifatorsHash[$entityType];
    }

    public function afterSave(Entity $entity, array $options = [])
    {
        if (!empty($options['silent']) || !empty($options['noNotifications'])) {
            return;
        }

        $entityType = $entity->getEntityType();

        if (!$this->checkHasStream($entityType) || $entity->hasLinkMultipleField('assignedUsers')) {
            if (in_array($entityType, $this->config->get('assignmentNotificationsEntityList', []))) {
                $notificator = $this->getNotificator($entityType);
                $notificator->process($entity, $options);
            }
        }
    }

    public function beforeRemove(Entity $entity, array $options = [])
    {
        if (!empty($options['silent']) || !empty($options['noNotifications'])) {
            return;
        }

        $entityType = $entity->getEntityType();
        if ($this->checkHasStream($entityType)) {
            $followersData = $this->getStreamService()->getEntityFollowers($entity);
            foreach ($followersData['idList'] as $userId) {
                if ($userId === $this->user->id) {
                    continue;
                }
                $notification = $this->entityManager->getEntity('Notification');
                $notification->set(array(
                    'userId' => $userId,
                    'type' => 'EntityRemoved',
                    'data' => array(
                        'entityType' => $entity->getEntityType(),
                        'entityId' => $entity->id,
                        'entityName' => $entity->get('name'),
                        'userId' => $this->user->id,
                        'userName' => $this->user->get('name'),
                    )
                ));
                $this->entityManager->saveEntity($notification);
            }
        }
    }

    public function afterRemove(Entity $entity)
    {
        $query = $this->entityManager->getQueryBuilder()
            ->delete()
            ->from('Notification')
            ->where([
                'OR' => [
                    [
                        'relatedId' => $entity->id,
                        'relatedType' => $entity->getEntityType(),
                    ],
                    [
                        'relatedParentId' => $entity->id,
                        'relatedParentType' => $entity->getEntityType(),
                    ],
                ],
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($query);
    }

    protected function getStreamService()
    {
        if (empty($this->streamService)) {
            $this->streamService = $this->serviceFactory->create('Stream');
        }
        return $this->streamService;
    }
}
