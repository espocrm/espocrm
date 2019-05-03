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

class Stream extends \Espo\Core\Hooks\Base
{
    protected $streamService = null;

    protected $auditedFieldsCache = array();

    protected $hasStreamCache = array();

    protected $isLinkObservableInStreamCache = array();

    protected $statusFields = null;

    public static $order = 9;

    protected function init()
    {
        parent::init();
        $this->addDependency('serviceFactory');
    }

    protected function getServiceFactory()
    {
        return $this->getInjection('serviceFactory');
    }

    protected function getPreferences()
    {
        return $this->getInjection('container')->get('preferences');
    }

    protected function checkHasStream(Entity $entity)
    {
        $entityType = $entity->getEntityType();
        if (!array_key_exists($entityType, $this->hasStreamCache)) {
            $this->hasStreamCache[$entityType] = $this->getMetadata()->get("scopes.{$entityType}.stream");
        }
        return $this->hasStreamCache[$entityType];
    }

    protected function isLinkObservableInStream($scope, $link)
    {
        $key = $scope . '__' . $link;
        if (!array_key_exists($key, $this->isLinkObservableInStreamCache)) {
            $this->isLinkObservableInStreamCache[$key] =
                $this->getMetadata()->get(['scopes', $scope, 'stream']) &&
                $this->getMetadata()->get(['entityDefs', $scope, 'links', $link, 'audited']);
        }

        return $this->isLinkObservableInStreamCache[$key];
    }

    public function afterRemove(Entity $entity)
    {
        if ($this->checkHasStream($entity)) {
            $this->getStreamService()->unfollowAllUsersFromEntity($entity);
        }

        $query = $this->getEntityManager()->getQuery();
        $sql = "
            UPDATE `note`
            SET `deleted` = 1, `modified_at` = '".date('Y-m-d H:i:s')."'
            WHERE
                (
                    (related_id = ".$query->quote($entity->id)." AND related_type = ".$query->quote($entity->getEntityType()) .")
                )
        ";
        $this->getEntityManager()->getPDO()->query($sql);
    }

    protected function handleCreateRelated(Entity $entity)
    {
        $linkDefs = $this->getMetadata()->get("entityDefs." . $entity->getEntityType() . ".links", array());

        $scopeNotifiedList = array();
        foreach ($linkDefs as $link => $defs) {
            if ($defs['type'] == 'belongsTo') {
                if (empty($defs['foreign']) || empty($defs['entity'])) {
                    continue;
                }
                $foreign = $defs['foreign'];
                $scope = $defs['entity'];
                $entityId = $entity->get($link . 'Id');
                if (!empty($scope) && !empty($entityId)) {
                    if (in_array($scope, $scopeNotifiedList) || !$this->isLinkObservableInStream($scope, $foreign)) {
                        continue;
                    }
                    $this->getStreamService()->noteCreateRelated($entity, $scope, $entityId);
                    $scopeNotifiedList[] = $scope;
                }
            } else if ($defs['type'] == 'belongsToParent') {
                if (empty($defs['foreign'])) {
                    continue;
                }
                $foreign = $defs['foreign'];
                $scope = $entity->get($link . 'Type');
                $entityId = $entity->get($link . 'Id');
                if (!empty($scope) && !empty($entityId)) {
                    if (in_array($scope, $scopeNotifiedList) || !$this->isLinkObservableInStream($scope, $foreign)) {
                        continue;
                    }
                    $this->getStreamService()->noteCreateRelated($entity, $scope, $entityId);
                    $scopeNotifiedList[] = $scope;

                }
            } else if ($defs['type'] == 'hasMany') {
                if (empty($defs['foreign']) || empty($defs['entity'])) {
                    continue;
                }
                $foreign = $defs['foreign'];
                $scope = $defs['entity'];
                $entityIds = $entity->get($link . 'Ids');
                if (!empty($scope) && is_array($entityIds) && !empty($entityIds)) {
                    if (in_array($scope, $scopeNotifiedList) || !$this->isLinkObservableInStream($scope, $foreign)) {
                        continue;
                    }
                    $entityId = $entityIds[0];
                    $this->getStreamService()->noteCreateRelated($entity, $scope, $entityId);
                    $scopeNotifiedList[] = $scope;
                }
            }
        }
    }

    protected function getAutofollowUserIdList(Entity $entity, array $ignoreList = array())
    {
        $entityType = $entity->getEntityType();
        $pdo = $this->getEntityManager()->getPDO();
        $userIdList = [];

        $sql = "
            SELECT user_id AS 'userId' FROM autofollow WHERE entity_type = ".$pdo->quote($entityType)."
        ";
        $sth = $pdo->prepare($sql);
        $sth->execute();
        $rows = $sth->fetchAll();
        foreach ($rows as $row) {
            $userId = $row['userId'];
            if (in_array($userId, $ignoreList)) {
                continue;
            }
            $userIdList[] = $userId;
        }

        return $userIdList;
    }

    public function afterSave(Entity $entity, array $options = [])
    {
        $entityType = $entity->getEntityType();

        if ($this->checkHasStream($entity)) {

            $hasAssignedUsersField = false;
            if ($entity->hasLinkMultipleField('assignedUsers')) {
                $hasAssignedUsersField = true;
            }

            if ($entity->isNew()) {
                $userIdList = [];

                $assignedUserId = $entity->get('assignedUserId');
                $createdById = $entity->get('createdById');

                $assignedUserIdList = [];
                if ($hasAssignedUsersField) {
                    $assignedUserIdList = $entity->getLinkMultipleIdList('assignedUsers');
                }

                if (
                    !$this->getUser()->isSystem()
                    &&
                    !$this->getUser()->isApi()
                    &&
                    $createdById
                    &&
                    $createdById === $this->getUser()->id
                    &&
                    (
                        $this->getUser()->isPortalUser()
                        ||
                        $this->getPreferences()->get('followCreatedEntities')
                        ||
                        (
                            is_array($this->getPreferences()->get('followCreatedEntityTypeList'))
                            &&
                            in_array($entityType, $this->getPreferences()->get('followCreatedEntityTypeList'))
                        )
                    )
                ) {
                    $userIdList[] = $createdById;
                }

                if ($hasAssignedUsersField) {
                    foreach ($assignedUserIdList as $userId) {
                        if (!empty($userId) && !in_array($userId, $userIdList)) {
                            $userIdList[] = $userId;
                        }
                    }
                }

                if (!empty($assignedUserId) && !in_array($assignedUserId, $userIdList)) {
                    $userIdList[] = $assignedUserId;
                }

                if (!empty($userIdList)) {
                    $this->getStreamService()->followEntityMass($entity, $userIdList);
                }

                if (empty($options['noStream']) && empty($options['silent'])) {
                    $this->getStreamService()->noteCreate($entity);
                }

                if (in_array($this->getUser()->id, $userIdList)) {
                	$entity->set('isFollowed', true);
                }

                $autofollowUserIdList = $this->getAutofollowUserIdList($entity, $userIdList);
                foreach ($autofollowUserIdList as $i => $userId) {
                    if (in_array($userId, $userIdList)) {
                        unset($autofollowUserIdList[$i]);
                    }
                }
                $autofollowUserIdList = array_values($autofollowUserIdList);

                if (!empty($autofollowUserIdList)) {
                    $job = $this->getEntityManager()->getEntity('Job');
                    $job->set([
                        'serviceName' => 'Stream',
                        'methodName' => 'afterRecordCreatedJob',
                        'data' => [
                            'userIdList' => $autofollowUserIdList,
                            'entityType' => $entity->getEntityType(),
                            'entityId' => $entity->id
                        ],
                        'queue' => 'q1'
                    ]);
                    $this->getEntityManager()->saveEntity($job);
                }
            } else {
                if (empty($options['noStream']) && empty($options['silent'])) {
                    if ($entity->isAttributeChanged('assignedUserId')) {
                        $assignedUserId = $entity->get('assignedUserId');
                        if (!empty($assignedUserId)) {
                            $this->getStreamService()->followEntity($entity, $assignedUserId);
                            $this->getStreamService()->noteAssign($entity);

			                if ($this->getUser()->id === $assignedUserId) {
			                	$entity->set('isFollowed', true);
			                }
                        } else {
                            $this->getStreamService()->noteAssign($entity);
                        }
                    }
                    $this->getStreamService()->handleAudited($entity);

                    $statusFields = $this->getStatusFields();

                    if (array_key_exists($entityType, $this->statusFields)) {
                        $field = $this->statusFields[$entityType];
                        $value = $entity->get($field);
                        if (!empty($value) && $value != $entity->getFetched($field)) {
                            $this->getStreamService()->noteStatus($entity, $field);
                        }
                    }

                    $assignedUserIdList = [];
                    if ($hasAssignedUsersField) {
                        $assignedUserIdList = $entity->getLinkMultipleIdList('assignedUsers');
                    }

                    if ($hasAssignedUsersField) {
                        $fetchedAssignedUserIdList = $entity->getFetched('assignedUsersIds');
                        if (!is_array($fetchedAssignedUserIdList)) {
                            $fetchedAssignedUserIdList = [];
                        }
                        foreach ($assignedUserIdList as $userId) {
                            if (in_array($userId, $fetchedAssignedUserIdList)) {
                                continue;
                            }
                            $this->getStreamService()->followEntity($entity, $userId);
                            if ($this->getUser()->id === $userId) {
                                $entity->set('isFollowed', true);
                            }
                        }
                    }
                }

                $methodName = 'isChangedWithAclAffect';
                if (
                    (
                        method_exists($entity, $methodName) && $entity->$methodName()
                    )
                    ||
                    (
                        !method_exists($entity, $methodName)
                        &&
                        (
                            $entity->isAttributeChanged('assignedUserId')
                            ||
                            $entity->isAttributeChanged('teamsIds')
                            ||
                            $entity->isAttributeChanged('assignedUsersIds')
                        )
                    )
                ) {
                    $job = $this->getEntityManager()->getEntity('Job');
                    $job->set([
                        'serviceName' => 'Stream',
                        'methodName' => 'controlFollowersJob',
                        'data' => [
                            'entityType' => $entity->getEntityType(),
                            'entityId' => $entity->id
                        ],
                        'queue' => 'q1'
                    ]);
                    $this->getEntityManager()->saveEntity($job);
                }
            }
        }

        if ($entity->isNew() && empty($options['noStream']) && empty($options['silent']) && $this->getMetadata()->get(['scopes', $entityType, 'object'])) {
            $this->handleCreateRelated($entity);
        }
    }

    public function afterRelate(Entity $entity, array $options = array(), array $data = array())
    {
        $entityType = $entity->getEntityType();
        if (
            empty($options['noStream']) && empty($options['silent']) &&
            $this->getMetadata()->get(['scopes', $entityType, 'object'])
        ) {
            if (empty($data['relationName']) || empty($data['foreignEntity']) || !($data['foreignEntity'] instanceof Entity)) {
                return;
            }
            $link = $data['relationName'];
            $foreignEntity = $data['foreignEntity'];

            if (
                $this->getMetadata()->get(['entityDefs', $entityType, 'links', $link, 'audited'])

            ) {
                $n = $this->getEntityManager()->getRepository('Note')->where(array(
                    'type' => 'Relate',
                    'parentId' => $entity->id,
                    'parentType' => $entityType,
                    'relatedId' => $foreignEntity->id,
                    'relatedType' => $foreignEntity->getEntityType()
                ))->findOne();
                if (!$n) {
                    $note = $this->getEntityManager()->getEntity('Note');
                    $note->set(array(
                        'type' => 'Relate',
                        'parentId' => $entity->id,
                        'parentType' => $entityType,
                        'relatedId' => $foreignEntity->id,
                        'relatedType' => $foreignEntity->getEntityType()
                    ));
                    $this->getEntityManager()->saveEntity($note);
                }
            }

            $foreignLink = $entity->getRelationParam($link, 'foreign');
            if ($this->getMetadata()->get(['entityDefs', $foreignEntity->getEntityType(), 'links', $foreignLink, 'audited'])) {
                $n = $this->getEntityManager()->getRepository('Note')->where(array(
                    'type' => 'Relate',
                    'parentId' => $foreignEntity->id,
                    'parentType' => $foreignEntity->getEntityType(),
                    'relatedId' => $entity->id,
                    'relatedType' => $entityType
                ))->findOne();
                if (!$n) {
                    $note = $this->getEntityManager()->getEntity('Note');
                    $note->set(array(
                        'type' => 'Relate',
                        'parentId' => $foreignEntity->id,
                        'parentType' => $foreignEntity->getEntityType(),
                        'relatedId' => $entity->id,
                        'relatedType' => $entityType
                    ));
                    $this->getEntityManager()->saveEntity($note);
                }
            }
        }
    }

    public function afterUnrelate(Entity $entity, array $options = array(), array $data = array())
    {
        $entityType = $entity->getEntityType();
        if (
            empty($options['noStream']) && empty($options['silent']) &&
            $this->getMetadata()->get(['scopes', $entityType, 'object'])
        ) {
            if (empty($data['relationName']) || empty($data['foreignEntity']) || !($data['foreignEntity'] instanceof Entity)) {
                return;
            }
            $link = $data['relationName'];
            $foreignEntity = $data['foreignEntity'];

            if ($this->getMetadata()->get(['entityDefs', $entityType, 'links', $link, 'audited'])) {
                $note = $this->getEntityManager()->getRepository('Note')->where(array(
                    'type' => 'Relate',
                    'parentId' => $entity->id,
                    'parentType' => $entityType,
                    'relatedId' => $foreignEntity->id,
                    'relatedType' => $foreignEntity->getEntityType()
                ))->findOne();
                if ($note) {
                    $this->getEntityManager()->removeEntity($note);
                }
            }

            $foreignLink = $entity->getRelationParam($link, 'foreign');
            if ($this->getMetadata()->get(['entityDefs', $foreignEntity->getEntityType(), 'links', $foreignLink, 'audited'])) {
                $note = $this->getEntityManager()->getRepository('Note')->where(array(
                    'type' => 'Relate',
                    'parentId' => $foreignEntity->id,
                    'parentType' => $foreignEntity->getEntityType(),
                    'relatedId' => $entity->id,
                    'relatedType' => $entityType
                ))->findOne();
                if (!$note) return;
                if ($note) {
                    $this->getEntityManager()->removeEntity($note);
                }
            }
        }
    }

    protected function getStatusFields()
    {
        if (is_null($this->statusFields)) {
            $this->statusFields = array();
            $scopes = $this->getMetadata()->get('scopes', array());
            foreach ($scopes as $scope => $data) {
                if (empty($data['statusField'])) continue;
                $this->statusFields[$scope] = $data['statusField'];
            }
        }
        return $this->statusFields;
    }

    protected function getStreamService()
    {
        if (empty($this->streamService)) {
            $this->streamService = $this->getServiceFactory()->create('Stream');
        }
        return $this->streamService;
    }
}
