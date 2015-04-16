<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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
 ************************************************************************/

namespace Espo\Core\Acl;

use \Espo\Core\Interfaces\Injectable;

use \Espo\Entities\User;
use \Espo\ORM\Entity;

class Base implements Injectable
{
    protected $dependencies = array(
        'config',
        'entityManager',
        'aclManager'
    );

    protected $injections = array();

    public function inject($name, $object)
    {
        $this->injections[$name] = $object;
    }

    public function __construct()
    {
        $this->init();
    }

    protected function init()
    {
    }

    protected function getInjection($name)
    {
        return $this->injections[$name];
    }

    protected function addDependency($name)
    {
        $this->dependencies[] = $name;
    }

    public function getDependencyList()
    {
        return $this->dependencies;
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

    public function checkReadOnlyTeam(User $user, $scope, $data)
    {
        if (empty($data) || !is_array($data) || !isset($data['read'])) {
            return false;
        }
        return $data['read'] === 'team';
    }

    public function checkReadOnlyOwn(User $user, $scope, $data)
    {
        if (empty($data) || !is_array($data) || !isset($data['read'])) {
            return false;
        }
        return $data['read'] === 'own';
    }

    public function checkEntity(User $user, Entity $entity, $data, $action)
    {
        return $this->checkScope($user, $data, $entity->getEntityType(), $action, null, null, $entity);
    }

    public function checkScope(User $user, $data, $scope, $action = null, $isOwner = null, $inTeam = null, Entity $entity = null)
    {
        if (is_null($data)) {
            return true;
        }
        if ($data === false) {
            return false;
        }
        if ($data === true) {
            return true;
        }

        if (!is_null($action)) {
            if (array_key_exists($action, $data)) {
                $value = $data[$action];

                if ($value === 'all' || $value === true) {
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
        }
        return true;
    }

    public function checkIsOwner(User $user, Entity $entity)
    {
        if ($entity->has('assignedUserId')) {
            if ($user->id === $entity->get('assignedUserId')) {
                return true;
            }
        }
        if ($entity->has('createdById')) {
            if ($user->id === $entity->get('createdById')) {
                return true;
            }
        }
        return false;
    }

    public function checkInTeam(User $user, Entity $entity)
    {
        $userTeamIds = $user->get('teamsIds');

        if (!$entity->hasRelation('teams') || !$entity->hasField('teamsIds')) {
            return false;
        }

        if (!$entity->has('teamsIds')) {
            $entity->loadLinkMultipleField('teams');
        }

        $teamIds = $entity->get('teamsIds');

        if (empty($teamIds)) {
            return false;
        }

        foreach ($userTeamIds as $id) {
            if (in_array($id, $teamIds)) {
                return true;
            }
        }
        return false;
    }
}

