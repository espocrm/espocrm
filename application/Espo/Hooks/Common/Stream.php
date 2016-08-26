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
            $this->isLinkObservableInStreamCache[$key] = $this->getMetadata()->get("scopes.{$scope}.stream") &&
                in_array($link, $this->getMetadata()->get("entityDefs.Note.streamRelated.{$scope}", array()));
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
            DELETE FROM `note`
            WHERE related_id = ".$query->quote($entity->id)." AND related_type = ".$query->quote($entity->getEntityType()) ."
        ";
        $this->getEntityManager()->getPDO()->query($sql);
    }

    protected function handleCreateRelated(Entity $entity)
    {
        $linkDefs = $this->getMetadata()->get("entityDefs." . $entity->getEntityName() . ".links", array());

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
                $foreign = $defs['foreign'];
                if (empty($defs['foreign'])) {
                    continue;
                }
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

    public function afterSave(Entity $entity, array $options = array())
    {
        $entityName = $entity->getEntityType();

        if ($this->checkHasStream($entity)) {
            if ($entity->isNew()) {
                $userIdList = [];

                $assignedUserId = $entity->get('assignedUserId');
                $createdById = $entity->get('createdById');

                if (
                    ($this->getConfig()->get('followCreatedEntities') || $this->getUser()->get('isPortalUser'))
                    &&
                    !empty($createdById)
                ) {
                    $userIdList[] = $createdById;
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
                    $job->set(array(
                        'serviceName' => 'Stream',
                        'method' => 'afterRecordCreatedJob',
                        'data' => array(
                            'userIdList' => $autofollowUserIdList,
                            'entityType' => $entity->getEntityType(),
                            'entityId' => $entity->id
                        )
                    ));
                    $this->getEntityManager()->saveEntity($job);
                }

            } else {
                if (empty($options['noStream']) && empty($options['silent'])) {
                    if ($entity->isFieldChanged('assignedUserId')) {
                        $assignedUserId = $entity->get('assignedUserId');
                        if (!empty($assignedUserId)) {
                            $this->getStreamService()->followEntity($entity, $assignedUserId);
                            $this->getStreamService()->noteAssign($entity);

			                if ($this->getUser()->id === $assignedUserId) {
			                	$entity->set('isFollowed', true);
			                }
                        }
                    }
                    $this->getStreamService()->handleAudited($entity);

                    $statusFields = $this->getStatusFields();

                    if (array_key_exists($entityName, $this->statusFields)) {
                        $field = $this->statusFields[$entityName];
                        $value = $entity->get($field);
                        if (!empty($value) && $value != $entity->getFetched($field)) {
                            $this->getStreamService()->noteStatus($entity, $field);
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
                    $job->set(array(
                        'serviceName' => 'Stream',
                        'method' => 'controlFollowersJob',
                        'data' => array(
                            'entityType' => $entity->getEntityType(),
                            'entityId' => $entity->id
                        )
                    ));
                    $this->getEntityManager()->saveEntity($job);
                }
            }

        }

        if ($entity->isNew() && empty($options['noStream']) && empty($options['silent']) && $this->getMetadata()->get("scopes.{$entityName}.tab")) {
            $this->handleCreateRelated($entity);
        }
    }

    protected function getStatusFields()
    {
        if (is_null($this->statusFields)) {
            $this->statusFields = $this->getMetadata()->get("entityDefs.Note.statusFields", array());
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

