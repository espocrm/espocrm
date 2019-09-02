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

use \Espo\ORM\Entity;

use \Espo\Core\Exceptions\Error;
use \Espo\Core\Exceptions\Forbidden;
use \Espo\Core\Exceptions\BadRequest;
use \Espo\Core\Exceptions\Conflict;
use \Espo\Core\Exceptions\NotFound;
use \Espo\Core\Exceptions\NotFoundSilent;
use \Espo\Core\Exceptions\ForbiddenSilent;
use \Espo\Core\Exceptions\ConflictSilent;

use \Espo\Core\Utils\Util;

class Record extends \Espo\Core\Services\Base
{
    protected $dependencyList = [
        'entityManager',
        'user',
        'metadata',
        'acl',
        'aclManager',
        'config',
        'serviceFactory',
        'fileManager',
        'selectManagerFactory',
        'fileStorageManager',
        'injectableFactory',
        'fieldManagerUtil',
        'container',
    ];

    protected $getEntityBeforeUpdate = false;

    protected $entityName;

    protected $entityType;

    private $streamService;

    protected $notFilteringAttributeList = []; // TODO maybe remove it

    protected $forbiddenAttributeList = [];

    protected $internalAttributeList = [];

    protected $onlyAdminAttributeList = [];

    protected $readOnlyAttributeList = [];

    protected $nonAdminReadOnlyAttributeList = [];

    protected $forbiddenLinkList = [];

    protected $internalLinkList = [];

    protected $readOnlyLinkList = [];

    protected $nonAdminReadOnlyLinkList = [];

    protected $onlyAdminLinkList = [];

    protected $linkParams = [];

    protected $linkSelectParams = [];

    protected $noEditAccessRequiredLinkList = [];

    protected $exportSkipAttributeList = [];

    protected $exportAdditionalAttributeList = [];

    protected $exportAllowedAttributeList = [];

    protected $checkForDuplicatesInUpdate = false;

    protected $actionHistoryDisabled = false;

    protected $duplicatingLinkList = [];

    protected $listCountQueryDisabled = false;

    protected $maxSelectTextAttributeLength = null;

    protected $maxSelectTextAttributeLengthDisabled = false;

    protected $skipSelectTextAttributes = false;

    protected $selectAttributeList = null;

    protected $mandatorySelectAttributeList = [];

    protected $forceSelectAllAttributes = false;

    protected $validateSkipFieldList = [];

    protected $findDuplicatesSelectAttributeList = ['id', 'name'];

    protected $duplicateIgnoreFieldList = [];

    protected $duplicateIgnoreAttributeList = [];

    const MAX_SELECT_TEXT_ATTRIBUTE_LENGTH = 5000;

    const FOLLOWERS_LIMIT = 4;

    public function __construct()
    {
        parent::__construct();
        if (empty($this->entityType)) {
            $name = get_class($this);
            if (preg_match('@\\\\([\w]+)$@', $name, $matches)) {
                $name = $matches[1];
            }
            if ($name != 'Record') {
                $this->entityType = Util::normilizeScopeName($name);
            }
        }
        $this->entityName = $this->entityType;
    }

    public function prepare()
    {
        parent::prepare();

        $aclManager = $this->getInjection('aclManager');

        foreach ($aclManager->getScopeRestrictedAttributeList($this->entityType, 'forbidden') as $item) {
            if (!in_array($item, $this->forbiddenAttributeList)) $this->forbiddenAttributeList[] = $item;
        }
        foreach ($aclManager->getScopeRestrictedAttributeList($this->entityType, 'internal') as $item) {
            if (!in_array($item, $this->internalAttributeList)) $this->internalAttributeList[] = $item;
        }
        foreach ($aclManager->getScopeRestrictedAttributeList($this->entityType, 'onlyAdmin') as $item) {
            if (!in_array($item, $this->onlyAdminAttributeList)) $this->onlyAdminAttributeList[] = $item;
        }
        foreach ($aclManager->getScopeRestrictedAttributeList($this->entityType, 'readOnly') as $item) {
            if (!in_array($item, $this->readOnlyAttributeList)) $this->readOnlyAttributeList[] = $item;
        }
        foreach ($aclManager->getScopeRestrictedAttributeList($this->entityType, 'nonAdminReadOnly') as $item) {
            if (!in_array($item, $this->nonAdminReadOnlyAttributeList)) $this->nonAdminReadOnlyAttributeList[] = $item;
        }

        foreach ($aclManager->getScopeRestrictedLinkList($this->entityType, 'forbidden') as $item) {
            if (!in_array($item, $this->forbiddenLinkList)) $this->forbiddenLinkList[] = $item;
        }
        foreach ($aclManager->getScopeRestrictedLinkList($this->entityType, 'internal') as $item) {
            if (!in_array($item, $this->internalLinkList)) $this->internalLinkList[] = $item;
        }
        foreach ($aclManager->getScopeRestrictedLinkList($this->entityType, 'onlyAdmin') as $item) {
            if (!in_array($item, $this->onlyAdminLinkList)) $this->onlyAdminLinkList[] = $item;
        }
        foreach ($aclManager->getScopeRestrictedLinkList($this->entityType, 'readOnly') as $item) {
            if (!in_array($item, $this->readOnlyLinkList)) $this->readOnlyLinkList[] = $item;
        }
        foreach ($aclManager->getScopeRestrictedLinkList($this->entityType, 'nonAdminReadOnly') as $item) {
            if (!in_array($item, $this->nonAdminReadOnlyLinkList)) $this->nonAdminReadOnlyLinkList[] = $item;
        }
    }

    public function setEntityType($entityType)
    {
        $this->entityType = $entityType;
        $this->entityName = $entityType;
    }

    public function getEntityType()
    {
        return $this->entityType;
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
        return $this->getInjection('acl');
    }

    protected function getAclManager()
    {
        return $this->getInjection('aclManager');
    }

    protected function getFileManager()
    {
        return $this->getInjection('fileManager');
    }

    protected function getMetadata()
    {
        return $this->getInjection('metadata');
    }

    protected function getFieldManagerUtil()
    {
        return $this->getInjection('fieldManagerUtil');
    }

    protected function getRepository()
    {
        return $this->getEntityManager()->getRepository($this->entityType);
    }

    protected function getRecordService($name)
    {
        if ($this->getServiceFactory()->checkExists($name)) {
            $service = $this->getServiceFactory()->create($name);
        } else {
            $service = $this->getServiceFactory()->create('Record');
            $service->setEntityType($name);
        }

        return $service;
    }

    protected function processActionHistoryRecord($action, Entity $entity)
    {
        if ($this->actionHistoryDisabled) return;
        if ($this->getConfig()->get('actionHistoryDisabled')) return;

        $historyRecord = $this->getEntityManager()->getEntity('ActionHistoryRecord');

        $historyRecord->set('action', $action);
        $historyRecord->set('userId', $this->getUser()->id);
        $historyRecord->set('authTokenId', $this->getUser()->get('authTokenId'));
        $historyRecord->set('ipAddress', $this->getUser()->get('ipAddress'));
        $historyRecord->set('authLogRecordId', $this->getUser()->get('authLogRecordId'));

        if ($entity) {
            $historyRecord->set([
                'targetType' => $entity->getEntityType(),
                'targetId' => $entity->id
            ]);
        }

        $this->getEntityManager()->saveEntity($historyRecord);
    }

    public function readEntity($id) //TODO Remove in 5.8
    {
        return $this->read($id);
    }

    public function read($id)
    {
        if (empty($id)) {
            throw new Error();
        }
        $entity = $this->getEntity($id);

        if (!$entity) throw new NotFoundSilent("Record does not exist.");

        $this->processActionHistoryRecord('read', $entity);

        return $entity;
    }

    public function getEntity($id = null)
    {
        if (!is_null($id)) {
            $selectParams = [];
            if ($this->getUser()->isAdmin()) {
                $selectParams['withDeleted'] = true;
            }
            $entity = $this->getRepository()->getById($id, $selectParams);
        } else {
            $entity = $this->getRepository()->getNew();
        }

        if ($entity && !is_null($id)) {
            $this->loadAdditionalFields($entity);
            if (!$this->getAcl()->check($entity, 'read')) throw new ForbiddenSilent();
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

    protected function loadFollowers(Entity $entity)
    {
        if ($this->getUser()->isPortal()) return;
        if (!$this->getMetadata()->get(['scopes', $entity->getEntityType(), 'stream'])) return;

        if (!$this->getAcl()->check($entity, 'stream')) return;

        $data = $this->getStreamService()->getEntityFollowers($entity, 0, self::FOLLOWERS_LIMIT);
        if ($data) {
            $entity->set('followersIds', $data['idList']);
            $entity->set('followersNames', $data['nameMap']);
        }
    }

    protected function loadLinkMultipleFields(Entity $entity)
    {
        $fieldDefs = $this->getMetadata()->get('entityDefs.' . $entity->getEntityType() . '.fields', array());
        foreach ($fieldDefs as $field => $defs) {
            if (isset($defs['type']) && in_array($defs['type'], ['linkMultiple', 'attachmentMultiple']) && empty($defs['noLoad'])) {
                $columns = null;
                if (!empty($defs['columns'])) {
                    $columns = $defs['columns'];
                }
                $entity->loadLinkMultipleField($field, $columns);
            }
        }
    }

    public function loadLinkMultipleFieldsForList(Entity $entity, $selectAttributeList)
    {
        foreach ($selectAttributeList as $attribute) {
            if ($entity->getAttributeParam($attribute, 'isLinkMultipleIdList')) {
                $field = $entity->getAttributeParam($attribute, 'relation');
                if (!$field) continue;
                if ($entity->has($attribute)) continue;
                $entity->loadLinkMultipleField($field);
            }
        }
    }

    protected function loadLinkFields(Entity $entity)
    {
        $fieldDefs = $this->getMetadata()->get('entityDefs.' . $entity->getEntityType() . '.fields', array());
        $linkDefs = $this->getMetadata()->get('entityDefs.' . $entity->getEntityType() . '.links', array());
        foreach ($fieldDefs as $field => $defs) {
            if (isset($defs['type']) && $defs['type'] === 'link') {
                if (!empty($defs['noLoad'])) continue;
                if (empty($linkDefs[$field])) continue;
                if (empty($linkDefs[$field]['type'])) continue;
                if ($linkDefs[$field]['type'] !== 'hasOne') continue;

                $entity->loadLinkField($field);
            }
        }
    }

    protected function loadParentNameFields(Entity $entity)
    {
        $fieldDefs = $this->getMetadata()->get('entityDefs.' . $entity->getEntityType() . '.fields', array());
        foreach ($fieldDefs as $field => $defs) {
            if (isset($defs['type']) && $defs['type'] == 'linkParent') {
                $parentId = $entity->get($field . 'Id');
                $parentType = $entity->get($field . 'Type');
                $entity->loadParentNameField($field);
            }
        }
    }

    protected function loadNotJoinedLinkFields(Entity $entity)
    {
        $linkDefs = $this->getMetadata()->get('entityDefs.' . $entity->getEntityType() . '.links', array());
        foreach ($linkDefs as $link => $defs) {
            if (isset($defs['type']) && $defs['type'] == 'belongsTo') {
                if (!empty($defs['noJoin']) && !empty($defs['entity'])) {
                    $nameAttribute = $link . 'Name';
                    $idAttribute = $link . 'Id';
                    if ($entity->hasAttribute($nameAttribute) && $entity->hasAttribute($idAttribute)) {
                        $id = $entity->get($idAttribute);
                    } else {
                        continue;
                    }
                    if (!empty($defs['entity'])) {
                        $scope = $defs['entity'];
                        if ($this->getEntityManager()->hasRepository($scope)) {
                            $foreignEntity = $this->getEntityManager()->getRepository($scope)
                                ->select(['id', 'name'])
                                ->where(['id' => $id])
                                ->findOne();
                            if ($foreignEntity) {
                                $entity->set($nameAttribute, $foreignEntity->get('name'));
                            } else {
                                $entity->set($nameAttribute, null);
                            }
                        }
                    }
                }
            }
        }
    }

    protected function loadEmptyNameLinkFields(Entity $entity)
    {
        $linkDefs = $this->getMetadata()->get(['entityDefs', $entity->getEntityType(), 'links'], []);
        foreach ($linkDefs as $link => $defs) {
            if (!isset($defs['type'])) continue;
            if ($defs['type'] != 'belongsTo') continue;

            $nameAttribute = $link . 'Name';
            $idAttribute = $link . 'Id';

            if ($entity->get($idAttribute) && !$entity->get($nameAttribute)) {
                $id = $entity->get($idAttribute);
                if (empty($defs['entity'])) continue;
                $scope = $defs['entity'];
                if ($this->getEntityManager()->hasRepository($scope)) {
                    $foreignEntity = $this->getEntityManager()->getRepository($scope)
                        ->select(['id', 'name'])
                        ->where(['id' => $id])
                        ->findOne();
                    if ($foreignEntity) {
                        $entity->set($nameAttribute, $foreignEntity->get('name'));
                    }
                }
            }
        }
    }

    public function loadAdditionalFields(Entity $entity)
    {
        $this->loadLinkFields($entity);
        $this->loadLinkMultipleFields($entity);
        $this->loadParentNameFields($entity);
        $this->loadIsFollowed($entity);
        $this->loadFollowers($entity);
        $this->loadEmailAddressField($entity);
        $this->loadPhoneNumberField($entity);
        $this->loadNotJoinedLinkFields($entity);
        $this->loadEmptyNameLinkFields($entity);
    }

    public function loadAdditionalFieldsForList(Entity $entity)
    {
        $this->loadParentNameFields($entity);
    }

    public function loadAdditionalFieldsForExport(Entity $entity)
    {
    }

    protected function loadEmailAddressField(Entity $entity)
    {
        $fieldDefs = $this->getMetadata()->get('entityDefs.' . $entity->getEntityType() . '.fields', array());
        if (!empty($fieldDefs['emailAddress']) && $fieldDefs['emailAddress']['type'] == 'email') {
            $dataAttributeName = 'emailAddressData';
            $emailAddressData = $this->getEntityManager()->getRepository('EmailAddress')->getEmailAddressData($entity);
            $entity->set($dataAttributeName, $emailAddressData);
            $entity->setFetched($dataAttributeName, $emailAddressData);
        }
    }

    protected function loadPhoneNumberField(Entity $entity)
    {
        $fieldDefs = $this->getMetadata()->get('entityDefs.' . $entity->getEntityType() . '.fields', array());
        if (!empty($fieldDefs['phoneNumber']) && $fieldDefs['phoneNumber']['type'] == 'phone') {
            $dataAttributeName = 'phoneNumberData';
            $phoneNumberData = $this->getEntityManager()->getRepository('PhoneNumber')->getPhoneNumberData($entity);
            $entity->set($dataAttributeName, $phoneNumberData);
            $entity->setFetched($dataAttributeName, $phoneNumberData);
        }
    }

    protected function getSelectManager($entityType = null)
    {
        if (!$entityType) {
            $entityType = $this->getEntityType();
        }
        return $this->getSelectManagerFactory()->create($entityType);
    }

    protected function storeEntity(Entity $entity)
    {
        return $this->getRepository()->save($entity);
    }

    public function processValidation(Entity $entity, $data)
    {
        $fieldList = $this->getFieldManagerUtil()->getEntityTypeFieldList($this->entityType);

        foreach ($fieldList as $field) {
            if (in_array($field, $this->validateSkipFieldList)) continue;
            if (!$entity->isNew()) {
                if (!$this->isFieldSetInData($data, $field)) continue;
            }
            $this->processValidationField($entity, $field, $data);
        }
    }

    protected function processValidationField(Entity $entity, $field, $data)
    {
        $fieldType = $this->getFieldManagerUtil()->getEntityTypeFieldParam($this->entityType, $field, 'type');
        $validationList = $this->getMetadata()->get(['fields', $fieldType, 'validationList'], []);
        $mandatoryValidationList = $this->getMetadata()->get(['fields', $fieldType, 'mandatoryValidationList'], []);
        $fieldValidatorManager = $this->getInjection('container')->get('fieldValidatorManager');


        foreach ($validationList as $type) {
            $value = $this->getFieldManagerUtil()->getEntityTypeFieldParam($this->entityType, $field, $type);
            if (is_null($value)) {
                if (!in_array($type, $mandatoryValidationList)) {
                    continue;
                }
            }

            $skipPropertyName = 'validate' . ucfirst($type) . 'SkipFieldList';
            if (property_exists($this, $skipPropertyName)) {
                $skipList = $this->$skipPropertyName;
                if (!in_array($type, $skipList)) {
                    continue;
                }
            }
            if (!$fieldValidatorManager->check($entity, $field, $type, $data)) {
                throw new BadRequest("Not valid data. Field: '{$field}', type: {$type}.");
            }
        }
    }

    protected function isFieldSetInData($data, $field)
    {
        $attributeList = $this->getFieldManagerUtil()->getActualAttributeList($this->entityType, $field);
        $isSet = false;
        foreach ($attributeList as $attribute) {
            if (property_exists($data, $attribute)) {
                $isSet = true;
                break;
            }
        }
        return $isSet;
    }

    public function checkAssignment(Entity $entity)
    {
        if (!$this->isPermittedAssignedUser($entity)) {
            return false;
        }
        if (!$this->isPermittedTeams($entity)) {
            return false;
        }
        if ($entity->hasLinkMultipleField('assignedUsers')) {
            if (!$this->isPermittedAssignedUsers($entity)) {
                return false;
            }
        }
        return true;
    }

    public function isPermittedAssignedUsers(Entity $entity)
    {
        if (!$entity->hasLinkMultipleField('assignedUsers')) {
            return true;
        }

        if ($this->getUser()->isPortal()) {
            if (count($entity->getLinkMultipleIdList('assignedUsers')) === 0) {
                return true;
            }
        }

        $assignmentPermission = $this->getAcl()->get('assignmentPermission');

        if ($assignmentPermission === true || $assignmentPermission === 'yes' || !in_array($assignmentPermission, ['team', 'no'])) {
            return true;
        }

        $toProcess = false;

        if (!$entity->isNew()) {
            $userIdList = $entity->getLinkMultipleIdList('assignedUsers');
            if ($entity->isAttributeChanged('assignedUsersIds')) {
                $toProcess = true;
            }
        } else {
            $toProcess = true;
        }

        $userIdList = $entity->getLinkMultipleIdList('assignedUsers');

        if ($toProcess) {
            if (empty($userIdList)) {
                if ($assignmentPermission == 'no') {
                    return false;
                }
                return true;
            }
            $fetchedAssignedUserIdList = $entity->getFetched('assignedUsersIds');

            if ($assignmentPermission == 'no') {
                foreach ($userIdList as $userId) {
                    if (!$entity->isNew() && in_array($userId, $fetchedAssignedUserIdList)) continue;
                    if ($this->getUser()->id != $userId) {
                        return false;
                    }
                }
            } else if ($assignmentPermission == 'team') {
                $teamIdList = $this->getUser()->getLinkMultipleIdList('teams');
                foreach ($userIdList as $userId) {
                    if (!$entity->isNew() && in_array($userId, $fetchedAssignedUserIdList)) continue;
                    if (!$this->getEntityManager()->getRepository('User')->checkBelongsToAnyOfTeams($userId, $teamIdList)) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    public function isPermittedAssignedUser(Entity $entity)
    {
        if (!$entity->hasAttribute('assignedUserId')) {
            return true;
        }

        $assignedUserId = $entity->get('assignedUserId');

        if ($this->getUser()->isPortal()) {
            if (!$entity->isAttributeChanged('assignedUserId') && empty($assignedUserId)) {
                return true;
            }
        }

        $assignmentPermission = $this->getAcl()->get('assignmentPermission');

        if ($assignmentPermission === true || $assignmentPermission === 'yes' || !in_array($assignmentPermission, ['team', 'no'])) {
            return true;
        }

        $toProcess = false;

        if (!$entity->isNew()) {
            if ($entity->isAttributeChanged('assignedUserId')) {
                $toProcess = true;
            }
        } else {
            $toProcess = true;
        }

        if ($toProcess) {
            if (empty($assignedUserId)) {
                if ($assignmentPermission == 'no') {
                    return false;
                }
                return true;
            }
            if ($assignmentPermission == 'no') {
                if ($this->getUser()->id != $assignedUserId) {
                    return false;
                }
            } else if ($assignmentPermission == 'team') {
                $teamIdList = $this->getUser()->get('teamsIds');
                if (!$this->getEntityManager()->getRepository('User')->checkBelongsToAnyOfTeams($assignedUserId, $teamIdList)) {
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

        if (!$entity->hasLinkMultipleField('teams')) {
            return true;
        }
        $teamIdList = $entity->getLinkMultipleIdList('teams');
        if (empty($teamIdList)) {
            if ($assignmentPermission === 'team') {
                if ($entity->hasLinkMultipleField('assignedUsers')) {
                    $assignedUserIdList = $entity->getLinkMultipleIdList('assignedUsers');
                    if (empty($assignedUserIdList)) {
                        return false;
                    }
                } else if ($entity->hasAttribute('assignedUserId')) {
                    if (!$entity->get('assignedUserId')) {
                        return false;
                    }
                }
            }
            return true;
        }

        $newIdList = [];

        if (!$entity->isNew()) {
            $existingIdList = [];
            foreach ($entity->get('teams') as $team) {
                $existingIdList[] = $team->id;
            }
            foreach ($teamIdList as $id) {
                if (!in_array($id, $existingIdList)) {
                    $newIdList[] = $id;
                }
            }
        } else {
            $newIdList = $teamIdList;
        }

        if (empty($newIdList)) {
            return true;
        }

        $userTeamIdList = $this->getUser()->getLinkMultipleIdList('teams');

        foreach ($newIdList as $id) {
            if (!in_array($id, $userTeamIdList)) {
                return false;
            }
        }
        return true;
    }


    protected function stripTags($string)
    {
        return strip_tags($string, '<a><img><p><br><span><ol><ul><li><blockquote><pre><h1><h2><h3><h4><h5><table><tr><td><th><thead><tbody><i><b>');
    }

    protected function filterInputAttribute($attribute, $value)
    {
        if (in_array($attribute, $this->notFilteringAttributeList)) {
            return $value;
        }
        $methodName = 'filterInputAttribute' . ucfirst($attribute);
        if (method_exists($this, $methodName)) {
            $value = $this->$methodName($value);
        }
        return $value;
    }

    protected function filterInput($data)
    {
        foreach ($this->readOnlyAttributeList as $attribute) {
            unset($data->$attribute);
        }

        foreach ($this->forbiddenAttributeList as $attribute) {
            unset($data->$attribute);
        }

        foreach ($data as $key => $value) {
            $data->$key = $this->filterInputAttribute($key, $data->$key);
        }

        if (!$this->getUser()->isAdmin()) {
            foreach ($this->onlyAdminAttributeList as $attribute) {
                unset($data->$attribute);
            }
        }

        foreach ($this->getAcl()->getScopeForbiddenAttributeList($this->entityType, 'edit') as $attribute) {
            unset($data->$attribute);
        }

        if (!$this->getUser()->isAdmin()) {
            foreach ($this->nonAdminReadOnlyAttributeList as $attribute) {
                unset($data->$attribute);
            }
        }
    }

    protected function filterCreateInput($data)
    {
    }

    protected function filterUpdateInput($data)
    {
    }

    protected function handleInput($data)
    {
    }

    protected function processDuplicateCheck(Entity $entity, $data)
    {
        if (empty($data->skipDuplicateCheck) && empty($data->forceDuplicate)) {
            $duplicateList = $this->findDuplicates($entity, $data);
            if (!empty($duplicateList)) {
                $data = [];
                foreach ($duplicateList as $e) {
                    $data[$e->id] = $e->getValueMap();
                }
                $reason = [
                    'reason' => 'Duplicate',
                    'data' => $data
                ];
                throw new ConflictSilent(json_encode($reason));
            }
        }
    }

    public function populateDefaults(Entity $entity, $data)
    {
        if (!$this->getUser()->isPortal()) {
            $forbiddenFieldList = null;
            if ($entity->hasAttribute('assignedUserId')) {
                $forbiddenFieldList = $this->getAcl()->getScopeForbiddenFieldList($this->entityType, 'edit');
                if (in_array('assignedUser', $forbiddenFieldList)) {
                    $entity->set('assignedUserId', $this->getUser()->id);
                    $entity->set('assignedUserName', $this->getUser()->get('name'));
                }
            }

            if ($entity->hasLinkMultipleField('teams')) {
                if (is_null($forbiddenFieldList)) {
                    $forbiddenFieldList = $this->getAcl()->getScopeForbiddenFieldList($this->entityType, 'edit');
                }
                if (in_array('teams', $forbiddenFieldList)) {
                    if ($this->getUser()->get('defaultTeamId')) {
                        $defaultTeamId = $this->getUser()->get('defaultTeamId');
                        $entity->addLinkMultipleId('teams', $defaultTeamId);
                        $teamsNames = $entity->get('teamsNames');
                        if (!$teamsNames || !is_object($teamsNames)) {
                            $teamsNames = (object) [];
                        }
                        $teamsNames->$defaultTeamId = $this->getUser()->get('defaultTeamName');
                        $entity->set('teamsNames', $teamsNames);
                    }
                }
            }
        }

        foreach ($this->getFieldManagerUtil()->getEntityTypeFieldList($this->entityType) as $field) {
            $type = $this->getFieldManagerUtil()->getEntityTypeFieldParam($this->entityType, $field, 'type');
            if ($type === 'currency') {
                if ($entity->get($field) && !$entity->get($field . 'Currency')) {
                    $entity->set($field . 'Currency', $this->getConfig()->get('defaultCurrency'));
                }
            }
        }
    }

    public function createEntity($data) //TODO Remove in 5.8
    {
        return $this->create($data);
    }

    public function create($data)
    {
        if (!$this->getAcl()->check($this->getEntityType(), 'create')) throw new ForbiddenSilent();

        $entity = $this->getRepository()->get();

        $this->filterInput($data);
        $this->filterCreateInput($data);
        $this->handleInput($data);

        unset($data->id);
        unset($data->modifiedById);
        unset($data->modifiedByName);
        unset($data->modifiedAt);
        unset($data->createdById);
        unset($data->createdByName);
        unset($data->createdAt);

        $entity->set($data);

        $this->populateDefaults($entity, $data);

        if (!$this->getAcl()->check($entity, 'create')) throw new ForbiddenSilent();

        $this->processValidation($entity, $data);

        if (!$this->checkAssignment($entity)) throw new Forbidden('Assignment permission failure.');

        $this->processDuplicateCheck($entity, $data);

        $this->beforeCreateEntity($entity, $data);

        if ($this->storeEntity($entity)) {
            $this->afterCreateEntity($entity, $data);
            $this->afterCreateProcessDuplicating($entity, $data);
            $this->loadAdditionalFields($entity);
            $this->prepareEntityForOutput($entity);
            $this->processActionHistoryRecord('create', $entity);
            return $entity;
        }

        throw new Error();
    }

    public function updateEntity($id, $data) //TODO Remove in 5.8
    {
        return $this->update($id, $data);
    }

    public function update($id, $data)
    {
        unset($data->deleted);

        if (empty($id)) throw new BadRequest();

        $this->filterInput($data);
        $this->filterUpdateInput($data);
        $this->handleInput($data);

        unset($data->id);
        unset($data->modifiedById);
        unset($data->modifiedByName);
        unset($data->modifiedAt);
        unset($data->createdById);
        unset($data->createdByName);
        unset($data->createdAt);

        if ($this->getEntityBeforeUpdate) {
            $entity = $this->getEntity($id);
        } else {
            $entity = $this->getRepository()->get($id);
        }

        if (!$entity) throw new NotFound();

        if (!$this->getAcl()->check($entity, 'edit')) throw new ForbiddenSilent();

        $entity->set($data);

        $this->processValidation($entity, $data);

        if (!$this->checkAssignment($entity)) throw new Forbidden("Assignment permission failure.");

        $this->beforeUpdateEntity($entity, $data);

        if ($this->checkForDuplicatesInUpdate) {
            $this->processDuplicateCheck($entity, $data);
        }

        if ($this->storeEntity($entity)) {
            $this->afterUpdateEntity($entity, $data);
            $this->prepareEntityForOutput($entity);
            $this->processActionHistoryRecord('update', $entity);
            return $entity;
        }

        throw new Error();
    }

    protected function beforeCreateEntity(Entity $entity, $data)
    {
    }

    protected function afterCreateEntity(Entity $entity, $data)
    {
    }

    protected function beforeUpdateEntity(Entity $entity, $data)
    {
    }

    protected function afterUpdateEntity(Entity $entity, $data)
    {
    }

    protected function beforeDeleteEntity(Entity $entity)
    {
    }

    protected function afterDeleteEntity(Entity $entity)
    {
    }

    protected function afterMassUpdate(array $idList, $data)
    {
    }

    protected function afterMassDelete(array $idList)
    {
    }

    public function deleteEntity($id)  //TODO Remove in 5.8
    {
        return $this->delete($id);
    }

    public function delete($id)
    {
        if (empty($id)) throw new BadRequest();

        $entity = $this->getRepository()->get($id);

        if (!$entity) throw new NotFound();

        if (!$this->getAcl()->check($entity, 'delete')) throw new ForbiddenSilent();

        $this->beforeDeleteEntity($entity);

        $result = $this->getRepository()->remove($entity);
        if ($result) {
            $this->afterDeleteEntity($entity);

            $this->processActionHistoryRecord('delete', $entity);

            return $result;
        }
    }

    protected function getSelectParams($params)
    {
        $selectParams = $this->getSelectManager($this->entityType)->getSelectParams($params, true, true, true);

        return $selectParams;
    }

    public function findEntities($params)
    {
        return $this->find($params);
    }

    public function find($params)
    {
        $disableCount = false;
        if (
            $this->listCountQueryDisabled
            ||
            in_array($this->entityType, $this->getConfig()->get('disabledCountQueryEntityList', []))
        ) {
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

        $selectParams['maxTextColumnsLength'] = $this->getMaxSelectTextAttributeLength();

        $selectAttributeList = $this->getSelectAttributeList($params);
        if ($selectAttributeList) {
            $selectParams['select'] = $selectAttributeList;
        } else {
            $selectParams['skipTextColumns'] = $this->isSkipSelectTextAttributes();
        }

        $collection = $this->getRepository()->find($selectParams);

        foreach ($collection as $e) {
            $this->loadAdditionalFieldsForList($e);
            if (!empty($params['loadAdditionalFields'])) {
                $this->loadAdditionalFields($e);
            }
            if (!empty($selectAttributeList)) {
                $this->loadLinkMultipleFieldsForList($e, $selectAttributeList);
            }
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

        return [
            'total' => $total,
            'collection' => $collection,
        ];
    }

    public function getListKanban($params)
    {
        $disableCount = false;
        if (
            $this->listCountQueryDisabled
            ||
            in_array($this->entityType, $this->getConfig()->get('disabledCountQueryEntityList', []))
        ) {
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

        $selectParams['maxTextColumnsLength'] = $this->getMaxSelectTextAttributeLength();

        $selectAttributeList = $this->getSelectAttributeList($params);
        if ($selectAttributeList) {
            $selectParams['select'] = $selectAttributeList;
        } else {
            $selectParams['skipTextColumns'] = $this->isSkipSelectTextAttributes();
        }

        $collection = new \Espo\ORM\EntityCollection([], $this->entityType);

        $statusField = $this->getMetadata()->get(['scopes', $this->entityType, 'statusField']);
        if (!$statusField) {
            throw new Error("No status field for entity type '{$this->entityType}'.");
        }

        $statusList = $this->getMetadata()->get(['entityDefs', $this->entityType, 'fields', $statusField, 'options']);
        if (empty($statusList)) {
            throw new Error("No options for status field for entity type '{$this->entityType}'.");
        }

        $statusIgnoreList = $this->getMetadata()->get(['scopes', $this->entityType, 'kanbanStatusIgnoreList'], []);

        $additionalData = (object) [
            'groupList' => []
        ];

        foreach ($statusList as $status) {
            if (in_array($status, $statusIgnoreList)) continue;
            if (!$status) continue;

            $selectParamsSub = $selectParams;
            $selectParamsSub['whereClause'][] = [
                $statusField => $status
            ];

            $o = (object) [
                'name' => $status
            ];

            $collectionSub = $this->getRepository()->find($selectParamsSub);

            if (!$disableCount) {
                $totalSub = $this->getRepository()->count($selectParamsSub);
            } else {
                if ($maxSize && count($collectionSub) > $maxSize) {
                    $totalSub = -1;
                    unset($collectionSub[count($collectionSub) - 1]);
                } else {
                    $totalSub = -2;
                }
            }

            foreach ($collectionSub as $e) {
                $this->loadAdditionalFieldsForList($e);
                if (!empty($params['loadAdditionalFields'])) {
                    $this->loadAdditionalFields($e);
                }
                if (!empty($selectAttributeList)) {
                    $this->loadLinkMultipleFieldsForList($e, $selectAttributeList);
                }
                $this->prepareEntityForOutput($e);

                $collection[] = $e;
            }

            $o->total = $totalSub;
            $o->list = $collectionSub->getValueMapList();

            $additionalData->groupList[] = $o;
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

        return (object) [
            'total' => $total,
            'collection' => $collection,
            'additionalData' => $additionalData
        ];
    }

    public function restoreDeleted(string $id)
    {
        if (!$this->getUser()->isAdmin()) throw new Forbidden();

        $entity = $this->getRepository()->getById($id, ['withDeleted' => true]);

        if (!$entity) throw new NotFound();
        if (!$entity->get('deleted')) throw new Forbidden();

        $this->getRepository()->restoreDeleted($entity->id);

        return true;
    }

    public function getMaxSelectTextAttributeLength()
    {
        if (!$this->maxSelectTextAttributeLengthDisabled) {
            if ($this->maxSelectTextAttributeLength) {
                return $this->maxSelectTextAttributeLength;
            } else {
                return $this->getConfig()->get('maxSelectTextAttributeLengthForList', self::MAX_SELECT_TEXT_ATTRIBUTE_LENGTH);
            }
        }
        return null;
    }

    public function isSkipSelectTextAttributes()
    {
        return $this->skipSelectTextAttributes;
    }

    public function findLinkedEntities($id, $link, $params)
    {
        return $this->findLinked($id, $link, $params);
    }

    public function findLinked($id, $link, $params)
    {
        $entity = $this->getRepository()->get($id);
        if (!$entity) {
            throw new NotFound();
        }
        if (!$this->getAcl()->check($entity, 'read')) {
            throw new Forbidden();
        }
        if (empty($link)) {
            throw new Error();
        }

        if (in_array($link, $this->forbiddenLinkList)) {
            throw new Forbidden();
        }

        if (in_array($link, $this->internalLinkList)) {
            throw new Forbidden();
        }

        if (!$this->getUser()->isAdmin() && in_array($link, $this->onlyAdminLinkList)) {
            throw new Forbidden();
        }

        $methodName = 'findLinked' . ucfirst($link);
        if ($link !== 'entities' && method_exists($this, $methodName)) {
            return $this->$methodName($id, $params);
        }

        $methodName = 'findLinkedEntities' . ucfirst($link);
        if (method_exists($this, $methodName)) {
            return $this->$methodName($id, $params);
        }

        $foreignEntityName = $entity->relations[$link]['entity'];

        $linkParams = $this->linkParams[$link] ?? [];
        $skipAcl = $linkParams['skipAcl'] ?? false;

        if (!$skipAcl) {
            if (!$this->getAcl()->check($foreignEntityName, 'read')) {
                throw new Forbidden();
            }
        }

        $recordService = $this->getRecordService($foreignEntityName);

        $disableCount = false;
        if (
            in_array($this->entityType, $this->getConfig()->get('disabledCountQueryEntityList', []))
        ) {
            $disableCount = true;
        }

        $maxSize = 0;
        if ($disableCount) {
            if (!empty($params['maxSize'])) {
                $maxSize = $params['maxSize'];
                $params['maxSize'] = $params['maxSize'] + 1;
            }
        }

        $selectParams = $this->getSelectManager($foreignEntityName)->getSelectParams($params, !$skipAcl, true);

        if (array_key_exists($link, $this->linkSelectParams)) {
            $selectParams = array_merge($selectParams, $this->linkSelectParams[$link]);
        }

        $additionalSelectParams = $this->getMetadata()->get(['entityDefs', $this->entityType, 'links', $link, 'selectParams']);
        if ($additionalSelectParams) {
            $selectParams = array_merge($selectParams, $additionalSelectParams);
        }

        $selectParams['maxTextColumnsLength'] = $recordService->getMaxSelectTextAttributeLength();

        $selectAttributeList = $recordService->getSelectAttributeList($params);
        if ($selectAttributeList) {
            $selectParams['select'] = $selectAttributeList;
        } else {
            $selectParams['skipTextColumns'] = $recordService->isSkipSelectTextAttributes();
        }

        $collection = $this->getRepository()->findRelated($entity, $link, $selectParams);

        foreach ($collection as $e) {
            $recordService->loadAdditionalFieldsForList($e);
            if (!empty($params['loadAdditionalFields'])) {
                $recordService->loadAdditionalFields($e);
            }
            if (!empty($selectAttributeList)) {
                $this->loadLinkMultipleFieldsForList($e, $selectAttributeList);
            }
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

        return [
            'total' => $total,
            'collection' => $collection
        ];
    }

    public function linkEntity($id, $link, $foreignId) //TODO Remove in 5.8
    {
        return $this->link($id, $link, $foreignId);
    }

    public function link($id, $link, $foreignId)
    {
        if (empty($id) || empty($link) || empty($foreignId)) {
            throw new BadRequest;
        }

        if (in_array($link, $this->forbiddenLinkList)) {
            throw new Forbidden();
        }

        if (in_array($link, $this->readOnlyLinkList)) {
            throw new Forbidden();
        }

        if (!$this->getUser()->isAdmin() && in_array($link, $this->nonAdminReadOnlyLinkList)) {
            throw new Forbidden();
        }

        if (!$this->getUser()->isAdmin() && in_array($link, $this->onlyAdminLinkList)) {
            throw new Forbidden();
        }

        $entity = $this->getRepository()->get($id);
        if (!$entity) {
            throw new NotFound();
        }
        if (!$this->getAcl()->check($entity, 'edit')) {
            throw new Forbidden();
        }

        $methodName = 'link' . ucfirst($link);
        if ($link !== 'entity' && $link !== 'entityMass' && method_exists($this, $methodName)) {
            return $this->$methodName($id, $foreignId);
        }

        $foreignEntityType = $entity->getRelationParam($link, 'entity');
        if (!$foreignEntityType) {
            throw new Error("Entity '{$this->entityType}' has not relation '{$link}'.");
        }

        $foreignEntity = $this->getEntityManager()->getEntity($foreignEntityType, $foreignId);
        if (!$foreignEntity) {
            throw new NotFound();
        }

        $accessActionRequired = 'edit';
        if (in_array($link, $this->noEditAccessRequiredLinkList)) {
            $accessActionRequired = 'read';
        }
        if (!$this->getAcl()->check($foreignEntity, $accessActionRequired)) {
            throw new Forbidden();
        }

        $this->getRepository()->relate($entity, $link, $foreignEntity);
        return true;
    }

    public function unlinkEntity($id, $link, $foreignId) //TODO Remove in 5.8
    {
        return $this->unlink($id, $link, $foreignId);
    }

    public function unlink($id, $link, $foreignId)
    {
        if (empty($id) || empty($link) || empty($foreignId)) {
            throw new BadRequest;
        }

        if (in_array($link, $this->readOnlyLinkList)) {
            throw new Forbidden();
        }

        if (in_array($link, $this->internalLinkList)) {
            throw new Forbidden();
        }

        if (in_array($link, $this->forbiddenLinkList)) {
            throw new Forbidden();
        }

        if (!$this->getUser()->isAdmin() && in_array($link, $this->nonAdminReadOnlyLinkList)) {
            throw new Forbidden();
        }

        if (!$this->getUser()->isAdmin() && in_array($link, $this->onlyAdminLinkList)) {
            throw new Forbidden();
        }

        $entity = $this->getRepository()->get($id);
        if (!$entity) {
            throw new NotFound();
        }
        if (!$this->getAcl()->check($entity, 'edit')) {
            throw new Forbidden();
        }

        $methodName = 'unlink' . ucfirst($link);
        if ($link !== 'entity' && method_exists($this, $methodName)) {
            return $this->$methodName($id, $foreignId);
        }

        $foreignEntityType = $entity->getRelationParam($link, 'entity');
        if (!$foreignEntityType) {
            throw new Error("Entity '{$this->entityType}' has not relation '{$link}'.");
        }

        $foreignEntity = $this->getEntityManager()->getEntity($foreignEntityType, $foreignId);
        if (!$foreignEntity) {
            throw new NotFound();
        }

        $accessActionRequired = 'edit';
        if (in_array($link, $this->noEditAccessRequiredLinkList)) {
            $accessActionRequired = 'read';
        }
        if (!$this->getAcl()->check($foreignEntity, $accessActionRequired)) {
            throw new Forbidden();
        }

        $this->getRepository()->unrelate($entity, $link, $foreignEntity);
        return true;
    }

    public function linkEntityMass($id, $link, $where, $selectData = null) //TODO Remove in 5.8
    {
        return $this->massLink($id, $link, $where, $selectData);
    }

    public function linkFollowers($id, $foreignId)
    {
        if (!$this->getMetadata()->get(['scopes', $this->entityType, 'stream'])) throw new NotFound();

        $entity = $this->getRepository()->get($id);
        if (!$entity) throw new NotFound();
        if (!$this->getAcl()->check($entity, 'edit')) throw new Forbidden();
        if (!$this->getAcl()->check($entity, 'stream')) throw new Forbidden();

        if (!$this->getUser()->isAdmin()) throw new Forbidden();

        $this->getStreamService()->followEntity($entity, $foreignId);

        return true;
    }

    public function unlinkFollowers($id, $foreignId)
    {
        if (!$this->getMetadata()->get(['scopes', $this->entityType, 'stream'])) throw new NotFound();

        $entity = $this->getRepository()->get($id);
        if (!$entity) throw new NotFound();
        if (!$this->getAcl()->check($entity, 'edit')) throw new Forbidden();
        if (!$this->getAcl()->check($entity, 'stream')) throw new Forbidden();

        if (!$this->getUser()->isAdmin()) throw new Forbidden();

        $this->getStreamService()->unfollowEntity($entity, $foreignId);

        return true;
    }

    public function massLink($id, $link, $where, $selectData = null)
    {
        if (empty($id) || empty($link)) {
            throw new BadRequest;
        }

        if (in_array($link, $this->forbiddenLinkList)) {
            throw new Forbidden();
        }

        if (in_array($link, $this->readOnlyLinkList)) {
            throw new Forbidden();
        }

        if (!$this->getUser()->isAdmin() && in_array($link, $this->nonAdminReadOnlyLinkList)) {
            throw new Forbidden();
        }

        if (!$this->getUser()->isAdmin() && in_array($link, $this->onlyAdminLinkList)) {
            throw new Forbidden();
        }

        $entity = $this->getRepository()->get($id);
        if (!$entity) {
            throw new NotFound();
        }
        if (!$this->getAcl()->check($entity, 'edit')) {
            throw new Forbidden();
        }

        $methodName = 'massLink' . ucfirst($link);
        if (method_exists($this, $methodName)) {
            return $this->$methodName($id, $where, $selectData);
        }

        $entityType = $entity->getEntityType();
        $foreignEntityType = $entity->getRelationParam($link, 'entity');

        if (empty($foreignEntityType)) {
            throw new Error();
        }

        $accessActionRequired = 'edit';
        if (in_array($link, $this->noEditAccessRequiredLinkList)) {
            $accessActionRequired = 'read';
        }

        if (!$this->getAcl()->check($foreignEntityType, $accessActionRequired)) {
            throw new Forbidden();
        }

        if (!is_array($where)) {
            $where = array();
        }
        $params['where'] = $where;

        if (is_array($selectData)) {
            foreach ($selectData as $k => $v) {
                $params[$k] = $v;
            }
        }

        $selectParams = $this->getRecordService($foreignEntityType)->getSelectParams($params);

        if ($this->getAcl()->getLevel($foreignEntityType, $accessActionRequired) === 'all') {
            return $this->getRepository()->massRelate($entity, $link, $selectParams);
        } else {
            $foreignEntityList = $this->getEntityManager()->getRepository($foreignEntityType)->find($selectParams);
            $countRelated = 0;
            foreach ($foreignEntityList as $foreignEntity) {
                if (!$this->getAcl()->check($foreignEntity, $accessActionRequired)) {
                    continue;
                }
                $this->getRepository()->relate($entity, $link, $foreignEntity);
                $countRelated++;
            }
            if ($countRelated) {
                return true;
            }
        }
    }

    public function massUpdate(array $params, $data)
    {
        $updatedIdList = [];
        $repository = $this->getRepository();

        $count = 0;

        $data = $data;
        $this->filterInput($data);

        if (array_key_exists('ids', $params) && is_array($params['ids'])) {
            $ids = $params['ids'];
            foreach ($ids as $id) {
                $entity = $this->getEntity($id);
                if ($this->getAcl()->check($entity, 'edit') && $this->checkEntityForMassUpdate($entity, $data)) {
                    $entity->set($data);
                    try {
                        $this->processValidation($entity, $data);
                    } catch (\Exception $e) {
                        continue;
                    }
                    if ($this->checkAssignment($entity)) {
                        if ($repository->save($entity, ['massUpdate' => true])) {
                            $updatedIdList[] = $entity->id;
                            $count++;

                            $this->processActionHistoryRecord('update', $entity);
                        }
                    }
                }
            }
        }

        if (array_key_exists('where', $params)) {
            $where = $params['where'];
            $p = [];
            $p['where'] = $where;

            if (!empty($params['selectData']) && is_array($params['selectData'])) {
                foreach ($params['selectData'] as $k => $v) {
                    $p[$k] = $v;
                }
            }

            $selectParams = $this->getSelectParams($p);

            $this->getEntityManager()->getRepository($this->getEntityType())->handleSelectParams($selectParams);

            $sql = $this->getEntityManager()->getQuery()->createSelectQuery($this->getEntityType(), $selectParams);
            $sth = $this->getEntityManager()->getPdo()->prepare($sql);
            $sth->execute();

            while ($dataRow = $sth->fetch(\PDO::FETCH_ASSOC)) {
                $entity = $this->getEntityManager()->getEntityFactory()->create($this->getEntityType());
                $entity->set($dataRow);
                $entity->setAsFetched();

                if ($this->getAcl()->check($entity, 'edit') && $this->checkEntityForMassUpdate($entity, $data)) {
                    $entity->set($data);
                    if ($this->checkAssignment($entity)) {
                        if ($repository->save($entity, ['massUpdate' => true, 'skipStreamNotesAcl' => true])) {
                            $updatedIdList[] = $entity->id;
                            $count++;

                            $this->processActionHistoryRecord('update', $entity);
                        }
                    }
                }
            }

            $this->afterMassUpdate($updatedIdList, $data);

            return (object) [
                'count' => $count
            ];
        }

        $this->afterMassUpdate($updatedIdList, $data);

        return (object) [
            'count' => $count,
            'ids' => $updatedIdList
        ];
    }

    protected function checkEntityForMassRemove(Entity $entity)
    {
        return true;
    }

    protected function checkEntityForMassUpdate(Entity $entity, $data)
    {
        return true;
    }

    public function massRemove(array $params)
    {
        return $this->massDelete();
    }

    public function massDelete(array $params)
    {
        $removedIdList = [];
        $repository = $this->getRepository();

        $count = 0;

        if (array_key_exists('ids', $params)) {
            $ids = $params['ids'];
            foreach ($ids as $id) {
                $entity = $this->getEntity($id);
                if ($entity && $this->getAcl()->check($entity, 'delete') && $this->checkEntityForMassRemove($entity)) {
                    if ($repository->remove($entity)) {
                        $removedIdList[] = $entity->id;
                        $count++;

                        $this->processActionHistoryRecord('delete', $entity);
                    }
                }
            }
        }

        if (array_key_exists('where', $params)) {
            $where = $params['where'];
            $p = array();
            $p['where'] = $where;

            if (!empty($params['selectData']) && is_array($params['selectData'])) {
                foreach ($params['selectData'] as $k => $v) {
                    $p[$k] = $v;
                }
            }

            $selectParams = $this->getSelectParams($p);
            $selectParams['skipTextColumns'] = true;

            $this->getEntityManager()->getRepository($this->getEntityType())->handleSelectParams($selectParams);

            $sql = $this->getEntityManager()->getQuery()->createSelectQuery($this->getEntityType(), $selectParams);
            $sth = $this->getEntityManager()->getPdo()->prepare($sql);
            $sth->execute();

            while ($dataRow = $sth->fetch(\PDO::FETCH_ASSOC)) {
                $entity = $this->getEntityManager()->getEntityFactory()->create($this->getEntityType());
                $entity->set($dataRow);
                $entity->setAsFetched();

                if ($this->getAcl()->check($entity, 'delete') && $this->checkEntityForMassRemove($entity)) {
                    if ($repository->remove($entity)) {
                        $removedIdList[] = $entity->id;
                        $count++;

                        $this->processActionHistoryRecord('delete', $entity);
                    }
                }
            }

            $this->afterMassDelete($removedIdList);

            return [
                'count' => $count
            ];
        }

        $this->afterMassDelete($removedIdList);

        return [
            'count' => $count,
            'ids' => $removedIdList
        ];
    }

    public function massRecalculateFormula(array $params)
    {
        if (!$this->getUser()->isAdmin()) throw new Forbidden();

        $count = 0;
        if (array_key_exists('ids', $params)) {
            if (!is_array($params['ids'])) throw new BadRequest();
            $selectParams = $this->getSelectParams([]);
            $selectParams['whereClause'][] = [
                'id' => $params['ids']
            ];

        } else if (array_key_exists('where', $params)) {
            $p = ['where' => $params['where']];
            if (!empty($params['selectData']) && is_array($params['selectData'])) {
                foreach ($params['selectData'] as $k => $v) {
                    $p[$k] = $v;
                }
            }
            $selectParams = $this->getSelectParams($p);
        } else {
            throw new BadRequest();
        }

        $collection = $this->getRepository()->find($selectParams);
        foreach ($collection as $entity) {
            $this->getEntityManager()->saveEntity($entity);
            $count++;
        }

        return [
            'count' => $count
        ];
    }

    public function follow($id, $userId = null)
    {
        $entity = $this->getRepository()->get($id);

        if (!$this->getAcl()->check($entity, 'stream')) {
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

        if (empty($userId)) {
            $userId = $this->getUser()->id;
        }

        return $this->getStreamService()->unfollowEntity($entity, $userId);
    }

    public function massFollow(array $params, $userId = null)
    {
        $resultIdList = [];

        if (empty($userId)) {
            $userId = $this->getUser()->id;
        }

        $streamService = $this->getStreamService();

        if (array_key_exists('ids', $params)) {
            $idList = $params['ids'];
            foreach ($idList as $id) {
                $entity = $this->getEntity($id);
                if ($entity && $this->getAcl()->check($entity, 'stream')) {
                    if ($streamService->followEntity($entity, $userId)) {
                        $resultIdList[] = $entity->id;
                    }
                }
            }
        }

        return [
            'ids' => $resultIdList,
            'count' => count($resultIdList)
        ];
    }

    public function massUnfollow(array $params, $userId = null)
    {
        $resultIdList = [];

        if (empty($userId)) {
            $userId = $this->getUser()->id;
        }

        $streamService = $this->getStreamService();

        if (array_key_exists('ids', $params)) {
            $idList = $params['ids'];
            foreach ($idList as $id) {
                $entity = $this->getEntity($id);
                if ($entity) {
                    if ($streamService->unfollowEntity($entity, $userId)) {
                        $resultIdList[] = $entity->id;
                    }
                }
            }
        }

        return [
            'ids' => $resultIdList,
            'count' => count($resultIdList)
        ];
    }

    protected function getDuplicateWhereClause(Entity $entity, $data)
    {
        return null;
    }

    public function checkIsDuplicate(Entity $entity) : bool
    {
        $where = $this->getDuplicateWhereClause($entity, (object) []);

        if ($where) {
            if ($entity->id) {
                $where['id!='] = $entity->id;
            }
            $duplicate = $this->getRepository()->select(['id'])->where($where)->findOne();
            if ($duplicate) {
                return true;
            }
        }

        return false;
    }

    public function findDuplicates(Entity $entity, $data = null) : ?\Espo\ORM\EntityCollection
    {
        if (!$data) {
            $data = (object) [];
        }

        $where = $this->getDuplicateWhereClause($entity, $data);

        if ($where) {
            if ($entity->id) {
                $where['id!='] = $entity->id;
            }
            $select = $this->findDuplicatesSelectAttributeList;

            $duplicateList = $this->getRepository()->select($select)->where($where)->limit(0, 20)->find();

            if (count($duplicateList)) {
                return $duplicateList;
            }
        }

        return null;
    }

    public function checkAttributeIsAllowedForExport($entity, $attribute, $isExportAllFields = false)
    {
        $entity = $this->getEntityManager()->getEntity($this->getEntityType());

        if (in_array($attribute, $this->internalAttributeList)) return false;
        if (in_array($attribute, $this->forbiddenAttributeList)) return false;

        if (!$this->getUser()->isAdmin() && in_array($attribute, $this->onlyAdminAttributeList)) return false;

        if (!$isExportAllFields) return true;

        if ($entity->getAttributeParam($attribute, 'notExportable')) return false;
        if ($entity->getAttributeParam($attribute, 'isLinkMultipleIdList')) return false;
        if ($entity->getAttributeParam($attribute, 'isLinkMultipleNameMap')) return false;
        if ($entity->getAttributeParam($attribute, 'isLinkStub')) return false;

        return true;
    }

    public function exportCollection(array $params, $collection)
    {
        $params['collection'] = $collection;
        return $this->export($params);
    }

    public function export(array $params)
    {
        if (array_key_exists('format', $params)) {
            $format = $params['format'];
        } else {
            $format = 'csv';
        }

        if (!in_array($format, $this->getMetadata()->get(['app', 'export', 'formatList']))) {
            throw new Error('Not supported export format.');
        }

        $className = $this->getMetadata()->get(['app', 'export', 'exportFormatClassNameMap', $format]);
        if (empty($className)) {
            throw new Error();
        }
        $exportObj = $this->getInjection('injectableFactory')->createByClassName($className);

        $collection = null;

        if (array_key_exists('collection', $params)) {
            $collection = $params['collection'];
        } else {
            $selectManager = $this->getSelectManager($this->getEntityType());
            if (array_key_exists('ids', $params)) {
                $ids = $params['ids'];
                $where = [
                    [
                        'type' => 'in',
                        'field' => 'id',
                        'value' => $ids
                    ]
                ];
                $selectParams = $selectManager->getSelectParams(['where' => $where], true, true);
            } else if (array_key_exists('where', $params)) {
                $where = $params['where'];

                $p = [];
                $p['where'] = $where;
                if (!empty($params['selectData']) && is_array($params['selectData'])) {
                    foreach ($params['selectData'] as $k => $v) {
                        $p[$k] = $v;
                    }
                }
                $selectParams = $this->getSelectParams($p);
            } else {
                throw new BadRequest();
            }

            $orderBy = $this->getMetadata()->get(['entityDefs', $this->getEntityType(), 'collection', 'orderBy']);
            $order = $this->getMetadata()->get(['entityDefs', $this->getEntityType(), 'collection', 'order']);

            if ($orderBy) {
                $selectManager->applyOrder($orderBy, $order, $selectParams);
            }

            $this->getEntityManager()->getRepository($this->getEntityType())->handleSelectParams($selectParams);

            $collection = $this->getEntityManager()->createSthCollection($this->getEntityType(), $selectParams);
        }

        $attributeListToSkip = [
            'deleted'
        ];

        foreach ($this->exportSkipAttributeList as $attribute) {
            $attributeListToSkip[] = $attribute;
        }

        foreach ($this->getAcl()->getScopeForbiddenAttributeList($this->getEntityType(), 'read') as $attribute) {
            $attributeListToSkip[] = $attribute;
        }

        $attributeList = null;
        if (array_key_exists('attributeList', $params)) {
            $attributeList = [];
            $seed = $this->getEntityManager()->getEntity($this->getEntityType());
            foreach ($params['attributeList'] as $attribute) {
                if (in_array($attribute, $attributeListToSkip)) continue;
                if ($this->checkAttributeIsAllowedForExport($seed, $attribute)) {
                    $attributeList[] = $attribute;
                }
            }
        }

        if (!array_key_exists('fieldList', $params)) {
            $exportAllFields = true;
            $fieldDefs = $this->getMetadata()->get(['entityDefs', $this->entityType, 'fields'], []);
            $fieldList = array_keys($fieldDefs);
            array_unshift($fieldList, 'id');
        } else {
            $exportAllFields = false;
            $fieldList = $params['fieldList'];
        }

        foreach ($fieldList as $i => $field) {
            if ($this->getMetadata()->get(['entityDefs', $this->entityType, 'fields', $field, 'exportDisabled'])) {
                unset($fieldList[$i]);
            }
        }
        $fieldList = array_values($fieldList);

        if (method_exists($exportObj, 'filterFieldList')) {
            $fieldList = $exportObj->filterFieldList($this->entityType, $fieldList, $exportAllFields);
        }

        $fp = null;

        if (is_null($attributeList)) {
            $attributeList = [];
            $seed = $this->getEntityManager()->getEntity($this->entityType);
            foreach ($seed->getAttributes() as $attribute => $defs) {
                if (in_array($attribute, $attributeListToSkip)) {
                    continue;
                }
                if ($this->checkAttributeIsAllowedForExport($seed, $attribute, true)) {
                    $attributeList[] = $attribute;
                }
            }
            foreach ($this->exportAdditionalAttributeList as $attribute) {
                $attributeList[] = $attribute;
            }
        }

        if (method_exists($exportObj, 'addAdditionalAttributes')) {
            $exportObj->addAdditionalAttributes($this->entityType, $attributeList, $fieldList);
        }

        $fp = fopen('php://temp', 'w');

        foreach ($collection as $entity) {
            $this->loadAdditionalFieldsForExport($entity);
            if (method_exists($exportObj, 'loadAdditionalFields')) {
                $exportObj->loadAdditionalFields($entity, $fieldList);
            }
            $row = [];
            foreach ($attributeList as $attribute) {
                $value = $this->getAttributeFromEntityForExport($entity, $attribute);
                $row[$attribute] = $value;
            }
            $line = base64_encode(serialize($row)) . \PHP_EOL;
            fwrite($fp, $line);
        }
        rewind($fp);


        if (is_null($attributeList)) {
            $attributeList = [];
        }

        $mimeType = $this->getMetadata()->get(['app', 'export', 'formatDefs', $format, 'mimeType']);
        $fileExtension = $this->getMetadata()->get(['app', 'export', 'formatDefs', $format, 'fileExtension']);

        $fileName = null;
        if (!empty($params['fileName'])) {
            $fileName = trim($params['fileName']);
        }

        if (!empty($fileName)) {
            $fileName = $fileName . '.' . $fileExtension;
        } else {
            $fileName = "Export_{$this->entityType}." . $fileExtension;
        }

        $exportParams = [
            'attributeList' => $attributeList,
            'fileName ' => $fileName
        ];

        $exportParams['fieldList'] = $fieldList;
        if (array_key_exists('exportName', $params)) {
            $exportParams['exportName'] = $params['exportName'];
        }
        $contents = $exportObj->process($this->entityType, $exportParams, null, $fp);

        fclose($fp);

        $attachment = $this->getEntityManager()->getEntity('Attachment');
        $attachment->set('name', $fileName);
        $attachment->set('role', 'Export File');
        $attachment->set('type', $mimeType);
        $attachment->set('contents', $contents);

        $this->getEntityManager()->saveEntity($attachment);

        return $attachment->id;
    }

    protected function getAttributeFromEntityForExport(Entity $entity, $attribute)
    {
        $methodName = 'getAttribute' . ucfirst($attribute). 'FromEntityForExport';
        if (method_exists($this, $methodName)) {
            return $this->$methodName($entity);
        }

        $defs = $entity->getAttributes();
        if (!empty($defs[$attribute]) && !empty($defs[$attribute]['type'])) {
            $type = $defs[$attribute]['type'];
            switch ($type) {
                case 'jsonObject':
                    if (!empty($defs[$attribute]['isLinkMultipleNameMap'])) {
                        break;
                    }
                    $value = $entity->get($attribute);
                    return \Espo\Core\Utils\Json::encode($value, \JSON_UNESCAPED_UNICODE);
                    break;
                case 'jsonArray':
                    if (!empty($defs[$attribute]['isLinkMultipleIdList'])) {
                        break;
                    }
                    $value = $entity->get($attribute);
                    if (is_array($value)) {
                        return \Espo\Core\Utils\Json::encode($value, \JSON_UNESCAPED_UNICODE);
                    } else {
                        return null;
                    }
                    break;
                case 'password':
                    return null;
                    break;
            }
        }
        return $entity->get($attribute);
    }

    public function prepareEntityForOutput(Entity $entity)
    {
        foreach ($this->internalAttributeList as $attribute) {
            $entity->clear($attribute);
        }
        foreach ($this->forbiddenAttributeList as $attribute) {
            $entity->clear($attribute);
        }
        if (!$this->getUser()->isAdmin()) {
            foreach ($this->onlyAdminAttributeList as $attribute) {
                $entity->clear($attribute);
            }
        }
        foreach ($this->getAcl()->getScopeForbiddenAttributeList($entity->getEntityType(), 'read') as $attribute) {
            $entity->clear($attribute);
        }
    }

    public function merge($id, array $sourceIdList = array(), $attributes)
    {
        if (empty($id)) {
            throw new Error();
        }

        $repository = $this->getRepository();

        $entity = $this->getEntityManager()->getEntity($this->getEntityType(), $id);

        if (!$entity) {
            throw new NotFound();
        }
        if (!$this->getAcl()->check($entity, 'edit')) {
            throw new Forbidden();
        }

        $this->filterInput($attributes);

        $entity->set($attributes);
        if (!$this->checkAssignment($entity)) {
            throw new Forbidden();
        }

        $sourceList = array();
        foreach ($sourceIdList as $sourceId) {
            $source = $this->getEntity($sourceId);
            $sourceList[] = $source;
            if (!$this->getAcl()->check($source, 'edit') || !$this->getAcl()->check($source, 'delete')) {
                throw new Forbidden();
            }
        }

        $this->beforeMerge($entity, $sourceList, $attributes);

        $fieldDefs = $this->getMetadata()->get('entityDefs.' . $entity->getEntityType() . '.fields', array());

        $hasPhoneNumber = false;
        if (!empty($fieldDefs['phoneNumber']) && $fieldDefs['phoneNumber']['type'] == 'phone') {
            $hasPhoneNumber = true;
        }

        $hasEmailAddress = false;
        if (!empty($fieldDefs['emailAddress']) && $fieldDefs['emailAddress']['type'] == 'email') {
            $hasEmailAddress = true;
        }

        if ($hasPhoneNumber) {
            $phoneNumberToRelateList = [];
            $phoneNumberList = $repository->findRelated($entity, 'phoneNumbers');
            foreach ($phoneNumberList as $phoneNumber) {
                $phoneNumberToRelateList[] = $phoneNumber;
            }
        }

        if ($hasEmailAddress) {
            $emailAddressToRelateList = [];
            $emailAddressList = $repository->findRelated($entity, 'emailAddresses');
            foreach ($emailAddressList as $emailAddress) {
                $emailAddressToRelateList[] = $emailAddress;
            }
        }

        $pdo = $this->getEntityManager()->getPDO();

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

            if ($hasPhoneNumber) {
                $phoneNumberList = $repository->findRelated($source, 'phoneNumbers');
                foreach ($phoneNumberList as $phoneNumber) {
                    $phoneNumberToRelateList[] = $phoneNumber;
                }
            }
            if ($hasEmailAddress) {
                $emailAddressList = $repository->findRelated($source, 'emailAddresses');
                foreach ($emailAddressList as $emailAddress) {
                    $emailAddressToRelateList[] = $emailAddress;
                }
            }
        }

        $mergeLinkList = [];
        $linksDefs = $this->getMetadata()->get(['entityDefs', $this->getEntityType(), 'links']);
        foreach ($linksDefs as $link => $d) {
            if (!empty($d['notMergeable'])) {
                continue;
            }
            if (!empty($d['type']) && in_array($d['type'], ['hasMany', 'hasChildren'])) {
                $mergeLinkList[] = $link;
            }
        }

        foreach ($sourceList as $source) {
            foreach ($mergeLinkList as $link) {
                $linkedList = $repository->findRelated($source, $link);
                foreach ($linkedList as $linked) {
                    $repository->relate($entity, $link, $linked);
                }
            }
        }

        foreach ($sourceList as $source) {
            $this->getEntityManager()->removeEntity($source);

            $this->processActionHistoryRecord('delete', $source);
        }

        if ($hasEmailAddress) {
            $emailAddressData = [];
            foreach ($emailAddressToRelateList as $i => $emailAddress) {
                $o = (object) [];
                $o->emailAddress = $emailAddress->get('name');
                $o->primary = false;
                if (empty($attributes->emailAddress)) {
                    if ($i === 0) {
                        $o->primary = true;
                    }
                } else {
                    $o->primary = $o->emailAddress === $attributes->emailAddress;
                }
                $o->optOut = $emailAddress->get('optOut');
                $o->invalid = $emailAddress->get('invalid');
                $emailAddressData[] = $o;
            }
            $attributes->emailAddressData = $emailAddressData;
        }

        if ($hasPhoneNumber) {
            $phoneNumberData = [];
            foreach ($phoneNumberToRelateList as $i => $phoneNumber) {
                $o = (object) [];
                $o->phoneNumber = $phoneNumber->get('name');
                $o->primary = false;
                if (empty($attributes->phoneNumber)) {
                    if ($i === 0) {
                        $o->primary = true;
                    }
                } else {
                    $o->primary = $o->phoneNumber === $attributes->phoneNumber;
                }
                $o->type = $phoneNumber->get('type');
                $phoneNumberData[] = $o;
            }
            $attributes->phoneNumberData = $phoneNumberData;
        }

        $entity->set($attributes);
        $repository->save($entity);

        $this->processActionHistoryRecord('update', $entity);

        $this->afterMerge($entity, $sourceList, $attributes);

        return true;
    }

    protected function beforeMerge(Entity $entity, array $sourceList, $attributes)
    {
    }

    protected function afterMerge(Entity $entity, array $sourceList, $attributes)
    {
    }

    protected function findLinkedFollowers($id, $params)
    {
        $entity = $this->getRepository()->get($id);
        if (!$entity) {
            throw new NotFound();
        }
        if (!$this->getAcl()->check($entity, 'read')) {
            throw new Forbidden();
        }

        return $this->getStreamService()->findEntityFollowers($entity, $params);
    }

    public function getDuplicateAttributes($id)
    {
        if (empty($id)) {
            throw new BadRequest();
        }

        $entity = $this->getEntity($id);

        if (!$entity) {
            throw new NotFound();
        }

        $attributes = $entity->getValueMap();
        unset($attributes->id);

        $fields = $this->getMetadata()->get(['entityDefs', $this->getEntityType(), 'fields'], []);

        $fieldManager = new \Espo\Core\Utils\FieldManagerUtil($this->getMetadata());

        foreach ($fields as $field => $item) {
            if (!empty($item['duplicateIgnore']) || in_array($field, $this->duplicateIgnoreFieldList)) {
                $attributeToIgnoreList = $fieldManager->getAttributeList($this->entityType, $field);
                foreach ($attributeToIgnoreList as $attribute) {
                    unset($attributes->$attribute);
                }
                continue;
            }

            if (empty($item['type'])) continue;
            $type = $item['type'];

            if (in_array($type, ['file', 'image'])) {
                $attachment = $entity->get($field);
                if ($attachment) {
                    $attachment = $this->getEntityManager()->getRepository('Attachment')->getCopiedAttachment($attachment);
                    $idAttribute = $field . 'Id';
                    if ($attachment) {
                        $attributes->$idAttribute = $attachment->id;
                    }
                }
            } else if (in_array($type, ['attachmentMultiple'])) {
                $attachmentList = $entity->get($field);
                if (count($attachmentList)) {
                    $idList = [];
                    $nameHash = (object) [];
                    $typeHash = (object) [];
                    foreach ($attachmentList as $attachment) {
                        $attachment = $this->getEntityManager()->getRepository('Attachment')->getCopiedAttachment($attachment);
                        if ($attachment) {
                            $idList[] = $attachment->id;
                            $nameHash->{$attachment->id} = $attachment->get('name');
                            $typeHash->{$attachment->id} = $attachment->get('type');
                        }
                    }
                    $attributes->{$field . 'Ids'} = $idList;
                    $attributes->{$field . 'Names'} = $nameHash;
                    $attributes->{$field . 'Types'} = $typeHash;
                }
            } else if ($type === 'linkMultiple') {
                $foreignLink = $entity->getRelationParam($field, 'foreign');
                $foreignEntityType = $entity->getRelationParam($field, 'entity');
                if ($foreignEntityType && $foreignLink) {
                    $foreignRelationType = $this->getMetadata()->get(['entityDefs', $foreignEntityType, 'links', $foreignLink, 'type']);
                    if ($foreignRelationType !== 'hasMany') {
                        unset($attributes->{$field . 'Ids'});
                        unset($attributes->{$field . 'Names'});
                        unset($attributes->{$field . 'Columns'});
                    }
                }
            }
        }

        foreach ($this->duplicateIgnoreAttributeList as $attribute) {
            unset($attributes->$attribute);
        }

        $attributes->_duplicatingEntityId = $id;

        return $attributes;
    }

    protected function afterCreateProcessDuplicating(Entity $entity, $data)
    {
        if (!isset($data->_duplicatingEntityId)) return;

        $duplicatingEntityId = $data->_duplicatingEntityId;
        if (!$duplicatingEntityId) return;
        $duplicatingEntity = $this->getEntityManager()->getEntity($entity->getEntityType(), $duplicatingEntityId);
        if (!$duplicatingEntity) return;
        if (!$this->getAcl()->check($duplicatingEntity, 'read')) return;

        $this->duplicateLinks($entity, $duplicatingEntity);
    }

    protected function duplicateLinks(Entity $entity, Entity $duplicatingEntity)
    {
        $repository = $this->getRepository();

        foreach ($this->duplicatingLinkList as $link) {
            $linkedList = $repository->findRelated($duplicatingEntity, $link);
            foreach ($linkedList as $linked) {
                $repository->relate($entity, $link, $linked);
            }
        }
    }

    protected function getFieldByTypeList($type)
    {
        return $this->getFieldManagerUtil()->getFieldByTypeList($this->entityType, $type);
    }

    public function getSelectAttributeList($params)
    {
        if ($this->forceSelectAllAttributes) {
            return null;
        }

        if ($this->selectAttributeList) {
            return $this->selectAttributeList;
        }

        // TODO remove in 5.7.0
        if (in_array($this->getEntityType(), ['Report', 'Workflow', 'ReportPanel'])) {
            return null;
        }

        if (!array_key_exists('select', $params)) {
            return null;
        }

        $attributeList = $this->getSelectManager()->getSelectAttributeList($params);

        if (!empty($this->mandatorySelectAttributeList)) {
            $seed = $this->getEntityManager()->getEntity($this->getEntityType());
            foreach ($this->mandatorySelectAttributeList as $attribute) {
                if (!in_array($attribute, $attributeList) && $seed->hasAttribute($attribute)) {
                    $attributeList[] = $attribute;
                }
            }
        }

        return $attributeList;
    }
}
