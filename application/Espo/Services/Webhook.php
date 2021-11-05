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

namespace Espo\Services;

use Espo\ORM\Entity;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Di;

use Espo\Entities\User;

use stdClass;

class Webhook extends Record implements
    Di\WebhookManagerAware

{
    use Di\WebhookManagerSetter;

    const WEBHOOK_MAX_COUNT_PER_USER = 50;

    protected $eventTypeList = [
        'create',
        'update',
        'delete',
        'fieldUpdate',
    ];

    protected $onlyAdminAttributeList = ['userId', 'userName'];

    protected $readOnlyAttributeList = ['secretKey'];

    public function populateDefaults(Entity $entity, stdClass $data): void
    {
        parent::populateDefaults($entity, $data);

        if ($this->getUser()->isApi()) {
            $entity->set('userId', $this->getUser()->id);
        }
    }

    protected function filterInput($data)
    {
        parent::filterInput($data);

        unset($data->entityType);
        unset($data->field);
        unset($data->type);
    }

    public function filterUpdateInput(stdClass $data): void
    {
        if (!$this->getUser()->isAdmin()) {
            unset($data->event);
        }

        parent::filterUpdateInput($data);
    }

    protected function beforeCreateEntity(Entity $entity, $data)
    {
        $this->checkEntityUserIsApi($entity);
        $this->processEntityEventData($entity);

        if (!$this->getUser()->isAdmin()) {
            $this->checkMaxCount();
        }
    }

    protected function checkMaxCount()
    {
        $maxCount = $this->getConfig()->get('webhookMaxCountPerUser', self::WEBHOOK_MAX_COUNT_PER_USER);

        $count = $this->getEntityManager()
            ->getRDBRepository('Webhook')
            ->where([
                'userId' => $this->getUser()->id,
            ])
            ->count();

        if ($maxCount && $count >= $maxCount) {
            throw new Forbidden("Webhook number per user exceeded the limit.");
        }
    }

    protected function beforeUpdateEntity(Entity $entity, $data)
    {
        $this->checkEntityUserIsApi($entity);
        $this->processEntityEventData($entity);
    }

    protected function checkEntityUserIsApi(Entity $entity)
    {
        $userId = $entity->get('userId');

        if (!$userId) {
            return;
        }

        /** @var ?User $user */
        $user = $this->getEntityManager()->getEntity('User', $userId);

        if (!$user || !$user->isApi()) {
            throw new Forbidden("User must be an API User.");
        }
    }

    protected function processEntityEventData(Entity $entity)
    {
        $event = $entity->get('event');

        if (!$event) {
            throw new Forbidden("Event is empty.");
        }

        if (!$entity->isNew()) {
            if ($entity->isAttributeChanged('event')) {
                throw new Forbidden("Event can't be changed.");
            }
        }

        $arr = explode('.', $event);

        if (count($arr) !== 2 && count($arr) !== 3) {
            throw new Forbidden("Not supported event.");
        }

        $arr = explode('.', $event);
        $entityType = $arr[0];
        $type = $arr[1];

        $entity->set('entityType', $entityType);
        $entity->set('type', $type);

        $field = null;

        if (!$entityType) {
            throw new Forbidden("Entity Type is empty.");
        }

        if (!$this->getMetadata()->get(['scopes', $entityType, 'object'])) {
            throw new Forbidden("Entity type is not available for Webhooks.");
        }

        if (!$this->getEntityManager()->hasRepository($entityType)) {
            throw new Forbidden("Not existing Entity Type.");
        }

        if (!$this->getAcl()->checkScope($entityType, 'read')) {
            throw new Forbidden("Entity type is forbidden.");
        }

        if (!in_array($type, $this->eventTypeList)) {
            throw new Forbidden("Not supported event.");
        }

        if ($type === 'fieldUpdate') {
            if (count($arr) == 3) {
                $field = $arr[2];
            }

            $entity->set('field', $field);

            if (!$field) {
                throw new Forbidden("Field is empty.");
            }

            $forbiddenFieldList = $this->getAcl()->getScopeForbiddenFieldList($entityType);

            if (in_array($field, $forbiddenFieldList)) {
                throw new Forbidden("Field is forbidden.");
            }

            if (!$this->getMetadata()->get(['entityDefs', $entityType, 'fields', $field])) {
                throw new Forbidden("Field does not exist.");
            }
        } else {
            $entity->set('field', null);
        }
    }

    protected function afterCreateEntity(Entity $entity, $data)
    {
        if ($entity->get('isActive')) {
            $this->webhookManager->addEvent($entity->get('event'));
        }
    }

    protected function afterDeleteEntity(Entity $entity)
    {
        if ($entity->get('isActive')) {
            $this->webhookManager->removeEvent($entity->get('event'));
        }
    }

    protected function afterUpdateEntity(Entity $entity, $data)
    {
        if (isset($data->isActive)) {
            if ($entity->get('isActive')) {
                $this->webhookManager->addEvent($entity->get('event'));
            }
            else {
                $this->webhookManager->removeEvent($entity->get('event'));
            }
        }
    }
}
