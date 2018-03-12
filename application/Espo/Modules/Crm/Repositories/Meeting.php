<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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
use Espo\Core\Utils\Util;

class Meeting extends \Espo\Core\Repositories\Event
{
    protected function beforeSave(Entity $entity, array $options = array())
    {
        if (!$entity->isNew() && $entity->isAttributeChanged('parentId')) {
            $entity->set('accountId', null);
        }

        $parentId = $entity->get('parentId');
        $parentType = $entity->get('parentType');
        if ($parentId && $parentType) {
            $parent = $this->getEntityManager()->getEntity($parentType, $parentId);
            if ($parent) {
                $accountId = null;
                if ($parent->getEntityType() == 'Account') {
                    $accountId = $parent->id;
                } else if ($parent->getEntityType() == 'Lead') {
                    if ($parent->get('status') == 'Converted') {
                        if ($parent->get('createdAccountId')) {
                            $accountId = $parent->get('createdAccountId');
                        }
                    }
                }
                if (!$accountId && $parent->get('accountId') && $parent->getRelationParam('account', 'entity') == 'Account') {
                    $accountId = $parent->get('accountId');
                }
                if ($accountId) {
                    $entity->set('accountId', $accountId);
                }
            }
        }

        if (!$entity->isNew()) {
            if ($entity->isAttributeChanged('dateStart') && $entity->isAttributeChanged('dateStart') && !$entity->isAttributeChanged('dateEnd')) {
                $dateEndPrevious = $entity->getFetched('dateEnd');
                $dateStartPrevious = $entity->getFetched('dateStart');
                if ($dateStartPrevious && $dateEndPrevious) {
                    $dtStart = new \DateTime($dateStartPrevious);
                    $dtEnd = new \DateTime($dateEndPrevious);
                    $dt = new \DateTime($entity->get('dateStart'));

                    if ($dtStart && $dtEnd && $dt) {
                        $duration = ($dtEnd->getTimestamp() - $dtStart->getTimestamp());
                        $dt->modify('+' . $duration . ' seconds');
                        $dateEnd = $dt->format('Y-m-d H:i:s');
                        $entity->set('dateEnd', $dateEnd);
                    }
                }
            }
        }

        parent::beforeSave($entity, $options);

        $assignedUserId = $entity->get('assignedUserId');
        if ($assignedUserId) {
            if ($entity->has('usersIds')) {
                $usersIds = $entity->get('usersIds');
                if (!is_array($usersIds)) {
                    $usersIds = [];
                }
                if (!in_array($assignedUserId, $usersIds)) {
                    $usersIds[] = $assignedUserId;
                    $entity->set('usersIds', $usersIds);
                    $hash = $entity->get('usersNames');
                    if ($hash instanceof \StdClass) {
                        $hash->$assignedUserId = $entity->get('assignedUserName');
                        $entity->set('usersNames', $hash);
                    }
                }
            } else {
                $entity->addLinkMultipleId('users', $assignedUserId);
            }
            if ($entity->isNew()) {
                $currentUserId = $this->getEntityManager()->getUser()->id;
                if (isset($usersIds) && in_array($currentUserId, $usersIds)) {
                    $usersColumns = $entity->get('usersColumns');
                    if (empty($usersColumns)) {
                        $usersColumns = new \StdClass();
                    }
                    if ($usersColumns instanceof \StdClass) {
                        if (empty($usersColumns->$currentUserId) || !($usersColumns->$currentUserId instanceof \StdClass)) {
                            $usersColumns->$currentUserId = new \StdClass();
                        }
                        if (empty($usersColumns->$currentUserId->status)) {
                            $usersColumns->$currentUserId->status = 'Accepted';
                        }
                    }
                }
            }
        }
    }
}
