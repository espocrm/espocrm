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

namespace Espo\Modules\Crm\Repositories;

use Espo\ORM\Entity;
use Espo\Core\Repositories\Event as EventRepository;
use Espo\Core\Di;
use Espo\Core\ORM\Entity as CoreEntity;

class Meeting extends EventRepository implements

    Di\ConfigAware,
    Di\UserAware
{
    use Di\ConfigSetter;
    use Di\UserSetter;

    protected function beforeSave(Entity $entity, array $options = [])
    {
        if (!$entity->isNew() && $entity->isAttributeChanged('parentId')) {
            $entity->set('accountId', null);
        }

        if ($entity->isAttributeChanged('parentId') || $entity->isAttributeChanged('parentType')) {
            $this->processParentChanged($entity);
        }

        parent::beforeSave($entity, $options);

        if (!$this->config->get('eventAssignedUserIsAttendeeDisabled')) {
            if ($entity->hasLinkMultipleField('assignedUsers')) {
                $assignedUserIdList = $entity->getLinkMultipleIdList('assignedUsers');

                foreach ($assignedUserIdList as $assignedUserId) {
                    $entity->addLinkMultipleId('users', $assignedUserId);
                    $entity->setLinkMultipleName('users', $assignedUserId,
                        $entity->getLinkMultipleName('assignedUsers', $assignedUserId)
                    );
                }
            }
            else {
                $assignedUserId = $entity->get('assignedUserId');

                if ($assignedUserId) {
                    $entity->addLinkMultipleId('users', $assignedUserId);
                    $entity->setLinkMultipleName('users', $assignedUserId, $entity->get('assignedUserName'));
                }
            }
        }

        if ($entity->isNew()) {
            $currentUserId = $this->user->getId();
            if (
                $entity->hasLinkMultipleId('users', $currentUserId) &&
                (
                    !$entity->getLinkMultipleColumn('users', 'status', $currentUserId) ||
                    $entity->getLinkMultipleColumn('users', 'status', $currentUserId) === 'None'
                )
            ) {
                $entity->setLinkMultipleColumn('users', 'status', $currentUserId, 'Accepted');
            }
        }
    }

    protected function processParentChanged(Entity $entity): void
    {
        $parent = null;

        $parentId = $entity->get('parentId');
        $parentType = $entity->get('parentType');

        if ($parentId && $parentType && $this->entityManager->hasRepository($parentType)) {
            $columnList = ['id', 'name'];

            $defs = $this->entityManager->getMetadata()->getDefs();

            if ($defs->getEntity($parentType)->hasAttribute('accountId')) {
                $columnList[] = 'accountId';

            }

            if ($parentType === 'Lead') {
                $columnList[] = 'status';
                $columnList[] = 'createdAccountId';
                $columnList[] = 'createdAccountName';
            }

            $parent = $this->entityManager
                ->getRDBRepository($parentType)
                ->select($columnList)
                ->where(['id' => $parentId])
                ->findOne();
        }

        $accountId = null;
        $accountName = null;

        if ($parent) {
            if ($parent->getEntityType() == 'Account') {
                $accountId = $parent->getId();
                $accountName = $parent->get('name');
            }
            else if (
                $parent->getEntityType() == 'Lead' &&
                $parent->get('status') == 'Converted' &&
                $parent->get('createdAccountId')
            ) {
                $accountId = $parent->get('createdAccountId');
                $accountName = $parent->get('createdAccountName');
            }

            if (
                !$accountId && $parent->get('accountId') &&
                $parent instanceof CoreEntity &&
                $parent->getRelationParam('account', 'entity') == 'Account'
            ) {
                $accountId = $parent->get('accountId');
            }

            if ($accountId) {
                $entity->set('accountId', $accountId);
                $entity->set('accountName', $accountName);
            }
        }

        if (
            $entity->get('accountId') &&
            !$entity->get('accountName')
        ) {
            $account = $this->entityManager
                ->getRDBRepository('Account')
                ->select(['id', 'name'])
                ->where(['id' => $entity->get('accountId')])
                ->findOne();

            if ($account) {
                $entity->set('accountName', $account->get('name'));
            }
        }
    }
}
