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

namespace Espo\Services;

use \Espo\Core\Exceptions\Forbidden;
use \Espo\Core\Exceptions\NotFound;
use \Espo\Core\Exceptions\BadRequest;

use Espo\ORM\Entity;

class Note extends Record
{
    protected $noteNotificationPeriod = '1 hour';

    public function getEntity($id = null)
    {
        $entity = parent::getEntity($id);
        if (!empty($id)) {
            $entity->loadAttachments();
        }
        return $entity;
    }

    public function create($data)
    {
        if (!empty($data->parentType) && !empty($data->parentId)) {
            $entity = $this->getEntityManager()->getEntity($data->parentType, $data->parentId);
            if ($entity) {
                if (!$this->getAcl()->check($entity, 'read')) {
                    throw new Forbidden();
                }
            }
        }

        return parent::create($data);
    }

    protected function afterCreateEntity(Entity $entity, $data)
    {
        parent::afterCreateEntity($entity, $data);

        if ($entity->get('type') === 'Post' && $entity->get('parentType') && $entity->get('parentType')) {
            $preferences = $this->getEntityManager()->getEntity('Preferences', $this->getUser()->id);
            if ($preferences && $preferences->get('followEntityOnStreamPost')) {
                if ($this->getMetadata()->get(['scopes', $entity->get('parentType'), 'stream'])) {
                    $parent = $this->getEntityManager()->getEntity($entity->get('parentType'), $entity->get('parentId'));
                    if ($parent && !$this->getUser()->isSystem() && !$this->getUser()->isApi()) {
                        $this->getServiceFactory()->create('Stream')->followEntity($parent, $this->getUser()->id);
                    }
                }
            }
        }
    }

    protected function beforeCreateEntity(Entity $entity, $data)
    {
        parent::beforeCreateEntity($entity, $data);

        if ($entity->get('type') === 'Post') {
            $this->handlePostText($entity);
        }

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

        if ($entity->get('type') === 'Post') {
            $this->handlePostText($entity);
        }

        $entity->clear('targetType');
        $entity->clear('usersIds');
        $entity->clear('teamsIds');
        $entity->clear('portalsIds');
        $entity->clear('isGlobal');
    }

    protected function handlePostText(Entity $entity)
    {
        $post = $entity->get('post');
        if (empty($post)) return;

        $siteUrl = $this->getConfig()->getSiteUrl();

        $regexp = '/' . preg_quote($siteUrl, '/') . '(\/portal|\/portal\/[a-zA-Z0-9]*)?\/#([A-Z][a-zA-Z0-9]*)\/view\/([a-zA-Z0-9]*)/';
        $post = preg_replace($regexp, '[\2/\3](#\2/view/\3)', $post);

        $entity->set('post', $post);
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

    public function link($id, $link, $foreignId)
    {
        if ($link === 'teams' || $link === 'users') {
            throw new Forbidden();
        }
        return parent::link($id, $link, $foreignId);
    }

    public function unlink($id, $link, $foreignId)
    {
        if ($link === 'teams' || $link === 'users') {
            throw new Forbidden();
        }
        return parent::unlink($id, $link, $foreignId);
    }

    public function processNoteAclJob($data)
    {
        $targetType = $data->targetType;
        $targetId = $data->targetId;

        if ($targetType && $targetId && $this->getEntityManager()->hasRepository($targetType)) {
            $entity = $this->getEntityManager()->getEntity($targetType, $targetId);
            if ($entity) {
                $this->processNoteAcl($entity, true);
            }
        }
    }

    public function processNoteAcl(Entity $entity, $forceProcessNoteNotifications = false)
    {
        $entityType = $entity->getEntityType();

        if (in_array($entityType, ['Note', 'User', 'Team', 'Role', 'Portal', 'PortalRole'])) return;

        if (!$this->getMetadata()->get(['scopes', $entityType, 'acl'])) return;
        if (!$this->getMetadata()->get(['scopes', $entityType, 'object'])) return;

        $ownerUserIdAttribute = $this->getAclManager()->getImplementation($entityType)->getOwnerUserIdAttribute($entity);

        $usersAttributeIsChanged = false;
        $teamsAttributeIsChanged = false;

        if ($ownerUserIdAttribute) {
            if ($entity->isAttributeChanged($ownerUserIdAttribute)) {
                $usersAttributeIsChanged = true;
            }

            if ($usersAttributeIsChanged || $forceProcessNoteNotifications) {
                if ($entity->getAttributeParam($ownerUserIdAttribute, 'isLinkMultipleIdList')) {
                    $userLink = $entity->getAttributeParam($ownerUserIdAttribute, 'relation');
                    $userIdList = $entity->getLinkMultipleIdList($userLink);
                } else {
                    $userId = $entity->get($ownerUserIdAttribute);
                    if ($userId) {
                        $userIdList = [$userId];
                    } else {
                        $userIdList = [];
                    }
                }
            }
        }

        if ($entity->hasLinkMultipleField('teams')) {
            if ($entity->isAttributeChanged('teamsIds')) {
                $teamsAttributeIsChanged = true;
            }
            if ($teamsAttributeIsChanged || $forceProcessNoteNotifications) {
                $teamIdList = $entity->getLinkMultipleIdList('teams');
            }
        }

        if ($usersAttributeIsChanged || $teamsAttributeIsChanged || $forceProcessNoteNotifications) {
            $noteList = $this->getEntityManager()->getRepository('Note')->where([
                'OR' => [
                    [
                        'relatedId' => $entity->id,
                        'relatedType' => $entityType
                    ],
                    [
                        'parentId' => $entity->id,
                        'parentType' => $entityType,
                        'superParentId!=' => null,
                        'relatedId' => null
                    ]
                ]
            ])->select([
                'id',
                'parentType',
                'parentId',
                'superParentType',
                'superParentId',
                'isInternal',
                'relatedType',
                'relatedId',
                'createdAt'
            ])->find();

            $noteOptions = [];
            if (!empty($forceProcessNoteNotifications)) {
                $noteOptions['forceProcessNotifications'] = true;
            }

            $period = '-' . $this->getConfig()->get('noteNotificationPeriod', $this->noteNotificationPeriod);
            $threshold = new \DateTime();
            $threshold->modify($period);

            foreach ($noteList as $note) {
                if (!$entity->isNew()) {
                    try {
                        $createdAtDt = new \DateTime($note->get('createdAt'));
                        if ($createdAtDt->getTimestamp() < $threshold->getTimestamp()) {
                            continue;
                        }
                    } catch (\Exception $e) {};
                }
                if ($teamsAttributeIsChanged || $forceProcessNoteNotifications) {
                    $note->set('teamsIds', $teamIdList);
                }
                if ($usersAttributeIsChanged || $forceProcessNoteNotifications) {
                    $note->set('usersIds', $userIdList);
                }
                $this->getEntityManager()->saveEntity($note, $noteOptions);
            }
        }
    }
}
