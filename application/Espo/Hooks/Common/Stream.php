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

namespace Espo\Hooks\Common;

use Espo\ORM\Entity;

use Espo\Core\{
    Utils\Metadata,
    ORM\EntityManager,
    ServiceFactory,
};

use Espo\Entities\User;
use Espo\Entities\Preferences;

class Stream
{
    protected $streamService = null;

    protected $auditedFieldsCache = [];

    protected $hasStreamCache = [];

    protected $isLinkObservableInStreamCache = [];

    protected $statusFields = null;

    public static $order = 9;

    protected $metadata;
    protected $config;
    protected $entityManager;
    protected $serviceFactory;
    protected $user;
    protected $preferences;

    public function __construct(
        Metadata $metadata,
        EntityManager $entityManager,
        ServiceFactory $serviceFactory,
        User $user,
        Preferences $preferences
    ) {
        $this->metadata = $metadata;
        $this->entityManager = $entityManager;
        $this->serviceFactory = $serviceFactory;
        $this->user = $user;
        $this->preferences = $preferences;
    }

    protected function checkHasStream(Entity $entity)
    {
        $entityType = $entity->getEntityType();
        if (!array_key_exists($entityType, $this->hasStreamCache)) {
            $this->hasStreamCache[$entityType] = $this->metadata->get("scopes.{$entityType}.stream");
        }
        return $this->hasStreamCache[$entityType];
    }

    protected function isLinkObservableInStream($scope, $link)
    {
        $key = $scope . '__' . $link;
        if (!array_key_exists($key, $this->isLinkObservableInStreamCache)) {
            $this->isLinkObservableInStreamCache[$key] =
                $this->metadata->get(['scopes', $scope, 'stream']) &&
                $this->metadata->get(['entityDefs', $scope, 'links', $link, 'audited']);
        }

        return $this->isLinkObservableInStreamCache[$key];
    }

    public function afterRemove(Entity $entity)
    {
        if ($this->checkHasStream($entity)) {
            $this->getStreamService()->unfollowAllUsersFromEntity($entity);
        }

        $query = $this->entityManager->getQueryBuilder()
            ->update()
            ->in('Note')
            ->set([
                'deleted' => true,
                'modifiedAt' => date('Y-m-d H:i:s'),
            ])
            ->where([
                'relatedId' => $entity->id,
                'relatedType' => $entity->getEntityType(),
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($query);
    }

    protected function handleCreateRelated(Entity $entity, array $options = [])
    {
        $linkDefs = $this->metadata->get("entityDefs." . $entity->getEntityType() . ".links", []);

        $scopeNotifiedList = [];
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
                    $this->getStreamService()->noteCreateRelated($entity, $scope, $entityId, $options);
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
                    $this->getStreamService()->noteCreateRelated($entity, $scope, $entityId, $options);
                    $scopeNotifiedList[] = $scope;
                }
            }
        }
    }

    protected function getAutofollowUserIdList(string $entityType, array $ignoreUseIdList = [])
    {
        $userIdList = [];

        $autofollowList = $this->entityManager->getRepository('Autofollow')
            ->select(['userId'])
            ->where([
                'entityType' => $entityType,
            ])
            ->find();

        foreach ($autofollowList as $autofollow) {
            $userId = $autofollow->get('userId');
            if (in_array($userId, $ignoreUseIdList)) {
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
                    !$this->user->isSystem()
                    &&
                    !$this->user->isApi()
                    &&
                    $createdById
                    &&
                    $createdById === $this->user->id
                    &&
                    (
                        $this->user->isPortalUser()
                        ||
                        $this->preferences->get('followCreatedEntities')
                        ||
                        (
                            is_array($this->preferences->get('followCreatedEntityTypeList'))
                            &&
                            in_array($entityType, $this->preferences->get('followCreatedEntityTypeList'))
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
                    $this->getStreamService()->noteCreate($entity, $options);
                }

                if (in_array($this->user->id, $userIdList)) {
                	$entity->set('isFollowed', true);
                }

                $autofollowUserIdList = $this->getAutofollowUserIdList($entity->getEntityType(), $userIdList);
                foreach ($autofollowUserIdList as $i => $userId) {
                    if (in_array($userId, $userIdList)) {
                        unset($autofollowUserIdList[$i]);
                    }
                }
                $autofollowUserIdList = array_values($autofollowUserIdList);

                if (!empty($autofollowUserIdList)) {
                    $job = $this->entityManager->getEntity('Job');
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
                    $this->entityManager->saveEntity($job);
                }
            } else {
                if (empty($options['noStream']) && empty($options['silent'])) {
                    if ($entity->isAttributeChanged('assignedUserId')) {
                        $assignedUserId = $entity->get('assignedUserId');
                        if (!empty($assignedUserId)) {
                            $this->getStreamService()->followEntity($entity, $assignedUserId);
                            $this->getStreamService()->noteAssign($entity, $options);

			                if ($this->user->id === $assignedUserId) {
			                	$entity->set('isFollowed', true);
			                }
                        } else {
                            $this->getStreamService()->noteAssign($entity, $options);
                        }
                    }
                    $this->getStreamService()->handleAudited($entity, $options);

                    $statusFields = $this->getStatusFields();

                    if (array_key_exists($entityType, $this->statusFields)) {
                        $field = $this->statusFields[$entityType];
                        $value = $entity->get($field);
                        if (!empty($value) && $value != $entity->getFetched($field)) {
                            $this->getStreamService()->noteStatus($entity, $field, $options);
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
                            if ($this->user->id === $userId) {
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
                    $job = $this->entityManager->getEntity('Job');
                    $job->set([
                        'serviceName' => 'Stream',
                        'methodName' => 'controlFollowersJob',
                        'data' => [
                            'entityType' => $entity->getEntityType(),
                            'entityId' => $entity->id
                        ],
                        'queue' => 'q1'
                    ]);
                    $this->entityManager->saveEntity($job);
                }
            }
        }

        if ($entity->isNew() && empty($options['noStream']) && empty($options['silent']) && $this->metadata->get(['scopes', $entityType, 'object'])) {
            $this->handleCreateRelated($entity, $options);
        }
    }

    public function afterRelate(Entity $entity, array $options = [], array $data = [])
    {
        $entityType = $entity->getEntityType();
        if (
            empty($options['noStream']) && empty($options['silent']) &&
            $this->metadata->get(['scopes', $entityType, 'object'])
        ) {
            if (empty($data['relationName']) || empty($data['foreignEntity']) || !($data['foreignEntity'] instanceof Entity)) {
                return;
            }
            $link = $data['relationName'];
            $foreignEntity = $data['foreignEntity'];

            if (
                $this->metadata->get(['entityDefs', $entityType, 'links', $link, 'audited'])
            ) {
                $this->getStreamService()->noteRelate($foreignEntity, $entityType, $entity->id);
            }

            $foreignLink = $entity->getRelationParam($link, 'foreign');
            if ($this->metadata->get(['entityDefs', $foreignEntity->getEntityType(), 'links', $foreignLink, 'audited'])) {
                $this->getStreamService()->noteRelate($entity, $foreignEntity->getEntityType(), $foreignEntity->id);
            }
        }
    }

    public function afterUnrelate(Entity $entity, array $options = [], array $data = [])
    {
        $entityType = $entity->getEntityType();
        if (
            empty($options['noStream']) && empty($options['silent']) &&
            $this->metadata->get(['scopes', $entityType, 'object'])
        ) {
            if (empty($data['relationName']) || empty($data['foreignEntity']) || !($data['foreignEntity'] instanceof Entity)) {
                return;
            }
            $link = $data['relationName'];
            $foreignEntity = $data['foreignEntity'];

            if ($this->metadata->get(['entityDefs', $entityType, 'links', $link, 'audited'])) {
                $note = $this->entityManager->getRepository('Note')->where([
                    'type' => 'Relate',
                    'parentId' => $entity->id,
                    'parentType' => $entityType,
                    'relatedId' => $foreignEntity->id,
                    'relatedType' => $foreignEntity->getEntityType(),
                ])->findOne();
                if ($note) {
                    $this->entityManager->removeEntity($note);
                }
            }

            $foreignLink = $entity->getRelationParam($link, 'foreign');
            if ($this->metadata->get(['entityDefs', $foreignEntity->getEntityType(), 'links', $foreignLink, 'audited'])) {
                $note = $this->entityManager->getRepository('Note')->where([
                    'type' => 'Relate',
                    'parentId' => $foreignEntity->id,
                    'parentType' => $foreignEntity->getEntityType(),
                    'relatedId' => $entity->id,
                    'relatedType' => $entityType
                ])->findOne();
                if (!$note) return;
                if ($note) {
                    $this->entityManager->removeEntity($note);
                }
            }
        }
    }

    protected function getStatusFields()
    {
        if (is_null($this->statusFields)) {
            $this->statusFields = array();
            $scopes = $this->metadata->get('scopes', []);
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
            $this->streamService = $this->serviceFactory->create('Stream');
        }
        return $this->streamService;
    }
}
