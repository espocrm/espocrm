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

namespace Espo\Services;

use \Espo\Core\Exceptions\Forbidden;
use \Espo\Core\Exceptions\NotFound;
use \Espo\Core\Exceptions\BadRequest;

use Espo\ORM\Entity;

class Note extends Record
{
    public function getEntity($id = null)
    {
        $entity = parent::getEntity($id);
        if (!empty($id)) {
            $entity->loadAttachments();
        }
        return $entity;
    }

    public function createEntity($data)
    {
        if (!empty($data->parentType) && !empty($data->parentId)) {
            $entity = $this->getEntityManager()->getEntity($data->parentType, $data->parentId);
            if ($entity) {
                if (!$this->getAcl()->check($entity, 'read')) {
                    throw new Forbidden();
                }
            }
        }

        return parent::createEntity($data);
    }

    protected function afterCreateEntity(Entity $entity, $data)
    {
        parent::afterCreateEntity($entity, $data);

        if ($entity->get('type') === 'Post' && $entity->get('parentType') && $entity->get('parentType')) {
            $preferences = $this->getEntityManager()->getEntity('Preferences', $this->getUser()->id);
            if ($preferences && $preferences->get('followEntityOnStreamPost')) {
                if ($this->getMetadata()->get(['scopes', $entity->get('parentType'), 'stream'])) {
                    $parent = $this->getEntityManager()->getEntity($entity->get('parentType'), $entity->get('parentId'));
                    if ($parent) {
                        $this->getServiceFactory()->create('Stream')->followEntity($parent, $this->getUser()->id);
                    }
                }
            }
        }
    }

    protected function beforeCreateEntity(Entity $entity, $data)
    {
        parent::beforeCreateEntity($entity, $data);
        $targetType = $entity->get('targetType');

        $entity->clear('isGlobal');

        switch ($targetType) {
            case 'all':
                $entity->clear('usersIds');
                $entity->clear('teamsIds');
                $entity->clear('portalsIds');
                $entity->set('isGlobal', true);
                break;
            case 'self':
                $entity->clear('usersIds');
                $entity->clear('teamsIds');
                $entity->clear('portalsIds');
                $entity->set('usersIds', [$this->getUser()->id]);
                $entity->set('isForSelf', true);
                break;
            case 'users':
                $entity->clear('teamsIds');
                $entity->clear('portalsIds');
                break;
            case 'teams':
                $entity->clear('usersIds');
                $entity->clear('portalsIds');
                break;
            case 'portals':
                $entity->clear('usersIds');
                $entity->clear('teamsIds');
                break;
        }
    }

    protected function beforeUpdateEntity(Entity $entity, $data)
    {
        parent::beforeUpdateEntity($entity, $data);

        $entity->clear('targetType');
        $entity->clear('usersIds');
        $entity->clear('teamsIds');
        $entity->clear('portalsIds');
        $entity->clear('isGlobal');
    }

    public function checkAssignment(Entity $entity)
    {
        if ($entity->isNew()) {
            $targetType = $entity->get('targetType');

            if ($targetType) {
                $assignmentPermission = $this->getAcl()->get('assignmentPermission');
                if ($assignmentPermission === false || $assignmentPermission === 'no') {
                    if ($targetType !== 'self') {
                        throw new Forbidden('Not permitted to post to anybody except self.');
                    }
                }

                if ($targetType === 'teams') {
                    $teamIdList = $entity->get('teamsIds');
                    if (empty($teamIdList) || !is_array($teamIdList)) {
                        throw new BadRequest();
                    }
                }
                if ($targetType === 'users') {
                    $userIdList = $entity->get('usersIds');
                    if (empty($userIdList) || !is_array($userIdList)) {
                        throw new BadRequest();
                    }
                }
                if ($targetType === 'portals') {
                    $portalIdList = $entity->get('portalsIds');
                    if (empty($portalIdList) || !is_array($portalIdList)) {
                        throw new BadRequest();
                    }
                    if ($this->getAcl()->get('portalPermission') !== 'yes') {
                        throw new Forbidden('Not permitted to post to portal users.');
                    }
                }

                if ($assignmentPermission === 'team') {
                    if ($targetType === 'all') {
                        throw new Forbidden('Not permitted to post to all.');
                    }

                    $userTeamIdList = $this->getUser()->getTeamIdList();

                    if ($targetType === 'teams') {
                        if (empty($userTeamIdList)) {
                            throw new Forbidden('Not permitted to post to foreign teams.');
                        }
                        foreach ($teamIdList as $teamId) {
                            if (!in_array($teamId, $userTeamIdList)) {
                                throw new Forbidden('Not permitted to post to foreign teams.');
                            }
                        }
                    } else if ($targetType === 'users') {
                        if (empty($userTeamIdList)) {
                            throw new Forbidden('Not permitted to post to users from foreign teams.');
                        }

                        foreach ($userIdList as $userId) {
                            if ($userId === $this->getUser()->id) {
                                continue;
                            }
                            if (!$this->getEntityManager()->getRepository('User')->checkBelongsToAnyOfTeams($userId, $userTeamIdList)) {
                                throw new Forbidden('Not permitted to post to users from foreign teams.');
                            }
                        }
                    }
                }
            }
        }
        return true;
    }

    public function linkEntity($id, $link, $foreignId)
    {
        if ($link === 'teams' || $link === 'users') {
            throw new Forbidden();
        }
        return parant::linkEntity($id, $link, $foreignId);
    }

    public function unlinkEntity($id, $link, $foreignId)
    {
        if ($link === 'teams' || $link === 'users') {
            throw new Forbidden();
        }
        return parant::unlinkEntity($id, $link, $foreignId);
    }
}
