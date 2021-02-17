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

namespace Espo\Services;

use Espo\Core\Exceptions\{
    Error,
    BadRequest,
    NotFound,
    Forbidden,
    NotFoundSilent,
    ForbiddenSilent,
    ConflictSilent,
};

use Espo\ORM\{
    Entity,
    Repository\Repository,
    Collection,
};

use Espo\Entities\User;

use Espo\Core\{
    Acl,
    AclManager,
    Utils\Util,
    Services\Crud,
    Select\SearchParams,
    Record\Collection as RecordCollection,
    MassAction\Params as MassActionParams,
    MassAction\Result as MassActionResult,
    MassAction\Data as MassActionData,
    MassAction\MassActionFactory,
    Action\Params as ActionParams,
    Action\Data as ActionData,
    Action\ActionFactory,
};

use Espo\Tools\{
    Export\Export as ExportTool,
    Kanban\Kanban as KanbanTool,
    Kanban\Result as KanbanResult,
};

use Espo\Core\Di;

use StdClass;

/**
 * The layer between Controller and Repository. For CRUD and other operations with records.
 * If a service with the name of an entity type exists then it will be used instead this one.
 * Access control is checked here.
 */
class Record implements Crud,

    Di\ConfigAware,
    Di\ServiceFactoryAware,
    Di\EntityManagerAware,
    Di\UserAware,
    Di\MetadataAware,
    Di\AclAware,
    Di\AclManagerAware,
    Di\FileManagerAware,
    Di\SelectManagerFactoryAware,
    Di\InjectableFactoryAware,
    Di\FieldUtilAware,
    Di\FieldValidatorManagerAware,
    Di\RecordServiceContainerAware,
    Di\SelectBuilderFactoryAware,

    /** for backward compatibility, to be removed */
    \Espo\Core\Interfaces\Injectable
{
    use Di\ConfigSetter;
    use Di\ServiceFactorySetter;
    use Di\EntityManagerSetter;
    use Di\UserSetter;
    use Di\MetadataSetter;
    use Di\AclSetter;
    use Di\AclManagerSetter;
    use Di\FileManagerSetter;
    use Di\SelectManagerFactorySetter;
    use Di\InjectableFactorySetter;
    use Di\FieldUtilSetter;
    use Di\FieldValidatorManagerSetter;
    use Di\RecordServiceContainerSetter;
    use Di\SelectBuilderFactorySetter;

    /** for backward compatibility, to be removed */
    use \Espo\Core\Traits\Injectable;

    /** for backward compatibility, to be removed */
    protected $dependencyList = [];

    protected $getEntityBeforeUpdate = false;

    protected $entityType = null;

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

    protected $linkMandatorySelectAttributeList = [];

    protected $noEditAccessRequiredLinkList = [];

    protected $noEditAccessRequiredForLink = false;

    protected $exportSkipAttributeList = [];

    protected $exportAdditionalAttributeList = [];

    protected $checkForDuplicatesInUpdate = false;

    protected $actionHistoryDisabled = false;

    protected $duplicatingLinkList = [];

    protected $listCountQueryDisabled = false;

    protected $maxSelectTextAttributeLength = null;

    protected $maxSelectTextAttributeLengthDisabled = false;

    protected $selectAttributeList = null;

    protected $mandatorySelectAttributeList = [];

    protected $forceSelectAllAttributes = false;

    protected $validateSkipFieldList = [];

    protected $findDuplicatesSelectAttributeList = ['id', 'name'];

    protected $duplicateIgnoreFieldList = [];

    protected $duplicateIgnoreAttributeList = [];

    protected $acl = null;

    protected $user = null;

    protected $aclManager = null;

    const MAX_SELECT_TEXT_ATTRIBUTE_LENGTH = 5000;

    const FOLLOWERS_LIMIT = 4;

    const FIND_DUPLICATES_LIMIT = 10;

    protected $selectBuilderFactory;

    public function __construct()
    {
        if (empty($this->entityType)) {
            $name = get_class($this);
            if (preg_match('@\\\\([\w]+)$@', $name, $matches)) {
                $name = $matches[1];
            }
            if ($name != 'Record') {
                $this->entityType = Util::normilizeScopeName($name);
            }
        }

        // to be removed
        $this->init();
    }

    // for backward compatibility, to be removed
    protected function init()
    {
    }

    public function setAclManager(AclManager $aclManager)
    {
        $this->aclManager = $aclManager;

        if ($this->entityType) {
            $this->initAclParams();
        }
    }

    public function setEntityType(string $entityType)
    {
        $initAclParams = false;
        if (!$this->entityType) {
            $initAclParams = true;
        }

        $this->entityType = $entityType;

        if ($initAclParams) {
            $this->initAclParams();
        }
    }

    protected function initAclParams()
    {
        $aclManager = $this->aclManager;

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

    public function getEntityType() : string
    {
        return $this->entityType;
    }

    protected function getConfig()
    {
        return $this->config;
    }

    protected function getServiceFactory()
    {
        return $this->serviceFactory;
    }

    protected function getSelectManagerFactory()
    {
        return $this->selectManagerFactory;
    }

    protected function getAcl()
    {
        return $this->acl;
    }

    protected function getUser()
    {
        return $this->user;
    }

    public function setAcl(Acl $acl)
    {
        $this->acl = $acl;
    }

    public function setUser(User $user)
    {
        $this->user = $user;
    }

    protected function getAclManager()
    {
        return $this->aclManager;
    }

    protected function getFileManager()
    {
        return $this->fileManager;
    }

    protected function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * @deprecated
     */
    protected function getFieldManagerUtil()
    {
        return $this->fieldUtil;
    }

    protected function getEntityManager()
    {
        return $this->entityManager;
    }

    protected function getRepository() : Repository
    {
        return $this->getEntityManager()->getRepository($this->entityType);
    }

    /** @deprecated */
    protected function getRecordService($name)
    {
        return $this->recordServiceContainer->get($name);
    }

    public function processActionHistoryRecord(string $action, Entity $entity)
    {
        if ($this->actionHistoryDisabled) {
            return;
        }

        if ($this->getConfig()->get('actionHistoryDisabled')) {
            return;
        }

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

    /**
     * @deprecated
     */
    public function readEntity($id) //TODO Remove in 5.8
    {
        return $this->read($id);
    }

    public function read(string $id) : Entity
    {
        if (empty($id)) {
            throw new Error("No ID passed.");
        }

        $entity = $this->getEntity($id);

        if (!$entity) {
            throw new NotFoundSilent("Record {$id} does not exist.");
        }

        $this->processActionHistoryRecord('read', $entity);

        return $entity;
    }

    public function getEntity(?string $id = null) : ?Entity
    {
        if ($id === null) {
            return $this->getRepository()->getNew();
        }

        $entity = $this->getRepository()->getById($id);

        if (!$entity && $this->getUser()->isAdmin()) {
            $entity = $this->getEntityEvenDeleted($id);
        }

        if (!$entity) {
            return null;
        }

        $this->loadAdditionalFields($entity);

        if (!$this->getAcl()->check($entity, 'read')) {
            throw new ForbiddenSilent("No read access.");
        }

        $this->prepareEntityForOutput($entity);

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
        if ($this->getUser()->isPortal()) {
            return;
        }

        if (!$this->getMetadata()->get(['scopes', $entity->getEntityType(), 'stream'])) {
            return;
        }

        if (!$this->getAcl()->check($entity, 'stream')) {
            return;
        }

        $data = $this->getStreamService()->getEntityFollowers($entity, 0, self::FOLLOWERS_LIMIT);

        if ($data) {
            $entity->set('followersIds', $data['idList']);
            $entity->set('followersNames', $data['nameMap']);
        }
    }

    protected function loadLinkMultipleFields(Entity $entity)
    {
        $fieldDefs = $this->getMetadata()->get(['entityDefs', $entity->getEntityType(), 'fields']) ?? [];

        foreach ($fieldDefs as $field => $defs) {
            if (
                isset($defs['type']) &&
                in_array($defs['type'], ['linkMultiple', 'attachmentMultiple']) &&
                empty($defs['noLoad']) &&
                empty($defs['notStorable']) &&
                $entity->hasRelation($field)
            ) {
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

                if (!$field) {
                    continue;
                }

                if ($entity->has($attribute)) {
                    continue;
                }

                $entity->loadLinkMultipleField($field);
            }
        }
    }

    protected function loadLinkFields(Entity $entity)
    {
        $fieldDefs = $this->getMetadata()->get('entityDefs.' . $entity->getEntityType() . '.fields', []);
        $linkDefs = $this->getMetadata()->get('entityDefs.' . $entity->getEntityType() . '.links', []);

        foreach ($fieldDefs as $field => $defs) {
            if (isset($defs['type']) && $defs['type'] === 'link') {
                if (!empty($defs['noLoad'])) {
                    continue;
                }

                if (empty($linkDefs[$field])) {
                    continue;
                }

                if (empty($linkDefs[$field]['type'])) {
                    continue;
                }

                if ($linkDefs[$field]['type'] !== 'hasOne') {
                    continue;
                }

                $entity->loadLinkField($field);
            }
        }
    }

    protected function loadParentNameFields(Entity $entity)
    {
        $fieldDefs = $this->getMetadata()->get('entityDefs.' . $entity->getEntityType() . '.fields', []);

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
        $linkDefs = $this->getMetadata()->get('entityDefs.' . $entity->getEntityType() . '.links', []);

        foreach ($linkDefs as $link => $defs) {
            $type = $defs['type'] ?? null;

            if ($type !== 'belongsTo') {
                continue;
            }

            if (empty($defs['noJoin']) || empty($defs['entity'])) {
                continue;
            }

            $nameAttribute = $link . 'Name';
            $idAttribute = $link . 'Id';

            if (!$entity->hasAttribute($nameAttribute) || $entity->hasAttribute($idAttribute)) {
                continue;
            }

            $id = $entity->get($idAttribute);

            if (!$id) {
                $entity->set($nameAttribute, null);

                continue;
            }

            $scope = $defs['entity'];

            if (!$this->getEntityManager()->hasRepository($scope)) {
                continue;
            }

            $foreignEntity = $this->getEntityManager()
                ->getRepository($scope)
                ->select(['id', 'name'])
                ->where(['id' => $id])
                ->findOne();

            if ($foreignEntity) {
                $entity->set($nameAttribute, $foreignEntity->get('name'));

                continue;
            }

            $entity->set($nameAttribute, null);
        }
    }

    protected function loadEmptyNameLinkFields(Entity $entity)
    {
        $linkDefs = $this->getMetadata()->get(['entityDefs', $entity->getEntityType(), 'links'], []);

        foreach ($linkDefs as $link => $defs) {
            if (!isset($defs['type'])) {
                continue;
            }

            if ($defs['type'] !== 'belongsTo') {
                continue;
            }

            $nameAttribute = $link . 'Name';
            $idAttribute = $link . 'Id';

            if ($entity->get($idAttribute) && !$entity->get($nameAttribute)) {
                $id = $entity->get($idAttribute);

                if (empty($defs['entity'])) {
                    continue;
                }

                $scope = $defs['entity'];

                if ($this->getEntityManager()->hasRepository($scope)) {
                    $foreignEntity = $this->getEntityManager()
                        ->getRepository($scope)
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
        $fieldDefs = $this->getMetadata()->get('entityDefs.' . $entity->getEntityType() . '.fields', []);

        if (!empty($fieldDefs['emailAddress']) && $fieldDefs['emailAddress']['type'] == 'email') {
            $dataAttributeName = 'emailAddressData';

            $emailAddressData = $this->getEntityManager()
                ->getRepository('EmailAddress')
                ->getEmailAddressData($entity);

            $entity->set($dataAttributeName, $emailAddressData);
            $entity->setFetched($dataAttributeName, $emailAddressData);
        }
    }

    protected function loadPhoneNumberField(Entity $entity)
    {
        $fieldDefs = $this->getMetadata()->get('entityDefs.' . $entity->getEntityType() . '.fields', []);

        if (!empty($fieldDefs['phoneNumber']) && $fieldDefs['phoneNumber']['type'] == 'phone') {
            $dataAttributeName = 'phoneNumberData';

            $phoneNumberData = $this->getEntityManager()
                ->getRepository('PhoneNumber')
                ->getPhoneNumberData($entity);

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
        $this->getRepository()->save($entity);
    }

    public function processValidation(Entity $entity, $data)
    {
        $fieldList = $this->fieldUtil->getEntityTypeFieldList($this->entityType);

        foreach ($fieldList as $field) {
            if (in_array($field, $this->validateSkipFieldList)) {
                continue;
            }

            if (!$entity->isNew()) {
                if (!$this->isFieldSetInData($data, $field)) {
                    continue;
                }
            }

            $this->processValidationField($entity, $field, $data);
        }
    }

    protected function processValidationField(Entity $entity, string $field, $data)
    {
        $fieldType = $this->fieldUtil->getEntityTypeFieldParam($this->entityType, $field, 'type');

        $validationList = $this->getMetadata()->get(['fields', $fieldType, 'validationList'], []);
        $mandatoryValidationList = $this->getMetadata()->get(['fields', $fieldType, 'mandatoryValidationList'], []);

        $fieldValidatorManager = $this->fieldValidatorManager;

        foreach ($validationList as $type) {
            $value = $this->fieldUtil->getEntityTypeFieldParam($this->entityType, $field, $type);

            if (is_null($value)) {
                if (!in_array($type, $mandatoryValidationList)) {
                    continue;
                }
            }

            $skipPropertyName = 'validate' . ucfirst($type) . 'SkipFieldList';

            if (property_exists($this, $skipPropertyName)) {
                $skipList = $this->$skipPropertyName;

                if (in_array($field, $skipList)) {
                    continue;
                }
            }

            if (!$fieldValidatorManager->check($entity, $field, $type, $data)) {
                throw new BadRequest("Not valid data. Field: '{$field}', type: {$type}.");
            }
        }
    }

    protected function isFieldSetInData(StdClass $data, string $field) : bool
    {
        $attributeList = $this->fieldUtil->getActualAttributeList($this->entityType, $field);

        $isSet = false;

        foreach ($attributeList as $attribute) {
            if (property_exists($data, $attribute)) {
                $isSet = true;

                break;
            }
        }

        return $isSet;
    }

    public function processAssignmentCheck(Entity $entity)
    {
        if (!$this->checkAssignment($entity)) {
            throw new Forbidden("Assignment failure: assigned user or team not allowed.");
        }
    }

    public function checkAssignment(Entity $entity) : bool
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

    public function isPermittedAssignedUsers(Entity $entity) : bool
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

        if (
            $assignmentPermission === true ||
            $assignmentPermission === 'yes' ||
            !in_array($assignmentPermission, ['team', 'no'])
        ) {
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
                if ($assignmentPermission == 'no' && !$this->getUser()->isApi()) {
                    return false;
                }

                return true;
            }

            $fetchedAssignedUserIdList = $entity->getFetched('assignedUsersIds');

            if ($assignmentPermission == 'no') {
                foreach ($userIdList as $userId) {
                    if (!$entity->isNew() && in_array($userId, $fetchedAssignedUserIdList)) {
                        continue;
                    }

                    if ($this->getUser()->id != $userId) {
                        return false;
                    }
                }
            } else if ($assignmentPermission == 'team') {
                $teamIdList = $this->getUser()->getLinkMultipleIdList('teams');

                foreach ($userIdList as $userId) {
                    if (!$entity->isNew() && in_array($userId, $fetchedAssignedUserIdList)) {
                        continue;
                    }

                    if (!$this->getEntityManager()->getRepository('User')->checkBelongsToAnyOfTeams($userId, $teamIdList)) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    public function isPermittedAssignedUser(Entity $entity) : bool
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

        if (
            $assignmentPermission === true ||
            $assignmentPermission === 'yes' ||
            !in_array($assignmentPermission, ['team', 'no'])
        ) {
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
                if ($assignmentPermission == 'no' && !$this->getUser()->isApi()) {
                    return false;
                }

                return true;
            }

            if ($assignmentPermission == 'no') {
                if ($this->getUser()->id != $assignedUserId) {
                    return false;
                }
            }
            else if ($assignmentPermission == 'team') {
                $teamIdList = $this->getUser()->get('teamsIds');

                if (
                    !$this->getEntityManager()
                        ->getRepository('User')
                        ->checkBelongsToAnyOfTeams($assignedUserId, $teamIdList)
                ) {
                    return false;
                }
            }
        }

        return true;
    }

    public function isPermittedTeams(Entity $entity) : bool
    {
        $assignmentPermission = $this->getAcl()->get('assignmentPermission');

        if (
            empty($assignmentPermission) ||
            $assignmentPermission === true ||
            !in_array($assignmentPermission, ['team', 'no'])
        ) {
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
                }
                else if ($entity->hasAttribute('assignedUserId')) {
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

            $teamCollection = $this->getEntityManager()
                ->getRepository($entity->getEntityType())
                ->getRelation($entity, 'teams')
                ->select('id')
                ->find();

            foreach ($teamCollection as $team) {
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

    public function filterCreateInput(StdClass $data) : void
    {
        unset($data->deleted);
        unset($data->id);
        unset($data->modifiedById);
        unset($data->modifiedByName);
        unset($data->modifiedAt);
        unset($data->createdById);
        unset($data->createdByName);
        unset($data->createdAt);

        $this->filterInput($data);

        $this->handleInput($data);
        $this->handleCreateInput($data);
    }

    public function filterUpdateInput(StdClass $data) : void
    {
        unset($data->deleted);
        unset($data->id);
        unset($data->modifiedById);
        unset($data->modifiedByName);
        unset($data->modifiedAt);
        unset($data->createdById);
        unset($data->createdByName);
        unset($data->createdAt);

        $this->filterInput($data);

        $this->handleInput($data);
    }

    /**
     * @deprecated
     */
    protected function handleCreateInput($data)
    {
    }

    /**
     * @deprecated
     */
    protected function handleInput($data)
    {
    }

    protected function processDuplicateCheck(Entity $entity, $data)
    {
        if (
            !empty($data->_skipDuplicateCheck) ||
            !empty($data->skipDuplicateCheck) ||
            !empty($data->forceDuplicate)
        ) {
            return;
        }

        $duplicateList = $this->findDuplicates($entity, $data);

        if (empty($duplicateList)) {
            return;
        }

        $list = [];

        foreach ($duplicateList as $e) {
            $list[] = $e->getValueMap();
        }

        throw ConflictSilent::createWithBody('duplicate', json_encode($list));
    }

    public function populateDefaults(Entity $entity, StdClass $data) : void
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

        foreach ($this->fieldUtil->getEntityTypeFieldList($this->entityType) as $field) {
            $type = $this->fieldUtil->getEntityTypeFieldParam($this->entityType, $field, 'type');

            if ($type === 'currency') {
                if ($entity->get($field) && !$entity->get($field . 'Currency')) {
                    $entity->set($field . 'Currency', $this->getConfig()->get('defaultCurrency'));
                }
            }
        }
    }

    /**
     * @deprecated
     */
    public function createEntity($data)
    {
        return $this->create($data);
    }

    public function create(StdClass $data) : Entity
    {
        if (!$this->getAcl()->check($this->getEntityType(), 'create')) {
            throw new ForbiddenSilent("No create access.");
        }

        $entity = $this->getRepository()->get();

        $this->filterCreateInput($data);

        $entity->set($data);

        $this->populateDefaults($entity, $data);

        if (!$this->getAcl()->check($entity, 'create')) {
            throw new ForbiddenSilent("No create access.");
        }

        $this->processValidation($entity, $data);
        $this->processAssignmentCheck($entity);
        $this->processDuplicateCheck($entity, $data);
        $this->beforeCreateEntity($entity, $data);

        $this->storeEntity($entity);

        $this->afterCreateEntity($entity, $data);
        $this->afterCreateProcessDuplicating($entity, $data);
        $this->loadAdditionalFields($entity);
        $this->prepareEntityForOutput($entity);
        $this->processActionHistoryRecord('create', $entity);

        return $entity;
    }

    /**
     * @deprecated
     */
    public function updateEntity($id, $data)
    {
        return $this->update($id, $data);
    }

    public function update(string $id, StdClass $data) : Entity
    {
        if (empty($id)) {
            throw new BadRequest("ID is empty.");
        }

        $this->filterUpdateInput($data);

        if ($this->getEntityBeforeUpdate) {
            $entity = $this->getEntity($id);
        } else {
            $entity = $this->getRepository()->get($id);
        }

        if (!$entity) {
            throw new NotFound("Record {$id} not found.");
        }

        if (!$this->getAcl()->check($entity, 'edit')) {
            throw new ForbiddenSilent("No edit access.");
        }

        $entity->set($data);

        $this->processValidation($entity, $data);

        $this->processAssignmentCheck($entity);

        $this->beforeUpdateEntity($entity, $data);

        if ($this->checkForDuplicatesInUpdate) {
            $this->processDuplicateCheck($entity, $data);
        }

        $this->storeEntity($entity);

        $this->afterUpdateEntity($entity, $data);

        $this->prepareEntityForOutput($entity);

        $this->processActionHistoryRecord('update', $entity);

        return $entity;
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

    /**
     * @deprecated
     */
    public function deleteEntity($id)
    {
        return $this->delete($id);
    }

    public function delete(string $id) : void
    {
        if (empty($id)) {
            throw new BadRequest("ID is empty.");
        }

        $entity = $this->getRepository()->get($id);

        if (!$entity) {
            throw new NotFound("Record {$id} not found.");
        }

        if (!$this->getAcl()->check($entity, 'delete')) {
            throw new ForbiddenSilent("No delete access.");
        }

        $this->beforeDeleteEntity($entity);

        $this->getRepository()->remove($entity);

        $this->afterDeleteEntity($entity);

        $this->processActionHistoryRecord('delete', $entity);
    }

    /**
     * @deprecated
     */
    protected function getSelectParams($params)
    {
        $selectManager = $this->getSelectManager($this->entityType);

        $selectParams = $selectManager->getSelectParams($params, true, true, true);

        if (empty($selectParams['orderBy'])) {
            $selectManager->applyDefaultOrder($selectParams);
        }

        return $selectParams;
    }

    /**
     * @deprecated
     */
    public function findEntities($params)
    {
        return $this->find($params);
    }

    public function find(array $params) : RecordCollection
    {
        $disableCount = false;
        if (
            $this->listCountQueryDisabled
            ||
            $this->getMetadata()->get(['entityDefs', $this->entityType, 'collection', 'countDisabled'])
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

        $this->handleListParams($params);

        $selectBuilder = $this->selectBuilderFactory->create();

        $query = $selectBuilder
            ->from($this->entityType)
            ->withStrictAccessControl()
            ->withSearchParams(
                SearchParams::fromRaw($params)
            )
            ->build();

        $collection = $this->getRepository()
            ->clone($query)
            ->find();

        foreach ($collection as $e) {
            $this->loadAdditionalFieldsForList($e);

            if (!empty($params['loadAdditionalFields'])) {
                $this->loadAdditionalFields($e);
            }

            if (!empty($params['select'])) {
                $this->loadLinkMultipleFieldsForList($e, $params['select']);
            }

            $this->prepareEntityForOutput($e);
        }

        if (!$disableCount) {
            $total = $this->getRepository()
                ->clone($query)
                ->count();
        }
        else {
            if ($maxSize && count($collection) > $maxSize) {
                $total = -1;

                unset($collection[count($collection) - 1]);
            } else {
                $total = -2;
            }
        }

        return new RecordCollection($collection, $total);
    }

    public function getListKanban(array $params) : KanbanResult
    {
        $disableCount = false;

        if (
            $this->listCountQueryDisabled
            ||
            $this->getMetadata()->get(['entityDefs', $this->entityType, 'collection', 'countDisabled'])
        ) {
            $disableCount = true;
        }

        $orderDisabled = $this->metadata->get(['scopes', $this->entityType, 'kanbanOrderDisabled']) ?? false;

        $this->handleListParams($params);

        $kanban = $this->injectableFactory->create(KanbanTool::class);

        $kanban
            ->setEntityType($this->entityType)
            ->setRecordService($this)
            ->setSearchParams($params)
            ->setCountDisabled($disableCount)
            ->setOrderDisabled($orderDisabled)
            ->setUserId($this->getUser()->id);

        $maxOrderNumber = $this->getConfig()->get('kanbanMaxOrderNumber') ?? null;

        if ($maxOrderNumber) {
            $kanban->setMaxOrderNumber($maxOrderNumber);
        }

        return $kanban->getResult();
    }

    protected function getEntityEvenDeleted(string $id) : ?Entity
    {
        $query = $this->entityManager->getQueryBuilder()
            ->select()
            ->from($this->entityType)
            ->where([
                'id' => $id,
            ])
            ->withDeleted()
            ->build();

        return $this->getRepository()->clone($query)->findOne();
    }

    public function restoreDeleted(string $id) : void
    {
        if (!$this->getUser()->isAdmin()) {
            throw new Forbidden();
        }

        $entity = $this->getEntityEvenDeleted($id);

        if (!$entity) {
            throw new NotFound();
        }

        if (!$entity->get('deleted')) {
            throw new Forbidden();
        }

        $this->getRepository()->restoreDeleted($entity->id);
    }

    public function getMaxSelectTextAttributeLength() : ?int
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

    /** @deprecated */
    public function findLinkedEntities($id, $link, $params)
    {
        return $this->findLinked($id, $link, $params);
    }

    public function findLinked(string $id, string $link, array $params) : RecordCollection
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

        $foreignEntityType = $this->entityManager
            ->getDefs()
            ->getEntity($this->entityType)
            ->getRelation($link)
            ->getForeignEntityType();

        $linkParams = $this->linkParams[$link] ?? [];

        $skipAcl = $linkParams['skipAcl'] ?? false;

        if (!$skipAcl) {
            if (!$this->getAcl()->check($foreignEntityType, 'read')) {
                throw new Forbidden();
            }
        }

        $recordService = $this->recordServiceContainer->get($foreignEntityType);

        $disableCount = false;
        $disableCountPropertyName = 'findLinked' . ucfirst($link) . 'CountQueryDisabled';

        if (
            !empty($this->$disableCountPropertyName)
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

        $recordService->handleListParams($params);

        if (isset($params['select'])) {
            $mandatorySelectAttributeList = $this->linkMandatorySelectAttributeList[$link] ?? [];

            foreach ($mandatorySelectAttributeList as $item) {
                if (in_array($item, $params['select'])) {
                    continue;
                }

                $params['select'][] = $item;
            }
        }

        $selectBuilder = $this->selectBuilderFactory->create();

        $selectBuilder
            ->from($foreignEntityType)
            ->withSearchParams(
                SearchParams::fromRaw($params)
            );

        if (!$skipAcl) {
            $selectBuilder->withStrictAccessControl();
        }
        else {
            $selectBuilder->withComplexExpressionsForbidden();
            $selectBuilder->withWherePermissionCheck();
        }

        $query = $selectBuilder->build();

        $collection = $this->entityManager
            ->getRepository($this->entityType)
            ->getRelation($entity, $link)
            ->clone($query)
            ->find();

        foreach ($collection as $e) {
            $recordService->loadAdditionalFieldsForList($e);

            if (!empty($params['loadAdditionalFields'])) {
                $recordService->loadAdditionalFields($e);
            }

            if (!empty($params['select'])) {
                $this->loadLinkMultipleFieldsForList($e, $params['select']);
            }

            $recordService->prepareEntityForOutput($e);
        }

        if (!$disableCount) {
            $total = $this->entityManager
                ->getRepository($this->entityType)
                ->getRelation($entity, $link)
                ->clone($query)
                ->count();
        }
        else {
            if ($maxSize && count($collection) > $maxSize) {
                $total = -1;

                unset($collection[count($collection) - 1]);
            }
            else {
                $total = -2;
            }
        }

        return new RecordCollection($collection, $total);
    }

    /**
     * @deprecated
     */
    public function linkEntity($id, $link, $foreignId)
    {
        return $this->link($id, $link, $foreignId);
    }

    public function link(string $id, string $link, string $foreignId) : void
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

        if ($this->noEditAccessRequiredForLink) {
            if (!$this->getAcl()->check($entity, 'read')) {
                throw new Forbidden();
            }
        } else {
            if (!$this->getAcl()->check($entity, 'edit')) {
                throw new Forbidden();
            }
        }

        $methodName = 'link' . ucfirst($link);

        if ($link !== 'entity' && $link !== 'entityMass' && method_exists($this, $methodName)) {
            $this->$methodName($id, $foreignId);

            return;
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
    }

    /**
     * @deprecated
     */
    public function unlinkEntity($id, $link, $foreignId)
    {
        return $this->unlink($id, $link, $foreignId);
    }

    public function unlink(string $id, string $link, string $foreignId) : void
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

        if ($this->noEditAccessRequiredForLink) {
            if (!$this->getAcl()->check($entity, 'read')) {
                throw new Forbidden();
            }
        } else {
            if (!$this->getAcl()->check($entity, 'edit')) {
                throw new Forbidden();
            }
        }

        $methodName = 'unlink' . ucfirst($link);

        if ($link !== 'entity' && method_exists($this, $methodName)) {
            $this->$methodName($id, $foreignId);

            return;
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
    }

    /**
     * @deprecated
     */
    public function linkEntityMass($id, $link, $where, $selectData = null)
    {
        return $this->massLink($id, $link, $where, $selectData);
    }

    public function linkFollowers(string $id, string $foreignId) : void
    {
        if (!$this->getMetadata()->get(['scopes', $this->entityType, 'stream'])) {
            throw new NotFound();
        }

        $entity = $this->getRepository()->get($id);

        if (!$entity) {
            throw new NotFound();
        }

        if (!$this->getAcl()->check($entity, 'edit')) {
            throw new Forbidden();
        }

        if (!$this->getAcl()->check($entity, 'stream')) {
            throw new Forbidden();
        }

        if (!$this->getUser()->isAdmin()) {
            throw new Forbidden();
        }

        $result = $this->getStreamService()->followEntity($entity, $foreignId);

        if (!$result) {
            throw new Forbidden("Could not add a user to followers. The user needs to have 'stream' access.");
        }
    }

    public function unlinkFollowers(string $id, string $foreignId) : void
    {
        if (!$this->getMetadata()->get(['scopes', $this->entityType, 'stream'])) {
            throw new NotFound();
        }

        $entity = $this->getRepository()->get($id);

        if (!$entity) {
            throw new NotFound();
        }

        if (!$this->getAcl()->check($entity, 'edit')) {
            throw new Forbidden();
        }

        if (!$this->getAcl()->check($entity, 'stream')) {
            throw new Forbidden();
        }

        if (!$this->getUser()->isAdmin()) {
            throw new Forbidden();
        }

        $this->getStreamService()->unfollowEntity($entity, $foreignId);
    }

    public function massLink(string $id, string $link, array $where, ?array $selectData = null)
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
            $where = [];
        }
        $params['where'] = $where;

        if (is_array($selectData)) {
            foreach ($selectData as $k => $v) {
                $params[$k] = $v;
            }
        }

        $query = $this->selectBuilderFactory->create()
            ->from($foreignEntityType)
            ->withStrictAccessControl()
            ->withSearchParams(SearchParams::fromRaw($params))
            ->build();

        if ($this->getAcl()->getLevel($foreignEntityType, $accessActionRequired) === 'all') {
            $this->getRepository()
                ->getRelation($entity, $link)
                ->massRelate($query);

            return true;
        }

        $countRelated = 0;

        $foreignCollection = $this->entityManager
            ->getRepository($foreignEntityType)
            ->clone($query)
            ->find();

        foreach ($foreignCollection as $foreignEntity) {
            if (!$this->getAcl()->check($foreignEntity, $accessActionRequired)) {
                continue;
            }

            $this->getRepository()
                ->getRelation($entity, $link)
                ->relate($foreignEntity);

            $countRelated++;
        }

        if ($countRelated) {
            return true;
        }

        return false;
    }

    public function follow(string $id, ?string $userId = null)
    {
        $entity = $this->getRepository()->get($id);

        if (!$entity) {
            throw new NotFoundSilent();
        }

        if (!$this->getAcl()->check($entity, 'stream')) {
            throw new Forbidden();
        }

        if (empty($userId)) {
            $userId = $this->getUser()->id;
        }

        return $this->getStreamService()->followEntity($entity, $userId);
    }

    public function unfollow(string $id, ?string $userId = null)
    {
        $entity = $this->getRepository()->get($id);

        if (!$entity) {
            throw new NotFoundSilent();
        }

        if (empty($userId)) {
            $userId = $this->getUser()->id;
        }

        return $this->getStreamService()->unfollowEntity($entity, $userId);
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

            $duplicate = $this->getRepository()
                ->select(['id'])
                ->where($where)
                ->findOne();

            if ($duplicate) {
                return true;
            }
        }

        return false;
    }

    public function findDuplicates(Entity $entity, $data = null) : ?Collection
    {
        if (!$data) {
            $data = (object) [];
        }

        $where = $this->getDuplicateWhereClause($entity, $data);

        if (!$where) {
            return null;
        }

        if ($entity->id) {
            $where['id!='] = $entity->id;
        }

        $select = $this->findDuplicatesSelectAttributeList;

        $builder = $this->selectBuilderFactory->create();

        $query = $builder
            ->from($this->entityType)
            ->withStrictAccessControl()
            ->build();

        $duplicateCollection = $this->getRepository()
            ->clone($query)
            ->select($select)
            ->where($where)
            ->limit(0, self::FIND_DUPLICATES_LIMIT)
            ->find();

        if (count($duplicateCollection)) {
            return $duplicateCollection;
        }

        return null;
    }

    public function exportCollection(array $params, $collection)
    {
        $params['collection'] = $collection;

        return $this->export($params);
    }

    public function export(array $params)
    {
        $export = $this->injectableFactory->create(ExportTool::class);

        return $export
            ->setRecordService($this)
            ->setParams($params)
            ->setEntityType($this->getEntityType())
            ->setAdditionalAttributeList($this->exportAdditionalAttributeList)
            ->setSkipAttributeList($this->exportSkipAttributeList)
            ->run();
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

    public function merge(string $id, array $sourceIdList, StdClass $attributes) : void
    {
        if (empty($id)) {
            throw new Error("No ID passed.");
        }

        $repository = $this->getRepository();

        $entity = $this->getEntityManager()->getEntity($this->getEntityType(), $id);

        if (!$entity) {
            throw new NotFound("Record not found.");
        }

        if (!$this->getAcl()->check($entity, 'edit')) {
            throw new Forbidden("No edit access.");
        }

        $this->filterUpdateInput($attributes);

        $entity->set($attributes);
        if (!$this->checkAssignment($entity)) {
            throw new Forbidden("Assignment permission failure.");
        }

        $sourceList = [];

        foreach ($sourceIdList as $sourceId) {
            $source = $this->getEntity($sourceId);

            $sourceList[] = $source;

            if (!$this->getAcl()->check($source, 'edit') || !$this->getAcl()->check($source, 'delete')) {
                throw new Forbidden("No edit or delete access.");
            }
        }

        $this->beforeMerge($entity, $sourceList, $attributes);

        $fieldDefs = $this->getMetadata()->get('entityDefs.' . $entity->getEntityType() . '.fields', []);

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

        foreach ($sourceList as $source) {
            $updateQuery = $this->getEntityManager()->getQueryBuilder()
                ->update()
                ->in('Note')
                ->set([
                    'parentId' => $entity->id,
                    'parentType' => $entity->getEntityType(),
                ])
                ->where([
                    'type' => ['Post', 'EmailSent', 'EmailReceived'],
                    'parentId' => $source->id,
                    'parentType' => $source->getEntityType(),
                    'deleted' => false,
                ])
                ->build();

            $this->getEntityManager()->getQueryExecutor()->execute($updateQuery);

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

    public function getDuplicateAttributes(string $id) : StdClass
    {
        if (empty($id)) {
            throw new BadRequest("No ID passed.");
        }

        $entity = $this->getEntity($id);

        if (!$entity) {
            throw new NotFound("Record not found.");
        }

        $attributes = $entity->getValueMap();

        unset($attributes->id);

        $fields = $this->getMetadata()->get(['entityDefs', $this->getEntityType(), 'fields'], []);

        $fieldManager = $this->fieldUtil;

        foreach ($fields as $field => $item) {
            if (!empty($item['duplicateIgnore']) || in_array($field, $this->duplicateIgnoreFieldList)) {
                $attributeToIgnoreList = $fieldManager->getAttributeList($this->entityType, $field);

                foreach ($attributeToIgnoreList as $attribute) {
                    unset($attributes->$attribute);
                }

                continue;
            }

            if (empty($item['type'])) {
                continue;
            }

            $type = $item['type'];

            if (in_array($type, ['file', 'image'])) {
                $attachment = $entity->get($field);

                if ($attachment) {
                    $attachment = $this->getEntityManager()
                        ->getRepository('Attachment')
                        ->getCopiedAttachment($attachment);

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
                        $attachment = $this->getEntityManager()
                            ->getRepository('Attachment')
                            ->getCopiedAttachment($attachment);

                        $attachment->set('field', $field);

                        $this->getEntityManager()->saveEntity($attachment);

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
                    $foreignRelationType = $this->getMetadata()->get(
                        ['entityDefs', $foreignEntityType, 'links', $foreignLink, 'type']
                    );

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
        if (!isset($data->_duplicatingEntityId)) {
            return;
        }

        $duplicatingEntityId = $data->_duplicatingEntityId;

        if (!$duplicatingEntityId) {
            return;
        }

        $duplicatingEntity = $this->getEntityManager()->getEntity($entity->getEntityType(), $duplicatingEntityId);

        if (!$duplicatingEntity) {
            return;
        }

        if (!$this->getAcl()->check($duplicatingEntity, 'read')) {
            return;
        }

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
        return $this->fieldUtil->getFieldByTypeList($this->entityType, $type);
    }

    /**
     * Handle list parameters (passed from front-end).
     */
    public function handleListParams(array &$params)
    {
        $this->handleListParamsSelect($params);

        $params['maxTextAttributeLength'] = $this->getMaxSelectTextAttributeLength();
    }

    protected function handleListParamsSelect(array &$params)
    {
        if ($this->forceSelectAllAttributes) {
            unset($params['select']);

            return;
        }

        if ($this->selectAttributeList) {
            $params['select'] = $this->selectAttributeList;

            return;
        }

        if (
            count($this->mandatorySelectAttributeList) &&
            array_key_exists('select', $params) && is_array($params['select'])
        ) {

            $itemList = $params['select'];

            foreach ($this->mandatorySelectAttributeList as $attribute) {
                if (in_array($attribute, $itemList)) {
                    continue;
                }

                $itemList[] = $attribute;
            }

            $params['select'] = $itemList;
        }
    }

    public function massAction(string $action, array $params, StdClass $data) : MassActionResult
    {
        if (!$this->acl->checkScope($this->entityType)) {
            throw new Forbidden();
        }

        $massActionFactory = $this->injectableFactory->create(MassActionFactory::class);

        $massActionParams = MassActionParams::fromRaw($params, $this->entityType);

        $massAction = $massActionFactory->create($action, $this->entityType);

        $result = $massAction->process(
            $massActionParams,
            MassActionData::fromRaw($data)
        );

        if ($massActionParams->hasIds()) {
            return $result;
        }

        return $result->withNoIds();
    }

    public function action(string $action, string $id, StdClass $data) : Entity
    {
        if (!$this->acl->checkScope($this->entityType)) {
            throw new Forbidden();
        }

        if (!$action || !$id) {
            throw new BadRequest();
        }

        $actionParams = new ActionParams($this->entityType, $id);

        $actionFactory = $this->injectableFactory->create(ActionFactory::class);

        $actionProcessor = $actionFactory->create($action, $this->entityType);

        $actionProcessor->process(
            $actionParams,
            ActionData::fromRaw($data)
        );

        $entity = $this->getEntity($id);

        if (!$entity) {
            throw new NotFound();
        }

        return $entity;
    }

    /**
     * @deprecated
     */
    protected function getConvertCurrencyFieldList()
    {
        if (isset($this->convertCurrencyFieldList)) {
            return $this->convertCurrencyFieldList;
        }

        $forbiddenFieldList = $this->getAcl()->getScopeForbiddenFieldList($this->entityType, 'edit');

        $list = [];

        foreach ($this->fieldUtil->getEntityTypeFieldList($this->entityType) as $field) {
            if ($this->getMetadata()->get(['entityDefs', $this->entityType, 'fields', $field, 'type']) !== 'currency') {
                continue;
            }

            if (in_array($field, $forbiddenFieldList)) {
                continue;
            }

            $list[] = $field;
        }

        return $list;
    }

    /**
     * @deprecated Use `Espo\Core\FieldUtils\Currency\CurrencyConverter`.
     */
    public function getConvertCurrencyValues(
        Entity $entity, string $targetCurrency, string $baseCurrency,
        StdClass $rates, bool $allFields = false, ?array $fieldList = null
    ) {
        $fieldList = $fieldList ?? $this->getConvertCurrencyFieldList();

        $data = (object) [];

        foreach ($fieldList as $field) {
            $currencyAttribute = $field . 'Currency';

            $currentCurrency = $entity->get($currencyAttribute);
            $value = $entity->get($field);

            if ($value === null) {
                continue;
            }

            if ($currentCurrency === $targetCurrency) {
                continue;
            }

            if ($currentCurrency !== $baseCurrency && !property_exists($rates, $currentCurrency)) {
                continue;
            }

            $rate1 = property_exists($rates, $currentCurrency) ? $rates->$currentCurrency : 1.0;
            $value = $value * $rate1;

            $rate2 = property_exists($rates, $targetCurrency) ? $rates->$targetCurrency : 1.0;
            $value = $value / $rate2;

            if (!$rate2) {
                continue;
            }

            $value = round($value, 2);

            $data->$currencyAttribute = $targetCurrency;

            $data->$field = $value;
        }

        return $data;
    }
}
