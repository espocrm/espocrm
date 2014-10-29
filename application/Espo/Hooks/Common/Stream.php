<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014  Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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
namespace Espo\Hooks\Common;

use Espo\Core\Hooks\Base;
use Espo\Core\ServiceFactory;
use Espo\ORM\Entity;

class Stream extends
    Base
{

    protected $streamService = null;

    protected $auditedFieldsCache = array();

    protected $statusDefs = array(
        'Lead' => 'status',
        'Case' => 'status',
        'Opportunity' => 'stage',
    );

    public function afterRemove(Entity $entity)
    {
        if ($this->checkHasStream($entity)) {
            $this->getStreamService()->unfollowAllUsersFromEntity($entity);
        }
    }

    protected function checkHasStream(Entity $entity)
    {
        $entityName = $entity->getEntityName();
        return $this->getMetadata()->get("scopes.{$entityName}.stream");
    }

    /**
     * @return \Espo\Services\Stream

     */
    protected function getStreamService()
    {
        if (empty($this->streamService)) {
            $this->streamService = $this->getServiceFactory()->create('Stream');
        }
        return $this->streamService;
    }

    /**
     * @return ServiceFactory

     */
    protected function getServiceFactory()
    {
        return $this->getInjection('serviceFactory');
    }

    public function afterSave(Entity $entity)
    {
        $entityName = $entity->getEntityName();
        if ($this->checkHasStream($entity)) {
            if (!$entity->isFetched()) {
                $assignedUserId = $entity->get('assignedUserId');
                $createdById = $entity->get('createdById');
                if (!empty($createdById)) {
                    $this->getStreamService()->followEntity($entity, $createdById);
                }
                if (!empty($assignedUserId) && $createdById != $assignedUserId) {
                    $this->getStreamService()->followEntity($entity, $assignedUserId);
                }
                $this->getStreamService()->noteCreate($entity);
            } else {
                if ($entity->isFieldChanged('assignedUserId')) {
                    $assignedUserId = $entity->get('assignedUserId');
                    if (!empty($assignedUserId)) {
                        $this->getStreamService()->followEntity($entity, $assignedUserId);
                        $this->getStreamService()->noteAssign($entity);
                    }
                }
                $this->getStreamService()->handleAudited($entity);
                if (array_key_exists($entityName, $this->statusDefs)) {
                    $field = $this->statusDefs[$entityName];
                    $value = $entity->get($field);
                    if (!empty($value) && $value != $entity->getFetched($field)) {
                        $this->getStreamService()->noteStatus($entity, $field);
                    }
                }
            }
        }
        if (!$entity->isFetched() && $this->getMetadata()->get("scopes.{$entityName}.tab")) {
            $this->handleCreateRelated($entity);
        }
    }

    protected function handleCreateRelated(Entity $entity)
    {
        $linkDefs = $this->getMetadata()->get("entityDefs." . $entity->getEntityName() . ".links", array());
        $scopeNotifiedList = array();
        foreach ($linkDefs as $link => $defs) {
            if ($defs['type'] == 'belongsTo') {
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

    protected function isLinkObservableInStream($scope, $link)
    {
        return $this->getMetadata()->get("scopes.{$scope}.stream") &&
        in_array($link, $this->getMetadata()->get("entityDefs.Note.streamRelated.{$scope}", array()));
    }

    protected function init()
    {
        $this->dependencies[] = 'serviceFactory';
    }
}

