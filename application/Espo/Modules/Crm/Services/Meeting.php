<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Modules\Crm\Services;

use Espo\Entities\User;
use Espo\Modules\Crm\Entities\Meeting as MeetingEntity;
use Espo\ORM\Entity;
use Espo\Services\Record;
use Espo\Core\ORM\Entity as CoreEntity;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Exceptions\BadRequest;

use Espo\Core\Di;

/**
 * @extends Record<CoreEntity>
 */
class Meeting extends Record implements
    Di\HookManagerAware
{
    use Di\HookManagerSetter;

    /**
     * @var string[]
     */
    protected $validateRequiredSkipFieldList = [
        'dateEnd',
    ];

    /**
     * @var string[]
     */
    protected $exportSkipFieldList = ['duration'];

    /**
     * @var string[]
     */
    protected $duplicateIgnoreAttributeList = [
        'usersColumns',
        'contactsColumns',
        'leadsColumns',
    ];

    public function checkAssignment(Entity $entity): bool
    {
        $result = parent::checkAssignment($entity);

        if (!$result) {
            return false;
        }

        $userIdList = $entity->get('usersIds');

        if (!is_array($userIdList)) {
            $userIdList = [];
        }

        $newIdList = [];

        if (!$entity->isNew()) {
            $existingIdList = [];

            $usersCollection = $this->entityManager
                ->getRDBRepository($entity->getEntityType())
                ->getRelation($entity, 'users')
                ->select('id')
                ->find();

            foreach ($usersCollection as $user) {
                $existingIdList[] = $user->getId();
            }

            foreach ($userIdList as $id) {
                if (!in_array($id, $existingIdList)) {
                    $newIdList[] = $id;
                }
            }
        }
        else {
            $newIdList = $userIdList;
        }

        foreach ($newIdList as $userId) {
            if (!$this->acl->checkAssignmentPermission($userId)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string[] $ids
     */
    public function massSetHeld(array $ids): bool
    {
        assert($this->entityType !== null);

        foreach ($ids as $id) {
            $entity = $this->entityManager->getEntity($this->entityType, $id);

            if ($entity && $this->acl->checkEntityEdit($entity)) {
                $entity->set('status', MeetingEntity::STATUS_HELD);

                $this->entityManager->saveEntity($entity);
            }
        }

        return true;
    }

    /**
     * @param string[] $ids
     */
    public function massSetNotHeld(array $ids): bool
    {
        assert($this->entityType !== null);

        foreach ($ids as $id) {
            $entity = $this->entityManager->getEntity($this->entityType, $id);

            if ($entity && $this->acl->checkEntityEdit($entity)) {
                $entity->set('status', MeetingEntity::STATUS_NOT_HELD);

                $this->entityManager->saveEntity($entity);
            }
        }

        return true;
    }

    public function setAcceptanceStatus(string $id, string $status, ?string $userId = null): bool
    {
        $userId = $userId ?? $this->user->getId();

        assert(is_string($this->entityType));

        $statusList = $this->metadata
                ->get(['entityDefs', $this->entityType, 'fields', 'acceptanceStatus', 'options'], []);

        if (!in_array($status, $statusList)) {
            throw new BadRequest();
        }

        $entity = $this->entityManager->getEntity($this->entityType, $id);

        if (!$entity) {
            throw new NotFound();
        }

        assert($entity instanceof CoreEntity);

        if (!$entity->hasLinkMultipleId('users', $userId)) {
            return false;
        }

        $this->entityManager
            ->getRDBRepository($this->entityType)
            ->updateRelation(
                $entity,
                'users',
                $userId,
                (object) ['status' => $status]
            );

        $actionData = [
            'eventName' => $entity->get('name'),
            'eventType' => $entity->getEntityType(),
            'eventId' => $entity->getId(),
            'dateStart' => $entity->get('dateStart'),
            'status' => $status,
            'link' => 'users',
            'inviteeType' => User::ENTITY_TYPE,
            'inviteeId' => $userId,
        ];

        $this->hookManager->process($this->entityType, 'afterConfirmation', $entity, [], $actionData);

        return true;
    }
}
