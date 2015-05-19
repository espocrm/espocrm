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

namespace Espo\Services;

use \Espo\ORM\Entity;

use \Espo\Core\Exceptions\Error;
use \Espo\Core\Exceptions\Forbidden;
use \Espo\Core\Exceptions\BadRequest;
use \Espo\Core\Exceptions\Conflict;
use \Espo\Core\Exceptions\NotFound;


use \Espo\Core\Utils\Util;

class Record extends \Espo\Core\Services\Base
{
    protected $dependencies = array(
        'entityManager',
        'user',
        'metadata',
        'acl',
        'config',
        'serviceFactory',
        'fileManager',
        'selectManagerFactory',
        'preferences'
    );

    protected $getEntityBeforeUpdate = false;

    protected $entityName;

    private $streamService;

    protected $notFilteringFields = array(); // TODO maybe remove it

    protected $internalFields = array();

    protected $readOnlyFields = array();

    protected $linkSelectParams = array();

    protected $mergeLinkList = array();

    public function __construct()
    {
        parent::__construct();
        if (empty($this->entityName)) {
            $name = get_class($this);
            if (preg_match('@\\\\([\w]+)$@', $name, $matches)) {
                $name = $matches[1];
            }
            if ($name != 'Record') {
                $this->entityName = Util::normilizeScopeName($name);
            }
        }
        $this->entityType = $this->entityName;
    }

    public function setEntityName($entityType)
    {
        $this->entityName = $entityType;
        $this->entityType = $entityType;
    }

    protected function getServiceFactory()
    {
        return $this->injections['serviceFactory'];
    }

    protected function getSelectManagerFactory()
    {
        return $this->injections['selectManagerFactory'];
    }

    protected function getAcl()
    {
        return $this->injections['acl'];
    }

    protected function getFileManager()
    {
        return $this->injections['fileManager'];
    }

    protected function getPreferences()
    {
        return $this->injections['preferences'];
    }

    protected function getMetadata()
    {
        return $this->injections['metadata'];
    }

    protected function getRepository()
    {
        return $this->getEntityManager()->getRepository($this->entityName);
    }

    protected function getRecordService($name)
    {
        if ($this->getServiceFactory()->checkExists($name)) {
            $service = $this->getServiceFactory()->create($name);
        } else {
            $service = $this->getServiceFactory()->create('Record');
            $service->setEntityName($name);
        }

        return $service;
    }

    protected function prepareEntity($entity)
    {

    }

    public function getEntity($id = null)
    {
        $entity = $this->getRepository()->get($id);
        if (!empty($entity) && !empty($id)) {
            $this->loadAdditionalFields($entity);

            if (!$this->getAcl()->check($entity, 'read')) {
                throw new Forbidden();
            }
        }
        if (!empty($entity)) {
            $this->prepareEntityForOutput($entity);
        }
        return $entity;
    }

    protected function getStreamService()
    {
        if (empty($this->streamService)) {
            $this->streamService = $this->getServiceFactory()->create('Stream');
        }
        return $this->streamService;
    }

    protected function loadIsFollowed(Entity $entity)
    {
        if ($this->getStreamService()->checkIsFollowed($entity)) {
            $entity->set('isFollowed', true);
        } else {
            $entity->set('isFollowed', false);
        }
    }

    protected function loadIsEditable(Entity $entity)
    {
        $entity->set('isEditable', $this->getAcl()->check($entity, 'edit'));
    }

    protected function loadLinkMultipleFields(Entity $entity)
    {
        $fieldDefs = $this->getMetadata()->get('entityDefs.' . $entity->getEntityName() . '.fields', array());
        foreach ($fieldDefs as $field => $defs) {
            if (isset($defs['type']) && $defs['type'] == 'linkMultiple' && empty($defs['noLoad'])) {
                $columns = null;
                if (!empty($defs['columns'])) {
                    $columns = $defs['columns'];
                }
                $entity->loadLinkMultipleField($field, $columns);
            }
        }
    }

    protected function loadParentNameFields(Entity $entity)
    {
        $fieldDefs = $this->getMetadata()->get('entityDefs.' . $entity->getEntityName() . '.fields', array());
        foreach ($fieldDefs as $field => $defs) {
            if (isset($defs['type']) && $defs['type'] == 'linkParent') {
                $id = $entity->get($field . 'Id');
                $scope = $entity->get($field . 'Type');

                if ($scope) {
                    if ($foreignEntity = $this->getEntityManager()->getEntity($scope, $id)) {
                        $entity->set($field . 'Name', $foreignEntity->get('name'));
                    }
                }
            }
        }
    }

    protected function loadNotJoinedLinkFields(Entity $entity)
    {
        $linkDefs = $this->getMetadata()->get('entityDefs.' . $entity->getEntityName() . '.links', array());
        foreach ($linkDefs as $link => $defs) {
            if (isset($defs['type']) && $defs['type'] == 'belongsTo') {
                if (!empty($defs['noJoin']) && !empty($defs['entity'])) {
                    $nameField = $link . 'Name';
                    $idField = $link . 'Id';
                    if ($entity->hasField($nameField) && $entity->hasField($idField)) {
                        $id = $entity->get($idField);
                    }

                    $scope = $defs['entity'];
                    if (!empty($scope) && $foreignEntity = $this->getEntityManager()->getEntity($scope, $id)) {
                        $entity->set($nameField, $foreignEntity->get('name'));
                    }
                }
            }
        }
    }

    protected function loadAdditionalFields(Entity $entity)
    {
        $this->loadLinkMultipleFields($entity);
        $this->loadParentNameFields($entity);
        $this->loadIsFollowed($entity);
        $this->loadEmailAddressField($entity);
        $this->loadPhoneNumberField($entity);
        $this->loadNotJoinedLinkFields($entity);
        $this->loadIsEditable($entity);
    }

    public function loadAdditionalFieldsForList(Entity $entity)
    {
        $this->loadParentNameFields($entity);
    }

    protected function loadEmailAddressField(Entity $entity)
    {
        $fieldDefs = $this->getMetadata()->get('entityDefs.' . $entity->getEntityName() . '.fields', array());
        if (!empty($fieldDefs['emailAddress']) && $fieldDefs['emailAddress']['type'] == 'email') {
            $dataFieldName = 'emailAddressData';
            $entity->set($dataFieldName, $this->getEntityManager()->getRepository('EmailAddress')->getEmailAddressData($entity));
        }
    }

    protected function loadPhoneNumberField(Entity $entity)
    {
        $fieldDefs = $this->getMetadata()->get('entityDefs.' . $entity->getEntityName() . '.fields', array());
        if (!empty($fieldDefs['phoneNumber']) && $fieldDefs['phoneNumber']['type'] == 'phone') {
            $dataFieldName = 'phoneNumberData';
            $entity->set($dataFieldName, $this->getEntityManager()->getRepository('PhoneNumber')->getPhoneNumberData($entity));
        }
    }

    protected function getSelectManager($entityName)
    {
        return $this->getSelectManagerFactory()->create($entityName);
    }

    protected function storeEntity(Entity $entity)
    {
        return $this->getRepository()->save($entity);
    }

    protected function isValid($entity)
    {
        $fieldDefs = $entity->getFields();
        if ($entity->hasField('name') && !empty($fieldDefs['name']['required'])) {
            if (!$entity->get('name')) {
                return false;
            }
        }

        if (!$this->isPermittedAssignedUser($entity)) {
            return false;
        }

        if (!$this->isPermittedTeams($entity)) {
            return false;
        }

        return true;
    }

    public function isPermittedAssignedUser(Entity $entity)
    {
        if (!$entity->hasField('assignedUserId')) {
            return true;
        }

        $assignedUserId = $entity->get('assignedUserId');

        if (empty($assignedUserId)) {
            return true;
        }

        $assignmentPermission = $this->getAcl()->get('assignmentPermission');

        if (empty($assignmentPermission) || $assignmentPermission === true || !in_array($assignmentPermission, ['team', 'no'])) {
            return true;
        }

        $toProcess = false;

        if (!$entity->isNew()) {
            if ($entity->isFieldChanged('assignedUserId')) {
                $toProcess = true;
            }
        } else {
            $toProcess = true;
        }

        if ($toProcess) {
            if ($assignmentPermission == 'no') {
                if ($this->getUser()->id != $assignedUserId) {
                    return false;
                }
            } else if ($assignmentPermission == 'team') {
                $teamIds = $this->getUser()->get('teamsIds');
                if (!$this->getEntityManager()->getRepository('User')->checkBelongsToAnyOfTeams($assignedUserId, $teamIds)) {
                    return false;
                }
            }
        }

        return true;
    }

    public function isPermittedTeams(Entity $entity)
    {
        $assignmentPermission = $this->getAcl()->get('assignmentPermission');

        if (empty($assignmentPermission) || $assignmentPermission === true || !in_array($assignmentPermission, ['team', 'no'])) {
            return true;
        }

        if (!$entity->hasField('teamsIds')) {
            return true;
        }
        $teamIds = $entity->get('teamsIds');
        if (empty($teamIds)) {
            return true;
        }

        $newIds = [];

        if (!$entity->isNew()) {
            $existingIds = [];
            foreach ($entity->get('teams') as $team) {
                $existingIds[] = $team->id;
            }
            foreach ($teamIds as $id) {
                if (!in_array($id, $existingIds)) {
                    $newIds[] = $id;
                }
            }
        } else {
            $newIds = $teamIds;
        }

        if (empty($newIds)) {
            return true;
        }

        $userTeamIds = $this->getUser()->get('teamsIds');

        foreach ($newIds as $id) {
            if (!in_array($id, $userTeamIds)) {
                return false;
            }
        }
        return true;
    }

    protected function stripTags($string)
    {
        return strip_tags($string, '<a><img><p><br><span><ol><ul><li><blockquote><pre><h1><h2><h3><h4><h5><table><tr><td><th><thead><tbody><i><b>');
    }

    protected function filterInputField($field, $value)
    {
        if (in_array($field, $this->notFilteringFields)) {
            return $value;
        }
        $methodName = 'filterInputField' . ucfirst($field);
        if (method_exists($this, $methodName)) {
            $value = $this->$methodName($value);
        }
        return $value;
    }

    protected function filterInput(&$data)
    {
        foreach ($this->readOnlyFields as $field) {
            unset($data[$field]);
        }

        foreach ($data as $key => $value) {
            if (is_array($data[$key])) {
                foreach ($data[$key] as $i => $v) {
                    $data[$key][$i] = $this->filterInputField($i, $data[$key][$i]);
                }
            } else if ($data[$key] instanceof \stdClass) {
                $propertyList = get_object_vars($data[$key]);
                foreach ($propertyList as $property => $value) {
                    $data[$key]->$property = $this->filterInputField($property, $data[$key]->$property);
                }
            } else {
                $data[$key] = $this->filterInputField($key, $data[$key]);
            }
        }
    }

    protected function handleInput(&$data)
    {

    }

    public function createEntity($data)
    {
        $entity = $this->getRepository()->get();

        $this->filterInput($data);
        $this->handleInput($data);

        $entity->set($data);

        $this->beforeCreate($entity, $data);

        if (!$this->isValid($entity)) {
            throw new BadRequest();
        }

        if (empty($data['forceDuplicate'])) {
            $duplicates = $this->checkEntityForDuplicate($entity);
            if (!empty($duplicates)) {
                $reason = array(
                    'reason' => 'Duplicate',
                    'data' => $duplicates
                );
                throw new Conflict(json_encode($reason));
            }
        }

        if ($this->storeEntity($entity)) {
            $this->afterCreate($entity, $data);
            $this->prepareEntityForOutput($entity);
            return $entity;
        }

        throw new Error();
    }


    public function updateEntity($id, $data)
    {
        unset($data['deleted']);

        if (empty($id)) {
            throw BadRequest();
        }

        $this->filterInput($data);
        $this->handleInput($data);

        if ($this->getEntityBeforeUpdate) {
            $entity = $this->getEntity($id);
        } else {
            $entity = $this->getRepository()->get($id);
        }

        if (!$entity) {
            throw new NotFound();
        }

        if (!$this->getAcl()->check($entity, 'edit')) {
            throw new Forbidden();
        }

        $entity->set($data);

        $this->beforeUpdate($entity, $data);

        if (!$this->isValid($entity)) {
            throw new BadRequest();
        }

        if ($this->storeEntity($entity)) {
            $this->afterUpdate($entity, $data);
            $this->prepareEntityForOutput($entity);
            return $entity;
        }

        throw new Error();
    }

    protected function beforeCreate(Entity $entity, array $data = array())
    {
    }

    protected function afterCreate(Entity $entity, array $data = array())
    {
    }

    protected function beforeUpdate(Entity $entity, array $data = array())
    {
    }

    protected function afterUpdate(Entity $entity, array $data = array())
    {
    }

    protected function beforeDelete(Entity $entity)
    {
    }

    protected function afterDelete(Entity $entity)
    {
    }

    public function deleteEntity($id)
    {
        if (empty($id)) {
            throw BadRequest();
        }

        $entity = $this->getRepository()->get($id);

        if (!$entity) {
            throw new NotFound();
        }

        if (!$this->getAcl()->check($entity, 'delete')) {
            throw new Forbidden();
        }

        $this->beforeDelete($entity);

        $result = $this->getRepository()->remove($entity);
        if ($result) {
            $this->afterDelete($entity);
            return $result;
        }
    }

    protected function getSelectParams($params)
    {
        $selectParams = $this->getSelectManager($this->entityName)->getSelectParams($params, true);

        return $selectParams;
    }

    public function findEntities($params)
    {
        $disableCount = false;
        if (in_array($this->entityName, $this->getConfig()->get('disabledCountQueryEntityList', array()))) {
            $disableCount = true;
        }

        $maxSize = 0;
        if ($disableCount) {
           if (!empty($params['maxSize'])) {
               $maxSize = $params['maxSize'];
               $params['maxSize'] = $params['maxSize'] + 1;
           }
        }

        $selectParams = $this->getSelectParams($params);

        $collection = $this->getRepository()->find($selectParams);

        foreach ($collection as $e) {
            $this->loadAdditionalFieldsForList($e);
            $this->prepareEntityForOutput($e);
        }

        if (!$disableCount) {
            $total = $this->getRepository()->count($selectParams);
        } else {
            if ($maxSize && count($collection) > $maxSize) {
                $total = -1;
                unset($collection[count($collection) - 1]);
            } else {
                $total = -2;
            }
        }

        return array(
            'total' => $total,
            'collection' => $collection,
        );
    }

    public function findLinkedEntities($id, $link, $params)
    {
        $entity = $this->getRepository()->get($id);
        if (!$entity) {
            throw new NotFound();
        }
        if (!$this->getAcl()->check($entity, 'read')) {
            throw new Forbidden();
        }

        $methodName = 'findLinkedEntities' . ucfirst($link);
        if (method_exists($this, $methodName)) {
            return $this->$methodName($id, $link, $params);
        }

        $foreignEntityName = $entity->relations[$link]['entity'];

        if (!$this->getAcl()->check($foreignEntityName, 'read')) {
            throw new Forbidden();
        }

        $disableCount = false;
        if (in_array($foreignEntityName, $this->getConfig()->get('disabledCountQueryEntityList', array()))) {
            $disableCount = true;
        }

        $maxSize = 0;
        if ($disableCount) {
            if (!empty($params['maxSize'])) {
                $maxSize = $params['maxSize'];
                $params['maxSize'] = $params['maxSize'] + 1;
            }
        }

        $selectParams = $this->getSelectManager($foreignEntityName)->getSelectParams($params, true);

        if (array_key_exists($link, $this->linkSelectParams)) {
            $selectParams = array_merge($selectParams, $this->linkSelectParams[$link]);
        }

        $collection = $this->getRepository()->findRelated($entity, $link, $selectParams);

        $recordService = $this->getRecordService($foreignEntityName);

        foreach ($collection as $e) {
            $recordService->loadAdditionalFieldsForList($e);
            $recordService->prepareEntityForOutput($e);
        }

        if (!$disableCount) {
            $total = $this->getRepository()->countRelated($entity, $link, $selectParams);
        } else {
            if ($maxSize && count($collection) > $maxSize) {
                $total = -1;
                unset($collection[count($collection) - 1]);
            } else {
                $total = -2;
            }
        }

        return array(
            'total' => $total,
            'collection' => $collection
        );
    }

    public function linkEntity($id, $link, $foreignId)
    {
        $entity = $this->getRepository()->get($id);

        $foreignEntityName = $entity->relations[$link]['entity'];

        if (!$this->getAcl()->check($entity, 'edit')) {
            throw new Forbidden();
        }

        if (empty($foreignEntityName)) {
            throw new Error();
        }

        $foreignEntity = $this->getEntityManager()->getEntity($foreignEntityName, $foreignId);

        if (!$this->getAcl()->check($foreignEntity, 'edit')) {
            throw new Forbidden();
        }

        if (!empty($foreignEntity)) {
            $this->getRepository()->relate($entity, $link, $foreignEntity);
            return true;
        }
    }

    public function unlinkEntity($id, $link, $foreignId)
    {
        $entity = $this->getRepository()->get($id);

        $foreignEntityName = $entity->relations[$link]['entity'];

        if (!$this->getAcl()->check($entity, 'edit')) {
            throw new Forbidden();
        }

        if (empty($foreignEntityName)) {
            throw new Error();
        }

        $foreignEntity = $this->getEntityManager()->getEntity($foreignEntityName, $foreignId);

        if (!$this->getAcl()->check($foreignEntity, 'edit')) {
            throw new Forbidden();
        }

        if (!empty($foreignEntity)) {
            $this->getRepository()->unrelate($entity, $link, $foreignEntity);
            return true;
        }
    }

    public function linkEntityMass($id, $link, $where)
    {
        $entity = $this->getRepository()->get($id);

        $entityType = $entity->getEntityType();
        $foreignEntityType = $entity->relations[$link]['entity'];

        if (!$this->getAcl()->check($entity, 'edit')) {
            throw new Forbidden();
        }
        if (empty($foreignEntityType)) {
            throw new Error();
        }
        if (!$this->getAcl()->check($foreignEntityType, 'edit')) {
            throw new Forbidden();
        }

        if (!is_array($where)) {
            $where = array();
        }
        $params['where'] = $where;


        $selectParams = $this->getRecordService($foreignEntityType)->getSelectParams($params);

        return $this->getRepository()->massRelate($entity, $link, $selectParams);
    }

    public function massUpdate($attributes = array(), array $params)
    {
        $idsUpdated = array();
        $repository = $this->getRepository();

        $count = 0;

        if (array_key_exists('ids', $params)) {
            $ids = $params['ids'];
            foreach ($ids as $id) {
                $entity = $this->getEntity($id);
                if ($this->getAcl()->check($entity, 'edit')) {
                    $entity->set(get_object_vars($attributes));
                    if ($repository->save($entity)) {
                        $idsUpdated[] = $id;
                        $count++;
                    }
                }
            }
        }

        if (array_key_exists('where', $params)) {
            $where = $params['where'];
            $p = array();
            $p['where'] = $where;
            $selectParams = $this->getSelectParams($p, true);

            $collection = $repository->find($selectParams);

            foreach ($collection as $entity) {
                if ($this->getAcl()->check($entity, 'edit')) {
                    $entity->set(get_object_vars($attributes));
                    if ($repository->save($entity)) {
                        $idsUpdated[] = $id;
                        $count++;
                    }
                }
            }

            return array(
                'count' => $count
            );

        }

        return array(
            'count' => $count,
            'ids' => $idsUpdated
        );
    }

    public function massRemove(array $params)
    {
        $idsRemoved = array();
        $repository = $this->getRepository();

        $count = 0;

        if (array_key_exists('ids',$params)) {
            $ids = $params['ids'];
            foreach ($ids as $id) {
                $entity = $this->getEntity($id);
                if ($entity && $this->getAcl()->check($entity, 'remove')) {
                    if ($repository->remove($entity)) {
                        $idsRemoved[] = $id;
                        $count++;
                    }
                }
            }
        }

        if (array_key_exists('where',$params)) {
            $where = $params['where'];
            $p = array();
            $p['where'] = $where;
            $selectParams = $this->getSelectParams($p, true);
            $collection = $repository->find($selectParams);

            foreach ($collection as $entity) {
                if ($this->getAcl()->check($entity, 'remove')) {
                    if ($repository->remove($entity)) {
                        $idsRemoved[] = $id;
                        $count++;
                    }
                }
            }
            return array(
                'count' => $count
            );
        }

        return array(
            'count' => $count,
            'ids' => $idsRemoved
        );
    }

    public function follow($id, $userId = null)
    {
        $entity = $this->getRepository()->get($id);

        if (!$this->getAcl()->check($entity, 'read')) {
            throw new Forbidden();
        }

        if (empty($userId)) {
            $userId = $this->getUser()->id;
        }

        return $this->getStreamService()->followEntity($entity, $userId);
    }

    public function unfollow($id, $userId = null)
    {
        $entity = $this->getRepository()->get($id);

        if (!$this->getAcl()->check($entity, 'read')) {
            throw new Forbidden();
        }

        if (empty($userId)) {
            $userId = $this->getUser()->id;
        }

        return $this->getStreamService()->unfollowEntity($entity, $userId);
    }

    protected function getDuplicateWhereClause(Entity $entity)
    {
        return false;
    }

    public function checkEntityForDuplicate(Entity $entity)
    {
        $where = $this->getDuplicateWhereClause($entity);

        if ($where) {
            $duplicates = $this->getRepository()->where($where)->find();
            if (count($duplicates)) {
                $result = array();
                foreach ($duplicates as $e) {
                    $result[$e->id] = $e->get('name');
                }
                return $result;
            }
        }
        return false;
    }

    public function export(array $params)
    {
        if (array_key_exists('ids', $params)) {
            $ids = $params['ids'];
            $where = array(
                array(
                    'type' => 'in',
                    'field' => 'id',
                    'value' => $ids
                )
            );
            $selectParams = $this->getSelectManager($this->entityName)->getSelectParams(array('where' => $where), true);
        } else if (array_key_exists('where', $params)) {
            $where = $params['where'];

            $p = array();
            $p['where'] = $where;
            $selectParams = $this->getSelectParams($p, true);
        } else {
            throw new BadRequest();
        }

        $collection = $this->getRepository()->find($selectParams);

        $arr = array();

        $collection->toArray();

        $fieldsToSkip = array(
            'modifiedByName',
            'createdByName',
            'modifiedById',
            'createdById',
            'modifiedAt',
            'createdAt',
            'deleted',
        );

        $fields = null;
        foreach ($collection as $entity) {
            if (empty($fields)) {
                $fields = array();
                foreach ($entity->getFields() as $field => $defs) {
                    if (in_array($field, $fieldsToSkip)) {
                        continue;
                    }

                    if (empty($defs['notStorable'])) {
                        $fields[] = $field;
                    } else {
                        if (in_array($defs['type'], array('email', 'phone'))) {
                            $fields[] = $field;
                        } else if ($defs['name'] == 'name') {
                            $fields[] = $field;
                        }
                    }
                }
            }

            $row = array();
            foreach ($fields as $field) {
                $value = $this->getFieldFromEntityForExport($entity, $field);
                $row[$field] = $value;
            }
            $arr[] = $row;
        }

        $delimiter = $this->getPreferences()->get('exportDelimiter');
        if (empty($delimiter)) {
            $delimiter = $this->getConfig()->get('exportDelimiter', ';');
        }

        $fp = fopen('php://temp', 'w');
        fputcsv($fp, array_keys($arr[0]), $delimiter);
        foreach ($arr as $row) {
            fputcsv($fp, $row, $delimiter);
        }
        rewind($fp);
        $csv = stream_get_contents($fp);
        fclose($fp);

        $fileName = "Export_{$this->entityName}.csv";

        $attachment = $this->getEntityManager()->getEntity('Attachment');
        $attachment->set('name', $fileName);
        $attachment->set('role', 'Export File');
        $attachment->set('type', 'text/csv');

        $this->getEntityManager()->saveEntity($attachment);

        if (!empty($attachment->id)) {
            $this->getInjection('fileManager')->putContents('data/upload/' . $attachment->id, $csv);
            // TODO cron job to remove file
            return $attachment->id;
        }
        throw new Error();
    }

    protected function getFieldFromEntityForExport(Entity $entity, $field)
    {
        $defs = $entity->getFields();
        if (!empty($defs[$field]) && !empty($defs[$field]['type'])) {
            $type = $defs[$field]['type'];
            switch ($type) {
                case 'jsonArray':
                    $value = $entity->get($field);
                    if (is_array($value)) {
                        return implode(',', $value);
                    } else {
                        return null;
                    }
                    break;
                case 'password':
                    return null;
                    break;
            }
        }
        return $entity->get($field);
    }

    public function prepareEntityForOutput(Entity $entity)
    {
        foreach ($this->internalFields as $field) {
            $entity->clear($field);
        }
    }

    public function merge($id, array $sourceIds = array())
    {
        if (empty($id)) {
            throw new Error();
        }

        $entity = $this->getEntity($id);

        if (!$entity) {
            throw new NotFound();
        }

        if (!$this->getAcl()->check($entity, 'edit')) {
            throw new Forbidden();
        }

        $pdo = $this->getEntityManager()->getPDO();

        $sourceList = array();
        foreach ($sourceIds as $sourceId) {
            $source = $this->getEntity($sourceId);
            $sourceList[] = $source;
            if (!$this->getAcl()->check($source, 'edit') || !$this->getAcl()->check($source, 'delete')) {
                throw new Forbidden();
            }
        }

        foreach ($sourceList as $source) {
            $sql = "
                UPDATE `note`
                    SET
                        `parent_id` = " . $pdo->quote($entity->id) . ",
                        `parent_type` = " . $pdo->quote($entity->getEntityType()) . "
                WHERE
                    `type` IN ('Post', 'EmailSent', 'EmailReceived') AND
                    `parent_id` = " . $pdo->quote($source->id) . " AND
                    `parent_type` = ".$pdo->quote($source->getEntityType())." AND
                    `deleted` = 0
            ";
            $pdo->query($sql);
        }

        $repository = $this->getEntityManager()->getRepository($entity->getEntityType());

        foreach ($sourceList as $source) {
            foreach ($this->mergeLinkList as $link) {
                $linkedList = $repository->findRelated($source, $link);
                foreach ($linkedList as $linked) {
                    $repository->relate($entity, $link, $linked);
                }
            }
        }


        foreach ($sourceList as $source) {
            $this->getEntityManager()->removeEntity($source);
        }

        return true;
    }
}

