<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2023 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Core\Record;

use Espo\Core\Binding\BindingContainerBuilder;
use Espo\Core\Binding\ContextualBinder;
use Espo\Core\Exceptions\Conflict;
use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\Error\Body as ErrorBody;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\ConflictSilent;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\ForbiddenSilent;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Exceptions\NotFoundSilent;
use Espo\Core\Field\LinkParent;
use Espo\Core\ORM\Entity as CoreEntity;
use Espo\Core\Record\Access\LinkCheck;
use Espo\Core\Record\Formula\Processor as FormulaProcessor;
use Espo\Core\Utils\Json;
use Espo\Core\Acl;
use Espo\Core\Acl\Table as AclTable;
use Espo\Core\Duplicate\Finder as DuplicateFinder;
use Espo\Core\FieldProcessing\ListLoadProcessor;
use Espo\Core\FieldProcessing\Loader\Params as FieldLoaderParams;
use Espo\Core\FieldProcessing\ReadLoadProcessor;
use Espo\Core\FieldValidation\FieldValidationParams as FieldValidationParams;
use Espo\Core\Record\Collection as RecordCollection;
use Espo\Core\Record\Duplicator\EntityDuplicator;
use Espo\Core\Record\HookManager as RecordHookManager;
use Espo\Core\Record\Select\ApplierClassNameListProvider;
use Espo\Core\Select\SearchParams;
use Espo\Core\Select\SelectBuilderFactory;
use Espo\Core\Di;
use Espo\ORM\Entity;
use Espo\ORM\Repository\RDBRepository;
use Espo\ORM\Collection;
use Espo\ORM\EntityManager;
use Espo\ORM\Query\Part\WhereClause;
use Espo\Tools\Stream\Service as StreamService;
use Espo\Entities\User;
use Espo\Entities\ActionHistoryRecord;
use stdClass;
use InvalidArgumentException;
use LogicException;

/**
 * The layer between a controller and ORM repository. For CRUD and other operations with records.
 * Access control is processed here.
 *
 * @template TEntity of Entity
 * @implements Crud<TEntity>
 */
class Service implements Crud,

    Di\ConfigAware,
    Di\ServiceFactoryAware,
    Di\EntityManagerAware,
    Di\UserAware,
    Di\MetadataAware,
    Di\AclAware,
    Di\InjectableFactoryAware,
    Di\FieldUtilAware,
    Di\FieldValidationManagerAware,
    Di\RecordServiceContainerAware,
    Di\SelectBuilderFactoryAware,
    Di\AssignmentCheckerManagerAware,
    Di\RecordHookManagerAware
{
    use Di\ConfigSetter;
    use Di\ServiceFactorySetter;
    use Di\EntityManagerSetter;
    use Di\UserSetter;
    use Di\MetadataSetter;
    use Di\AclSetter;
    use Di\InjectableFactorySetter;
    use Di\FieldUtilSetter;
    use Di\FieldValidationManagerSetter;
    use Di\RecordServiceContainerSetter;
    use Di\SelectBuilderFactorySetter;
    use Di\AssignmentCheckerManagerSetter;
    use Di\RecordHookManagerSetter;

    /** @var bool */
    protected $getEntityBeforeUpdate = false;
    /** @var string */
    protected $entityType;
    /** @var ?StreamService */
    private $streamService = null;
    /**
     * @var string[]
     * @todo Maybe remove it?
     */
    protected $notFilteringAttributeList = [];
    /** @var string[] */
    protected $forbiddenAttributeList = [];
    /** @var string[] */
    protected $internalAttributeList = [];
    /** @var string[] */
    protected $onlyAdminAttributeList = [];
    /** @var string[] */
    protected $readOnlyAttributeList = [];
    /** @var string[] */
    protected $nonAdminReadOnlyAttributeList = [];
    /** @var string[] */
    protected $forbiddenLinkList = [];
    /** @var string[] */
    protected $internalLinkList = [];
    /** @var string[] */
    protected $readOnlyLinkList = [];
    /** @var string[] */
    protected $nonAdminReadOnlyLinkList = [];
    /** @var string[] */
    protected $onlyAdminLinkList = [];
    /** @var array<string, array<string, mixed>> */
    protected $linkParams = [];
    /** @var array<string, string[]> */
    protected $linkMandatorySelectAttributeList = [];
    /** @var string[] */
    protected $noEditAccessRequiredLinkList = [];
    /** @var bool */
    protected $noEditAccessRequiredForLink = false;
    /** @var bool */
    protected $checkForDuplicatesInUpdate = false;
    /** @var bool */
    protected $actionHistoryDisabled = false;
    /** @var string[] */
    protected $duplicatingLinkList = [];
    /** @var bool */
    protected $listCountQueryDisabled = false;
    /** @var ?int */
    protected $maxSelectTextAttributeLength = null;
    /** @var bool */
    protected $maxSelectTextAttributeLengthDisabled = false;
    /** @var ?string[] */
    protected $selectAttributeList = null;
    /** @var string[] */
    protected $mandatorySelectAttributeList = [];
    /** @var bool */
    protected $forceSelectAllAttributes = false;
    /** @var string[] */
    protected $validateSkipFieldList = [];
    /**
     * @todo Move to metadata.
     * @var string[]
     */
    protected $validateRequiredSkipFieldList = [];
    /** @var string[] */
    protected $duplicateIgnoreAttributeList = [];

    /** @var Acl */
    protected $acl = null;
    /** @var User */
    protected $user = null;
    /** @var EntityManager */
    protected $entityManager;
    /** @var SelectBuilderFactory */
    protected $selectBuilderFactory;
    /** @var RecordHookManager */
    protected $recordHookManager;
    /** @var ?ListLoadProcessor */
    private $listLoadProcessor = null;
    /** @var ?DuplicateFinder */
    private $duplicateFinder = null;
    private ?LinkCheck $linkCheck = null;

    protected const MAX_SELECT_TEXT_ATTRIBUTE_LENGTH = 10000;

    public function __construct(string $entityType = '')
    {
        $this->entityType = $entityType;
    }

    /**
     * @return RDBRepository<TEntity>
     */
    protected function getRepository(): RDBRepository
    {
        return $this->entityManager->getRDBRepository($this->entityType);
    }

    /**
     * Add an action-history record.
     *
     * @param ActionHistoryRecord::ACTION_* $action
     */
    public function processActionHistoryRecord(string $action, Entity $entity): void
    {
        if ($this->actionHistoryDisabled) {
            return;
        }

        if ($this->config->get('actionHistoryDisabled')) {
            return;
        }

        /** @var ActionHistoryRecord $historyRecord */
        $historyRecord = $this->entityManager->getNewEntity(ActionHistoryRecord::ENTITY_TYPE);

        $historyRecord
            ->setAction($action)
            ->setUserId($this->user->getId())
            ->setAuthTokenId($this->user->get('authTokenId'))
            ->setAuthLogRecordId($this->user->get('authLogRecordId'))
            ->setIpAddress($this->user->get('ipAddress'))
            ->setTarget(LinkParent::createFromEntity($entity));

        $this->entityManager->saveEntity($historyRecord);
    }

    /**
     * Read a record by ID. Access control check is performed.
     *
     * @param non-empty-string $id
     * @return TEntity
     * @throws NotFoundSilent If not found.
     * @throws ForbiddenSilent If no read access.
     */
    public function read(string $id, ReadParams $params): Entity
    {
        if ($id === '') {
            throw new InvalidArgumentException();
        }

        if (!$this->acl->check($this->entityType, AclTable::ACTION_READ)) {
            throw new ForbiddenSilent();
        }

        $entity = $this->getEntity($id);

        if (!$entity) {
            throw new NotFoundSilent("Record $id does not exist.");
        }

        $this->recordHookManager->processBeforeRead($entity, $params);
        $this->processActionHistoryRecord(ActionHistoryRecord::ACTION_READ, $entity);

        return $entity;
    }

    /**
     * Get an entity by ID. Access control check is performed.
     *
     * @throws ForbiddenSilent If no read access.
     * @return ?TEntity
     */
    public function getEntity(string $id): ?Entity
    {
        $entity = $this->getRepository()->getById($id);

        if (!$entity && $this->user->isAdmin()) {
            $entity = $this->getEntityEvenDeleted($id);
        }

        if (!$entity) {
            return null;
        }

        $this->loadAdditionalFields($entity);

        if (!$this->acl->check($entity, AclTable::ACTION_READ)) {
            throw new ForbiddenSilent("No 'read' access.");
        }

        $this->prepareEntityForOutput($entity);

        return $entity;
    }

    protected function getStreamService(): StreamService
    {
        if (empty($this->streamService)) {
            $this->streamService = $this->injectableFactory->create(StreamService::class);
        }

        return $this->streamService;
    }

    private function createReadLoadProcessor(): ReadLoadProcessor
    {
        return $this->injectableFactory->create(ReadLoadProcessor::class);
    }

    private function getListLoadProcessor(): ListLoadProcessor
    {
        if (!$this->listLoadProcessor) {
            $this->listLoadProcessor = $this->injectableFactory->create(ListLoadProcessor::class);
        }

        return $this->listLoadProcessor;
    }

    /**
     * @param TEntity $entity
     * @return void
     */
    public function loadAdditionalFields(Entity $entity)
    {
        $loadProcessor = $this->createReadLoadProcessor();

        $loadProcessor->process($entity);
    }

    /**
     * @param Entity $entity
     * @todo Make private.
     * @note Not to be extended.
     */
    protected function loadListAdditionalFields(Entity $entity, ?SearchParams $searchParams = null): void
    {
        $params = new FieldLoaderParams();

        if ($searchParams && $searchParams->getSelect()) {
            $params = $params->withSelect($searchParams->getSelect());
        }

        $loadProcessor = $this->getListLoadProcessor();

        $loadProcessor->process($entity, $params);
    }

    /**
     * @param TEntity $entity
     * @param stdClass $data
     * @return void
     * @throws BadRequest
     */
    public function processValidation(Entity $entity, $data)
    {
        $params = FieldValidationParams
            ::create()
            ->withSkipFieldList($this->validateSkipFieldList)
            ->withTypeSkipFieldList('required', $this->validateRequiredSkipFieldList);

        $this->fieldValidationManager->process($entity, $data, $params);
    }

    /**
     * @param TEntity $entity
     * @throws Forbidden
     */
    protected function processAssignmentCheck(Entity $entity): void
    {
        if (!$this->checkAssignment($entity)) {
            throw new Forbidden("Assignment failure: assigned user or team not allowed.");
        }
    }

    /**
     * Check whether assignment can be applied for an entity.
     *
     * @param TEntity $entity
     */
    public function checkAssignment(Entity $entity): bool
    {
        return $this->assignmentCheckerManager->check($this->user, $entity);
    }

    private function getLinkCheck(): LinkCheck
    {
        if (!$this->linkCheck) {
            $linkCheck = $this->injectableFactory->createWithBinding(
                LinkCheck::class,
                BindingContainerBuilder::create()
                    ->bindInstance(Acl::class, $this->acl)
                    ->bindInstance(User::class, $this->user)
                    ->inContext(LinkCheck::class, function (ContextualBinder $binder) {
                        $binder
                            ->bindValue('$noEditAccessRequiredLinkList', $this->noEditAccessRequiredLinkList)
                            ->bindValue('$noEditAccessRequiredForLink', $this->noEditAccessRequiredForLink);
                    })
                    ->build()
            );

            $this->linkCheck = $linkCheck;
        }

        return $this->linkCheck;
    }

    /**
     * @param string $attribute
     * @param mixed $value
     * @return mixed
     */
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

    /**
     * @param stdClass $data
     * @return void
     */
    protected function filterInput($data)
    {
        foreach($this->readOnlyAttributeList as $attribute) {
            unset($data->$attribute);
        }

        foreach ($this->forbiddenAttributeList as $attribute) {
            unset($data->$attribute);
        }

        foreach (get_object_vars($data) as $key => $value) {
            $data->$key = $this->filterInputAttribute($key, $data->$key);
        }

        if (!$this->user->isAdmin()) {
            foreach ($this->onlyAdminAttributeList as $attribute) {
                unset($data->$attribute);
            }
        }

        $forbiddenAttributeList = $this->acl
            ->getScopeForbiddenAttributeList($this->entityType, AclTable::ACTION_EDIT);

        foreach ($forbiddenAttributeList as $attribute) {
            unset($data->$attribute);
        }

        if (!$this->user->isAdmin()) {
            foreach ($this->nonAdminReadOnlyAttributeList as $attribute) {
                unset($data->$attribute);
            }
        }
    }

    public function filterCreateInput(stdClass $data): void
    {
        unset($data->deleted);
        unset($data->id);
        unset($data->modifiedById);
        unset($data->modifiedByName);
        unset($data->modifiedAt);
        unset($data->createdById);
        unset($data->createdByName);
        unset($data->createdAt);
        unset($data->versionNumber);

        $this->filterInput($data);
        $this->handleInput($data);
        $this->handleCreateInput($data);
    }

    public function filterUpdateInput(stdClass $data): void
    {
        unset($data->deleted);
        unset($data->id);
        unset($data->modifiedById);
        unset($data->modifiedByName);
        unset($data->modifiedAt);
        unset($data->createdById);
        unset($data->createdByName);
        unset($data->createdAt);
        unset($data->versionNumber);

        $this->filterInput($data);
        $this->handleInput($data);
    }

    /**
     * @deprecated As of v7.0. Use filterCreateInput or filterUpdateInput. Or better don't extend the class.
     * Use entityAcl, app > acl, roles to restrict write access for specific fields.
     * @param stdClass $data
     * @return void
     */
    protected function handleCreateInput($data)
    {
    }

    /**
     * @deprecated As of v7.0. Use filterCreateInput or filterUpdateInput. Or better don't extend the class.
     * Use entityAcl, app > acl, roles to restrict write access for specific fields.
     * @param stdClass $data
     * @return void
     */
    protected function handleInput($data)
    {
    }

    /**
     * @param TEntity $entity
     * @throws Conflict
     */
    protected function processConcurrencyControl(Entity $entity, stdClass $data, int $versionNumber): void
    {
        if ($entity->get('versionNumber') === null) {
            return;
        }

        if ($versionNumber === $entity->get('versionNumber')) {
            return;
        }

        $attributeList = array_keys(get_object_vars($data));

        $notMatchingAttributeList = [];

        foreach ($attributeList as $attribute) {
            if ($entity->get($attribute) !== $data->$attribute) {
                $notMatchingAttributeList[] = $attribute;
            }
        }

        if (empty($notMatchingAttributeList)) {
            return;
        }

        $values = (object) [];

        foreach ($notMatchingAttributeList as $attribute) {
            $values->$attribute = $entity->get($attribute);
        }

        $responseData = (object) [
            'values' => $values,
            'versionNumber' => $entity->get('versionNumber'),
        ];

        throw ConflictSilent::createWithBody('modified', Json::encode($responseData));
    }

    /**
     * @param TEntity $entity
     * @throws Conflict
     */
    protected function processDuplicateCheck(Entity $entity): void
    {
        $duplicateList = $this->findDuplicates($entity);

        if (empty($duplicateList)) {
            return;
        }

        $list = [];

        foreach ($duplicateList as $e) {
            $list[] = (object) [
                'id' => $e->getId(),
                'name' => $e->get('name'),
            ];
        }

        throw ConflictSilent::createWithBody('duplicate', Json::encode($list));
    }

    /**
     * @param TEntity $entity
     */
    public function populateDefaults(Entity $entity, stdClass $data): void
    {
        if (!$this->user->isPortal()) {
            $forbiddenFieldList = null;

            if ($entity->hasAttribute('assignedUserId')) {
                $forbiddenFieldList = $this->acl
                    ->getScopeForbiddenFieldList($this->entityType, AclTable::ACTION_EDIT);

                if (in_array('assignedUser', $forbiddenFieldList)) {
                    $entity->set('assignedUserId', $this->user->getId());
                    $entity->set('assignedUserName', $this->user->getName());
                }
            }

            if (
                $entity instanceof CoreEntity &&
                $entity->hasLinkMultipleField('teams')
            ) {
                if (is_null($forbiddenFieldList)) {
                    $forbiddenFieldList = $this->acl
                        ->getScopeForbiddenFieldList($this->entityType, AclTable::ACTION_EDIT);
                }

                if (
                    in_array('teams', $forbiddenFieldList) &&
                    $this->user->get('defaultTeamId')
                ) {

                    $defaultTeamId = $this->user->get('defaultTeamId');

                    $entity->addLinkMultipleId('teams', $defaultTeamId);

                    $teamsNames = $entity->get('teamsNames');

                    if (!$teamsNames || !is_object($teamsNames)) {
                        $teamsNames = (object) [];
                    }

                    $teamsNames->$defaultTeamId = $this->user->get('defaultTeamName');

                    $entity->set('teamsNames', $teamsNames);

                }
            }
        }

        foreach ($this->fieldUtil->getEntityTypeFieldList($this->entityType) as $field) {
            $type = $this->fieldUtil->getEntityTypeFieldParam($this->entityType, $field, 'type');

            if ($type === 'currency') {
                if ($entity->get($field) && !$entity->get($field . 'Currency')) {
                    $entity->set($field . 'Currency', $this->config->get('defaultCurrency'));
                }
            }
        }
    }

    /**
     * Create a record.
     *
     * @return TEntity
     * @throws BadRequest
     * @throws Forbidden If no create access.
     * @throws Conflict
     */
    public function create(stdClass $data, CreateParams $params): Entity
    {
        if (!$this->acl->check($this->entityType, AclTable::ACTION_CREATE)) {
            throw new ForbiddenSilent();
        }

        $entity = $this->getRepository()->getNew();

        $this->filterCreateInput($data);

        $entity->set($data);

        $this->populateDefaults($entity, $data);

        if (!$this->acl->check($entity, AclTable::ACTION_CREATE)) {
            throw new ForbiddenSilent("No create access.");
        }

        $this->processValidation($entity, $data);
        $this->processAssignmentCheck($entity);
        $this->getLinkCheck()->process($entity);

        if (!$params->skipDuplicateCheck()) {
            $this->processDuplicateCheck($entity);
        }

        $this->processApiBeforeCreateApiScript($entity, $params);
        $this->recordHookManager->processBeforeCreate($entity, $params);

        $this->beforeCreateEntity($entity, $data);

        $this->entityManager->saveEntity($entity);

        $this->afterCreateEntity($entity, $data);
        $this->afterCreateProcessDuplicating($entity, $params);
        $this->loadAdditionalFields($entity);
        $this->prepareEntityForOutput($entity);
        $this->processActionHistoryRecord(ActionHistoryRecord::ACTION_CREATE, $entity);

        return $entity;
    }

    /**
     * Update a record.
     *
     * @return TEntity
     * @throws NotFound If record not found.
     * @throws Forbidden If no access.
     * @throws Conflict
     * @throws BadRequest
     */
    public function update(string $id, stdClass $data, UpdateParams $params): Entity
    {
        if (!$this->acl->check($this->entityType, AclTable::ACTION_EDIT)) {
            throw new ForbiddenSilent();
        }

        if (!$id) {
            throw new BadRequest("ID is empty.");
        }

        $this->filterUpdateInput($data);

        $entity = $this->getEntityBeforeUpdate ?
            $this->getEntity($id) :
            $this->getRepository()->getById($id);

        if (!$entity) {
            throw new NotFound("Record $id not found.");
        }

        if (!$this->getEntityBeforeUpdate) {
            $this->loadAdditionalFields($entity);
        }

        if (!$this->acl->check($entity, AclTable::ACTION_EDIT)) {
            throw new ForbiddenSilent("No edit access.");
        }

        if ($params->getVersionNumber() !== null) {
            $this->processConcurrencyControl($entity, $data, $params->getVersionNumber());
        }

        $entity->set($data);

        $this->processValidation($entity, $data);
        $this->processAssignmentCheck($entity);
        $this->getLinkCheck()->process($entity);

        $checkForDuplicates =
            $this->metadata->get(['recordDefs', $this->entityType, 'updateDuplicateCheck']) ??
            $this->checkForDuplicatesInUpdate;

        if ($checkForDuplicates && !$params->skipDuplicateCheck()) {
            $this->processDuplicateCheck($entity);
        }

        $this->processApiBeforeUpdateApiScript($entity, $params);
        $this->recordHookManager->processBeforeUpdate($entity, $params);
        $this->beforeUpdateEntity($entity, $data);

        $this->entityManager->saveEntity($entity);

        $this->afterUpdateEntity($entity, $data);
        $this->prepareEntityForOutput($entity);
        $this->processActionHistoryRecord(ActionHistoryRecord::ACTION_UPDATE, $entity);

        return $entity;
    }

    /**
     * Delete a record.
     *
     * @throws Forbidden
     * @throws BadRequest
     * @throws NotFound
     */
    public function delete(string $id, DeleteParams $params): void
    {
        if (!$this->acl->check($this->entityType, AclTable::ACTION_DELETE)) {
            throw new ForbiddenSilent();
        }

        if (!$id) {
            throw new BadRequest("ID is empty.");
        }

        $entity = $this->getRepository()->getById($id);

        if (!$entity) {
            throw new NotFound("Record $id not found.");
        }

        if (!$this->acl->check($entity, AclTable::ACTION_DELETE)) {
            throw new ForbiddenSilent("No delete access.");
        }

        $this->recordHookManager->processBeforeDelete($entity, $params);
        $this->beforeDeleteEntity($entity);
        $this->getRepository()->remove($entity);
        $this->afterDeleteEntity($entity);
        $this->processActionHistoryRecord(ActionHistoryRecord::ACTION_DELETE, $entity);
    }

    /**
     * Find records.
     *
     * @return RecordCollection<TEntity>
     * @throws Forbidden
     * @throws BadRequest
     * @throws Error
     */
    public function find(SearchParams $searchParams, ?FindParams $params = null): RecordCollection
    {
        if (!$this->acl->check($this->entityType, AclTable::ACTION_READ)) {
            throw new ForbiddenSilent();
        }

        if (!$params) {
            $params = FindParams::create();
        }

        $disableCount =
            $params->noTotal() ||
            $this->listCountQueryDisabled ||
            $this->metadata->get(['entityDefs', $this->entityType, 'collection', 'countDisabled']);

        $maxSize = $searchParams->getMaxSize();

        if ($disableCount && $maxSize) {
            $searchParams = $searchParams->withMaxSize($maxSize + 1);
        }

        $preparedSearchParams = $this->prepareSearchParams($searchParams);

        $selectBuilder = $this->selectBuilderFactory->create();

        $query = $selectBuilder
            ->from($this->entityType)
            ->withStrictAccessControl()
            ->withSearchParams($preparedSearchParams)
            ->withAdditionalApplierClassNameList(
                $this->createSelectApplierClassNameListProvider()->get($this->entityType)
            )
            ->build();

        $collection = $this->getRepository()
            ->clone($query)
            ->find();

        foreach ($collection as $entity) {
            $this->loadListAdditionalFields($entity, $preparedSearchParams);

            $this->prepareEntityForOutput($entity);
        }

        if ($disableCount) {
            return RecordCollection::createNoCount($collection, $maxSize);
        }

        $total = $this->getRepository()
            ->clone($query)
            ->count();

        return RecordCollection::create($collection, $total);
    }

    protected function createSelectApplierClassNameListProvider(): ApplierClassNameListProvider
    {
        return $this->injectableFactory->create(ApplierClassNameListProvider::class);
    }

    /**
     * @return TEntity|null
     */
    protected function getEntityEvenDeleted(string $id): ?Entity
    {
        $query = $this->entityManager
            ->getQueryBuilder()
            ->select()
            ->from($this->entityType)
            ->where([
                'id' => $id,
            ])
            ->withDeleted()
            ->build();

        return $this->getRepository()
            ->clone($query)
            ->findOne();
    }

    /**
     * Restore a deleted record.
     *
     * @throws NotFound If not found.
     * @throws Forbidden If no access.
     */
    public function restoreDeleted(string $id): void
    {
        if (!$this->user->isAdmin()) {
            throw new Forbidden();
        }

        $entity = $this->getEntityEvenDeleted($id);

        if (!$entity) {
            throw new NotFound();
        }

        if (!$entity->get('deleted')) {
            throw new Forbidden();
        }

        $this->getRepository()->restoreDeleted($entity->getId());
    }

    public function getMaxSelectTextAttributeLength(): ?int
    {
        if ($this->maxSelectTextAttributeLengthDisabled) {
            return null;
        }

        if ($this->maxSelectTextAttributeLength) {
            return $this->maxSelectTextAttributeLength;
        }

        return $this->config->get('maxSelectTextAttributeLengthForList') ??
            self::MAX_SELECT_TEXT_ATTRIBUTE_LENGTH;
    }

    /**
     * Find linked records.
     *
     * @param non-empty-string $link
     * @return RecordCollection<Entity>
     * @throws NotFound If a record not found.
     * @throws Forbidden If no access.
     * @throws BadRequest
     * @throws Error
     */
    public function findLinked(string $id, string $link, SearchParams $searchParams): RecordCollection
    {
        if ($link === '') {
            throw new InvalidArgumentException();
        }

        if (!$this->acl->check($this->entityType, AclTable::ACTION_READ)) {
            throw new ForbiddenSilent("No access.");
        }

        $entity = $this->getRepository()->getById($id);

        if (!$entity) {
            throw new NotFound();
        }

        if (!$this->acl->check($entity, AclTable::ACTION_READ)) {
            throw new ForbiddenSilent();
        }

        $this->processForbiddenLinkReadCheck($link);

        if ($methodResult = $this->processFindLinkedMethod($id, $link, $searchParams)) {
            return $methodResult;
        }

        $foreignEntityType = $this->entityManager
            ->getDefs()
            ->getEntity($this->entityType)
            ->getRelation($link)
            ->getForeignEntityType();

        $linkParams = $this->linkParams[$link] ?? [];

        $skipAcl = $linkParams['skipAcl'] ?? false;

        if (!$skipAcl) {
            if (!$this->acl->check($foreignEntityType, AclTable::ACTION_READ)) {
                throw new Forbidden();
            }
        }

        $recordService = $this->recordServiceContainer->get($foreignEntityType);

        $disableCountPropertyName = 'findLinked' . ucfirst($link) . 'CountQueryDisabled';

        $disableCount =
            property_exists($this, $disableCountPropertyName) &&
            $this->$disableCountPropertyName;

        $maxSize = $searchParams->getMaxSize();

        if ($disableCount && $maxSize) {
            $searchParams = $searchParams->withMaxSize($maxSize + 1);
        }

        $preparedSearchParams = $this->prepareLinkSearchParams(
            $recordService->prepareSearchParams($searchParams),
            $link
        );

        $selectBuilder = $this->selectBuilderFactory->create();

        $selectBuilder
            ->from($foreignEntityType)
            ->withSearchParams($preparedSearchParams);

        if (!$skipAcl) {
            $selectBuilder->withStrictAccessControl();
        }
        else {
            $selectBuilder->withComplexExpressionsForbidden();
            $selectBuilder->withWherePermissionCheck();
        }

        $query = $selectBuilder->build();

        $collection = $this->entityManager
            ->getRDBRepository($this->entityType)
            ->getRelation($entity, $link)
            ->clone($query)
            ->find();

        foreach ($collection as $itemEntity) {
            $this->loadListAdditionalFields($itemEntity, $preparedSearchParams);

            $recordService->prepareEntityForOutput($itemEntity);
        }

        if ($disableCount) {
            return RecordCollection::createNoCount($collection, $maxSize);
        }

        $total = $this->entityManager
            ->getRDBRepository($this->entityType)
            ->getRelation($entity, $link)
            ->clone($query)
            ->count();

        return RecordCollection::create($collection, $total);
    }

    /**
     * @param string $link
     * @return ?RecordCollection<Entity>
     * @throws Forbidden
     * @throws NotFound
     */
    private function processFindLinkedMethod(string $id, string $link, SearchParams $searchParams): ?RecordCollection
    {
        if ($link === 'followers') {
            return $this->findLinkedFollowers($id, $searchParams);
        }

        $methodName = 'findLinked' . ucfirst($link);

        if (method_exists($this, $methodName)) {
            return $this->$methodName($id, $searchParams);
        }

        return null;
    }

    /**
     * Link records.
     *
     * @throws BadRequest
     * @throws Forbidden
     * @throws NotFound
     */
    public function link(string $id, string $link, string $foreignId): void
    {
        if (!$this->acl->check($this->entityType)) {
            throw new Forbidden();
        }

        if (empty($id) || empty($link) || empty($foreignId)) {
            throw new BadRequest();
        }

        $this->processForbiddenLinkEditCheck($link);

        $entity = $this->getRepository()->getById($id);

        if (!$entity) {
            throw new NotFound();
        }

        if (!$entity instanceof CoreEntity) {
            throw new LogicException("Only core entities are supported.");
        }

        $this->getLinkCheck()->processLink($entity, $link);

        if ($this->processLinkMethod($id, $link, $foreignId)) {
            return;
        }

        $foreignEntityType = $entity->getRelationParam($link, 'entity');

        if (!$foreignEntityType) {
            throw new LogicException("Entity '$this->entityType' has not relation '$link'.");
        }

        $foreignEntity = $this->entityManager->getEntityById($foreignEntityType, $foreignId);

        if (!$foreignEntity) {
            throw new NotFound();
        }

        $this->getLinkCheck()->processLinkForeign($entity, $link, $foreignEntity);

        $this->recordHookManager->processBeforeLink($entity, $link, $foreignEntity);

        $this->getRepository()
            ->getRelation($entity, $link)
            ->relate($foreignEntity);
    }

    /**
     * Unlink records.
     *
     * @throws BadRequest
     * @throws Forbidden
     * @throws NotFound
      */
    public function unlink(string $id, string $link, string $foreignId): void
    {
        if (!$this->acl->check($this->entityType)) {
            throw new Forbidden();
        }

        if (empty($id) || empty($link) || empty($foreignId)) {
            throw new BadRequest();
        }

        $this->processForbiddenLinkEditCheck($link);

        $entity = $this->getRepository()->getById($id);

        if (!$entity) {
            throw new NotFound();
        }

        if (!$entity instanceof CoreEntity) {
            throw new LogicException("Only core entities are supported.");
        }

        $this->getLinkCheck()->processLink($entity, $link);

        if ($this->processUnlinkMethod($id, $link, $foreignId)) {
            return;
        }

        $foreignEntityType = $entity->getRelationParam($link, 'entity');

        if (!$foreignEntityType) {
            throw new LogicException("Entity '$this->entityType' has not relation '$link'.");
        }

        $foreignEntity = $this->entityManager->getEntityById($foreignEntityType, $foreignId);

        if (!$foreignEntity) {
            throw new NotFound();
        }

        $this->getLinkCheck()->processLinkForeign($entity, $link, $foreignEntity);

        $this->recordHookManager->processBeforeUnlink($entity, $link, $foreignEntity);

        $this->getRepository()
            ->getRelation($entity, $link)
            ->unrelate($foreignEntity);
    }

    /**
     * @throws Forbidden
     * @throws NotFound
     */
    private function processLinkMethod(string $id, string $link, string $foreignId): bool
    {
        if ($link === 'followers') {
            $this->linkFollowers($id, $foreignId);

            return true;
        }

        $methodName = 'link' . ucfirst($link);

        if (
            $link !== 'entity' &&
            $link !== 'entityMass' &&
            method_exists($this, $methodName)
        ) {
            $this->$methodName($id, $foreignId);

            return true;
        }

        return false;
    }

    /**
     * @throws Forbidden
     * @throws NotFound
     */
    private function processUnlinkMethod(string $id, string $link, string $foreignId): bool
    {
        if ($link === 'followers') {
            $this->unlinkFollowers($id, $foreignId);

            return true;
        }

        $methodName = 'unlink' . ucfirst($link);

        if (
            $link !== 'entity' &&
            method_exists($this, $methodName)
        ) {
            $this->$methodName($id, $foreignId);

            return true;
        }

        return false;
    }

    /**
     * @throws Forbidden
     * @throws NotFound
     * @throws ForbiddenSilent
     */
    protected function linkFollowers(string $id, string $foreignId): void
    {
        if (!$this->acl->check($this->entityType, AclTable::ACTION_EDIT)) {
            throw new Forbidden();
        }

        if (!$this->metadata->get(['scopes', $this->entityType, 'stream'])) {
            throw new NotFound();
        }

        $entity = $this->getRepository()->getById($id);

        if (!$entity) {
            throw new NotFound();
        }

        /** @var ?User $user */
        $user = $this->entityManager->getEntityById(User::ENTITY_TYPE, $foreignId);

        if (!$user) {
            throw new NotFound();
        }

        if (!$this->acl->check($entity, AclTable::ACTION_EDIT)) {
            throw new ForbiddenSilent("No 'edit' access.");
        }

        if (!$this->acl->check($entity, AclTable::ACTION_STREAM)) {
            throw new ForbiddenSilent("No 'stream' access.");
        }

        if (!$user->isPortal() && !$this->acl->check($user, AclTable::ACTION_READ)) {
            throw new ForbiddenSilent("No 'read' access to user.");
        }

        if ($user->isPortal() && $this->acl->getPermissionLevel('portal') !== AclTable::LEVEL_YES) {
            throw new ForbiddenSilent("No 'portal' permission.");
        }

        if (
            !$user->isPortal() &&
            $this->user->getId() !== $user->getId() &&
            !$this->acl->checkUserPermission($user, 'followerManagement')
        ) {
            throw new Forbidden();
        }

        $result = $this->getStreamService()->followEntity($entity, $foreignId);

        if (!$result) {
            throw ForbiddenSilent::createWithBody(
                "Could not add user to followers.",
                ErrorBody::create()
                    ->withMessageTranslation(
                        'couldNotAddFollowerUserHasNoAccessToStream',
                        'Stream',
                        [
                            'userName' => $user->getUserName() ?? '',
                        ]
                    )
                    ->encode()
            );
        }
    }

    /**
     * @throws Forbidden
     * @throws NotFound
     * @throws ForbiddenSilent
     */
    protected function unlinkFollowers(string $id, string $foreignId): void
    {
        if (!$this->acl->check($this->entityType, AclTable::ACTION_EDIT)) {
            throw new Forbidden();
        }

        if (!$this->metadata->get(['scopes', $this->entityType, 'stream'])) {
            throw new NotFound();
        }

        $entity = $this->getRepository()->getById($id);

        if (!$entity) {
            throw new NotFound();
        }

        /** @var ?User $user */
        $user = $this->entityManager->getEntityById(User::ENTITY_TYPE, $foreignId);

        if (!$user) {
            throw new NotFound();
        }

        if (!$this->acl->check($entity, AclTable::ACTION_EDIT)) {
            throw new ForbiddenSilent("No 'edit' access.");
        }

        if (!$this->acl->check($entity, AclTable::ACTION_STREAM)) {
            throw new ForbiddenSilent("No 'stream' access.");
        }

        if (!$user->isPortal() && !$this->acl->check($user, AclTable::ACTION_READ)) {
            throw new ForbiddenSilent("No 'read' access to user.");
        }

        if ($user->isPortal() && $this->acl->getPermissionLevel('portal') !== AclTable::LEVEL_YES) {
            throw new ForbiddenSilent("No 'portal' permission.");
        }

        if (
            !$user->isPortal() &&
            $this->user->getId() !== $user->getId() &&
            !$this->acl->checkUserPermission($user, 'followerManagement')
        ) {
            throw new Forbidden();
        }

        $this->getStreamService()->unfollowEntity($entity, $foreignId);
    }

    /**
     * @throws BadRequest
     * @throws Forbidden
     * @throws NotFound
     * @throws Error
     */
    public function massLink(string $id, string $link, SearchParams $searchParams): bool
    {
        if (!$this->acl->check($this->entityType, AclTable::ACTION_EDIT)) {
            throw new Forbidden();
        }

        if (!$id || !$link) {
            throw new BadRequest;
        }

        $this->processForbiddenLinkEditCheck($link);

        $entity = $this->getRepository()->getById($id);

        if (!$entity) {
            throw new NotFound();
        }

        if (!$this->acl->check($entity, AclTable::ACTION_EDIT)) {
            throw new Forbidden();
        }

        $methodName = 'massLink' . ucfirst($link);

        if (method_exists($this, $methodName)) {
            return $this->$methodName($id, $searchParams);
        }

        if (!$entity instanceof CoreEntity) {
            throw new LogicException("Only core entities are supported");
        }

        $foreignEntityType = $entity->getRelationParam($link, 'entity');

        if (empty($foreignEntityType)) {
            throw new LogicException("Link '$link' has no 'entity'.");
        }

        $accessActionRequired = AclTable::ACTION_EDIT;

        if (in_array($link, $this->noEditAccessRequiredLinkList)) {
            $accessActionRequired = AclTable::ACTION_READ;
        }

        if (!$this->acl->check($foreignEntityType, $accessActionRequired)) {
            throw new Forbidden();
        }

        $query = $this->selectBuilderFactory->create()
            ->from($foreignEntityType)
            ->withStrictAccessControl()
            ->withSearchParams($searchParams->withSelect(null))
            ->build();

        if ($this->acl->getLevel($foreignEntityType, $accessActionRequired) === AclTable::LEVEL_ALL) {
            $this->getRepository()
                ->getRelation($entity, $link)
                ->massRelate($query);

            return true;
        }

        $countRelated = 0;

        $foreignCollection = $this->entityManager
            ->getRDBRepository($foreignEntityType)
            ->clone($query)
            ->find();

        foreach ($foreignCollection as $foreignEntity) {
            if (!$this->acl->check($foreignEntity, $accessActionRequired)) {
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

    /**
     * @throws Forbidden
     */
    protected function processForbiddenLinkReadCheck(string $link): void
    {
        $forbiddenLinkList = $this->acl
            ->getScopeForbiddenLinkList($this->entityType);

        if (in_array($link, $forbiddenLinkList)) {
            throw new Forbidden();
        }

        if (in_array($link, $this->forbiddenLinkList)) {
            throw new Forbidden();
        }

        if (in_array($link, $this->internalLinkList)) {
            throw new Forbidden();
        }

        if (!$this->user->isAdmin() && in_array($link, $this->onlyAdminLinkList)) {
            throw new Forbidden();
        }
    }

    /**
     * @throws Forbidden
     */
    protected function processForbiddenLinkEditCheck(string $link): void
    {
        $forbiddenLinkList = $this->acl
            ->getScopeForbiddenLinkList($this->entityType, AclTable::ACTION_EDIT);

        if (in_array($link, $forbiddenLinkList)) {
            throw new Forbidden();
        }

        if (in_array($link, $this->forbiddenLinkList)) {
            throw new Forbidden();
        }

        if (in_array($link, $this->readOnlyLinkList)) {
            throw new Forbidden();
        }

        if (!$this->user->isAdmin() && in_array($link, $this->nonAdminReadOnlyLinkList)) {
            throw new Forbidden();
        }

        if (!$this->user->isAdmin() && in_array($link, $this->onlyAdminLinkList)) {
            throw new Forbidden();
        }
    }

    /**
     * Follow a record.
     *
     * @param string $id A record ID.
     * @param string|null $userId A user ID. If not specified then a current user will be used.
     *
     * @throws NotFoundSilent
     * @throws Forbidden
     */
    public function follow(string $id, ?string $userId = null): void
    {
        if (!$this->acl->check($this->entityType, AclTable::ACTION_STREAM)) {
            throw new Forbidden();
        }

        $entity = $this->getRepository()->getById($id);

        if (!$entity) {
            throw new NotFoundSilent();
        }

        if (!$this->acl->check($entity, AclTable::ACTION_STREAM)) {
            throw new Forbidden();
        }

        if (empty($userId)) {
            $userId = $this->user->getId();
        }

        $this->getStreamService()->followEntity($entity, $userId);
    }

    /**
     * Unfollow a record.
     *
     * @param string $id A record ID.
     * @param string|null $userId A user ID. If not specified then a current user will be used.
     *
     * @throws NotFoundSilent
     */
    public function unfollow(string $id, ?string $userId = null): void
    {
        $entity = $this->getRepository()->getById($id);

        if (!$entity) {
            throw new NotFoundSilent();
        }

        if (empty($userId)) {
            $userId = $this->user->getId();
        }

        $this->getStreamService()->unfollowEntity($entity, $userId);
    }

    private function getDuplicateFinder(): DuplicateFinder
    {
        if (!$this->duplicateFinder) {
            $this->duplicateFinder = $this->injectableFactory->create(DuplicateFinder::class);
        }

        return $this->duplicateFinder;
    }

    /**
     * Check whether an entity has a duplicate.
     *
     * @param TEntity $entity
     */
    public function checkIsDuplicate(Entity $entity): bool
    {
        $finder = $this->getDuplicateFinder();

        // For backward compatibility.
        if (method_exists($this, 'getDuplicateWhereClause')) {
            $whereClause = $this->getDuplicateWhereClause($entity, (object) []);

            if (!$whereClause) {
                return false;
            }

            return $finder->checkByWhere($entity, WhereClause::fromRaw($whereClause));
        }

        return $finder->check($entity);
    }

    /**
     * Find duplicates for an entity.
     *
     * @return ?Collection<TEntity>
     */
    public function findDuplicates(Entity $entity): ?Collection
    {
        $finder = $this->getDuplicateFinder();

        // For backward compatibility.
        if (method_exists($this, 'getDuplicateWhereClause')) {
            $whereClause = $this->getDuplicateWhereClause($entity, (object) []);

            if (!$whereClause) {
                return null;
            }

            /** @var ?Collection<TEntity> */
            return $finder->findByWhere($entity, WhereClause::fromRaw($whereClause));
        }

        /** @var ?Collection<TEntity> */
        return $finder->find($entity);
    }

    /**
     * Prepare an entity for output. Clears not allowed attributes.
     *
     * @param TEntity $entity
     * @return void
     */
    public function prepareEntityForOutput(Entity $entity)
    {
        foreach ($this->internalAttributeList as $attribute) {
            $entity->clear($attribute);
        }

        foreach ($this->forbiddenAttributeList as $attribute) {
            $entity->clear($attribute);
        }

        if (!$this->user->isAdmin()) {
            foreach ($this->onlyAdminAttributeList as $attribute) {
                $entity->clear($attribute);
            }
        }

        $forbiddenAttributeList = $this->acl->getScopeForbiddenAttributeList($entity->getEntityType());

        foreach ($forbiddenAttributeList as $attribute) {
            $entity->clear($attribute);
        }
    }

    /**
     * @return RecordCollection<User>
     * @throws NotFound
     * @throws Forbidden
     */
    protected function findLinkedFollowers(string $id, SearchParams $params): RecordCollection
    {
        $entity = $this->getRepository()->getById($id);

        if (!$entity) {
            throw new NotFound();
        }

        if (!$this->acl->check($entity, AclTable::ACTION_READ)) {
            throw new Forbidden();
        }

        return $this->getStreamService()->findEntityFollowers($entity, $params);
    }

    private function createEntityDuplicator(): EntityDuplicator
    {
        return $this->injectableFactory->create(EntityDuplicator::class);
    }

    /**
     * @throws BadRequest
     * @throws Forbidden
     * @throws ForbiddenSilent
     * @throws NotFound
     */
    public function getDuplicateAttributes(string $id): stdClass
    {
        if (!$id) {
            throw new BadRequest("No ID.");
        }

        if (!$this->acl->check($this->entityType, AclTable::ACTION_CREATE)) {
            throw new Forbidden("No 'create' access.");
        }

        if (!$this->acl->check($this->entityType, AclTable::ACTION_READ)) {
            throw new Forbidden("No 'read' access.");
        }

        $entity = $this->getEntity($id);

        if (!$entity) {
            throw new NotFound("Record not found.");
        }

        $attributes = $this->createEntityDuplicator()->duplicate($entity);

        foreach ($this->duplicateIgnoreAttributeList as $attribute) {
            unset($attributes->$attribute);
        }

        if ($this->acl->getPermissionLevel('assignment') === AclTable::LEVEL_NO) {
            unset($attributes->assignedUserId);
            unset($attributes->assignedUserName);
            unset($attributes->assignedUsersIds);
        }

        return $attributes;
    }

    /**
     * @param TEntity $entity
     */
    protected function afterCreateProcessDuplicating(Entity $entity, CreateParams $params): void
    {
        $duplicatingEntityId = $params->getDuplicateSourceId();

        if (!$duplicatingEntityId) {
            return;
        }

        /** @var ?TEntity $duplicatingEntity */
        $duplicatingEntity = $this->entityManager->getEntityById($entity->getEntityType(), $duplicatingEntityId);

        if (!$duplicatingEntity) {
            return;
        }

        if (!$this->acl->check($duplicatingEntity, AclTable::ACTION_READ)) {
            return;
        }

        $this->duplicateLinks($entity, $duplicatingEntity);
    }

    /**
     * @param TEntity $entity
     * @param TEntity $duplicatingEntity
     */
    protected function duplicateLinks(Entity $entity, Entity $duplicatingEntity): void
    {
        $repository = $this->getRepository();

        foreach ($this->duplicatingLinkList as $link) {
            $linkedList = $repository
                ->getRelation($duplicatingEntity, $link)
                ->find();

            foreach ($linkedList as $linked) {
                $repository
                    ->getRelation($entity, $link)
                    ->relate($linked);
            }
        }
    }

    /**
     * @deprecated
     * @todo Remove in v7.6.
     * @param string $type
     * @return string[]
     */
    protected function getFieldByTypeList($type)
    {
        return $this->fieldUtil->getFieldByTypeList($this->entityType, $type);
    }

    public function prepareSearchParams(SearchParams $searchParams): SearchParams
    {
        return $this
            ->prepareSearchParamsSelect($searchParams)
            ->withMaxTextAttributeLength(
                $this->getMaxSelectTextAttributeLength()
            );
    }

    protected function prepareSearchParamsSelect(SearchParams $searchParams): SearchParams
    {
        if ($this->forceSelectAllAttributes) {
            return $searchParams->withSelect(null);
        }

        if ($this->selectAttributeList) {
            return $searchParams->withSelect($this->selectAttributeList);
        }

        if (count($this->mandatorySelectAttributeList) && $searchParams->getSelect() !== null) {
            $select = array_unique(
                array_merge(
                    $searchParams->getSelect(),
                    $this->mandatorySelectAttributeList
                )
            );

            return $searchParams->withSelect($select);
        }

        return $searchParams;
    }

    protected function prepareLinkSearchParams(SearchParams $searchParams, string $link): SearchParams
    {
        if ($searchParams->getSelect() === null) {
            return $searchParams;
        }

        $mandatorySelectAttributeList = $this->linkMandatorySelectAttributeList[$link] ?? null;

        if ($mandatorySelectAttributeList === null) {
            return $searchParams;
        }

        $select = array_unique(
            array_merge(
                $searchParams->getSelect(),
                $mandatorySelectAttributeList
            )
        );

        return $searchParams->withSelect($select);
    }

    /**
     * @param TEntity $entity
     */
    private function processApiBeforeCreateApiScript(Entity $entity, CreateParams $params): void
    {
        $processor = $this->injectableFactory->create(FormulaProcessor::class);

        $processor->processBeforeCreate($entity, $params);
    }

    /**
     * @param TEntity $entity
     */
    private function processApiBeforeUpdateApiScript(Entity $entity, UpdateParams $params): void
    {
        $processor = $this->injectableFactory->create(FormulaProcessor::class);

        $processor->processBeforeUpdate($entity, $params);
    }

    /**
     * @param TEntity $entity
     * @param stdClass $data
     * @return void
     */
    protected function beforeCreateEntity(Entity $entity, $data)
    {}

    /**
     * @param TEntity $entity
     * @param stdClass $data
     * @return void
     */
    protected function afterCreateEntity(Entity $entity, $data)
    {}

    /**
     * @param TEntity $entity
     * @param stdClass $data
     * @return void
     */
    protected function beforeUpdateEntity(Entity $entity, $data)
    {}

    /**
     * @param TEntity $entity
     * @param stdClass $data
     * @return void
     */
    protected function afterUpdateEntity(Entity $entity, $data)
    {}

    /**
     * @param TEntity $entity
     * @return void
     */
    protected function beforeDeleteEntity(Entity $entity)
    {}

    /**
     * @param TEntity $entity
     * @return void
     */
    protected function afterDeleteEntity(Entity $entity)
    {}
}
