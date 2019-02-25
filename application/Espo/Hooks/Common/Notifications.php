<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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
use Espo\Core\Utils\Util;

class Notifications extends \Espo\Core\Hooks\Base
{
    public static $order = 10;

    protected $notifatorsHash = array();

    private $streamService;

    private $hasStreamCache = array();

    protected function getServiceFactory()
    {
        return $this->getContainer()->get('serviceFactory');
    }

    protected function getNotificatorFactory()
    {
        return $this->getContainer()->get('notificatorFactory');
    }

    protected function checkHasStream($entityType)
    {
        if (!array_key_exists($entityType, $this->hasStreamCache)) {
            $this->hasStreamCache[$entityType] = $this->getMetadata()->get("scopes.{$entityType}.stream");
        }
        return $this->hasStreamCache[$entityType];
    }

    protected function getNotificator($entityType)
    {
        if (empty($this->notifatorsHash[$entityType])) {
            $notificator = $this->getNotificatorFactory()->create($entityType);
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
            if (in_array($entityType, $this->getConfig()->get('assignmentNotificationsEntityList', []))) {
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
                if ($userId === $this->getUser()->id) {
                    continue;
                }
                $notification = $this->getEntityManager()->getEntity('Notification');
                $notification->set(array(
                    'userId' => $userId,
                    'type' => 'EntityRemoved',
                    'data' => array(
                        'entityType' => $entity->getEntityType(),
                        'entityId' => $entity->id,
                        'entityName' => $entity->get('name'),
                        'userId' => $this->getUser()->id,
                        'userName' => $this->getUser()->get('name')
                    )
                ));
                $this->getEntityManager()->saveEntity($notification);
            }
        }
    }

    public function afterRemove(Entity $entity)
    {
        $query = $this->getEntityManager()->getQuery();
        $sql = "
            DELETE FROM `notification`
            WHERE
                (related_id = ".$query->quote($entity->id)." AND related_type = ".$query->quote($entity->getEntityType()) .")
                OR
                (related_parent_id = ".$query->quote($entity->id)." AND related_parent_type = ".$query->quote($entity->getEntityType()) .")
        ";
        $this->getEntityManager()->getPDO()->query($sql);
    }

    protected function getStreamService()
    {
        if (empty($this->streamService)) {
            $this->streamService = $this->getServiceFactory()->create('Stream');
        }
        return $this->streamService;
    }
}
