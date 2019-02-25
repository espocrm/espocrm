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

namespace Espo\Core\Acl;

use \Espo\Core\Interfaces\Injectable;

use \Espo\Entities\User;
use \Espo\ORM\Entity;

class Base implements Injectable
{
    protected $dependencyList = [
        'config',
        'entityManager',
        'aclManager',
    ];

    protected $dependencies = []; // for backward compatibility

    protected $scope;

    protected $injections = [];

    protected $ownerUserIdAttribute = null;

    protected $allowDeleteCreatedThresholdPeriod = '24 hours';

    public function inject($name, $object)
    {
        $this->injections[$name] = $object;
    }

    public function __construct($scope)
    {
        $this->init();
        $this->scope = $scope;
    }

    protected function init()
    {
    }

    protected function getInjection($name)
    {
        return $this->injections[$name];
    }

    protected function addDependencyList(array $list)
    {
        foreach ($list as $item) {
            $this->addDependency($item);
        }
    }

    protected function addDependency($name)
    {
        $this->dependencyList[] = $name;
    }

    public function getDependencyList()
    {
        return array_merge($this->dependencyList, $this->dependencies);
    }

    protected function getConfig()
    {
        return $this->getInjection('config');
    }

    protected function getEntityManager()
    {
        return $this->getInjection('entityManager');
    }

    protected function getAclManager()
    {
        return $this->getInjection('aclManager');
    }

    public function checkReadOnlyTeam(User $user, $data)
    {
        if (empty($data) || !is_object($data) || !isset($data->read)) {
            return false;
        }
        return $data->read === 'team';
    }

    public function checkReadNo(User $user, $data)
    {
        if (empty($data) || !is_object($data) || !isset($data->read)) {
            return false;
        }
        return $data->read === 'no';
    }

    public function checkReadOnlyOwn(User $user, $data)
    {
        if (empty($data) || !is_object($data) || !isset($data->read)) {
            return false;
        }
        return $data->read === 'own';
    }

    public function checkEntity(User $user, Entity $entity, $data, $action)
    {
        if ($user->isAdmin()) {
            return true;
        }
        return $this->checkScope($user, $data, $action, $entity);
    }

    public function checkScope(User $user, $data, $action = null, Entity $entity = null, $entityAccessData = array())
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (is_null($data)) {
            return false;
        }
        if ($data === false) {
            return false;
        }
        if ($data === true) {
            return true;
        }
        if (is_string($data)) {
            return true;
        }

        $isOwner = null;
        if (isset($entityAccessData['isOwner'])) {
            $isOwner = $entityAccessData['isOwner'];
        }
        $inTeam = null;
        if (isset($entityAccessData['inTeam'])) {
            $inTeam = $entityAccessData['inTeam'];
        }

        if (is_null($action)) {
            return true;
        }

        if (!isset($data->$action)) {
            return false;
        }

        $value = $data->$action;

        if ($value === 'all' || $value === 'yes' || $value === true) {
            return true;
        }

        if (!$value || $value === 'no') {
            return false;
        }

        if (is_null($isOwner)) {
            if ($entity) {
                $isOwner = $this->checkIsOwner($user, $entity);
            } else {
                return true;
            }
        }

        if ($isOwner) {
            if ($value === 'own' || $value === 'team') {
                return true;
            }
        }
        if (is_null($inTeam) && $entity) {
            $inTeam = $this->checkInTeam($user, $entity);
        }

        if ($inTeam) {
            if ($value === 'team') {
                return true;
            }
        }
        return false;
    }

    public function checkIsOwner(User $user, Entity $entity)
    {
        if ($entity->hasAttribute('assignedUserId')) {
            if ($entity->has('assignedUserId')) {
                if ($user->id === $entity->get('assignedUserId')) {
                    return true;
                }
            }
        } else if ($entity->hasAttribute('createdById')) {
            if ($entity->has('createdById')) {
                if ($user->id === $entity->get('createdById')) {
                    return true;
                }
            }
        }

        if ($entity->hasLinkMultipleField('assignedUsers')) {
            if ($entity->hasLinkMultipleId('assignedUsers', $user->id)) {
                return true;
            }
        }

        return false;
    }

    public function checkInTeam(User $user, Entity $entity)
    {
        $userTeamIdList = $user->getLinkMultipleIdList('teams');

        if (!$entity->hasRelation('teams') || !$entity->hasAttribute('teamsIds')) {
            return false;
        }

        $entityTeamIdList = $entity->getLinkMultipleIdList('teams');

        if (empty($entityTeamIdList)) {
            return false;
        }

        foreach ($userTeamIdList as $id) {
            if (in_array($id, $entityTeamIdList)) {
                return true;
            }
        }
        return false;
    }

    public function checkEntityDelete(User $user, Entity $entity, $data)
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($this->checkEntity($user, $entity, $data, 'delete')) {
            return true;
        }

        if (is_object($data)) {
            if ($data->edit !== 'no' || $data->create !== 'no') {
                if (
                    $this->getConfig()->get('aclAllowDeleteCreated')
                    &&
                    $entity->has('createdById') && $entity->get('createdById') == $user->id
                ) {
                    $isDeletedAllowed = false;
                    if (!$entity->has('assignedUserId')) {
                        $isDeletedAllowed = true;
                    } else {
                        if (!$entity->get('assignedUserId')) {
                            $isDeletedAllowed = true;
                        } else if ($entity->get('assignedUserId') == $entity->get('createdById')) {
                            $isDeletedAllowed = true;
                        }
                    }

                    if ($isDeletedAllowed) {
                        $createdAt = $entity->get('createdAt');
                        if ($createdAt) {
                            $deleteThresholdPeriod = $this->getConfig()->get('aclAllowDeleteCreatedThresholdPeriod', $this->allowDeleteCreatedThresholdPeriod);
                            if (\Espo\Core\Utils\DateTime::isAfterThreshold($createdAt, $deleteThresholdPeriod)) {
                                return false;
                            }
                        }
                        return true;
                    }
                }
            }
        }

        return false;
    }

    public function getOwnerUserIdAttribute(Entity $entity)
    {
        if ($this->ownerUserIdAttribute) {
            return $this->ownerUserIdAttribute;
        }

        if ($entity->hasLinkMultipleField('assignedUsers')) {
            return 'assignedUsersIds';
        }

        if ($entity->hasAttribute('assignedUserId')) {
            return 'assignedUserId';
        }

        if ($entity->hasAttribute('createdById')) {
            return 'createdById';
        }
    }
}
