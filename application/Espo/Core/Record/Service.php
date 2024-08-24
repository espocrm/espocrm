<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Core\Record;

use Espo\Core\Binding\BindingContainer;
use Espo\Core\Binding\BindingContainerBuilder;
use Espo\Core\Binding\ContextualBinder;
use Espo\Core\Exceptions\Conflict;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\ConflictSilent;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\ForbiddenSilent;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Exceptions\NotFoundSilent;
use Espo\Core\FieldSanitize\SanitizeManager;
use Espo\Core\ORM\Defs\AttributeParam;
use Espo\Core\ORM\Entity as CoreEntity;
use Espo\Core\ORM\Repository\Option\SaveOption;
use Espo\Core\Record\Access\LinkCheck;
use Espo\Core\Record\ActionHistory\Action;
use Espo\Core\Record\ActionHistory\ActionLogger;
use Espo\Core\Record\Defaults\Populator as DefaultsPopulator;
use Espo\Core\Record\Defaults\PopulatorFactory as DefaultsPopulatorFactory;
use Espo\Core\Record\Formula\Processor as FormulaProcessor;
use Espo\Core\Record\Input\Data;
use Espo\Core\Record\Input\Filter;
use Espo\Core\Record\Input\FilterProvider;
use Espo\Core\Select\Primary\Filters\One;
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
use Espo\Core\Record\Select\ApplierClassNameListProvider;
use Espo\Core\Select\SearchParams;
use Espo\Core\Di;
use Espo\ORM\Entity;
use Espo\ORM\Repository\RDBRepository;
use Espo\ORM\Collection;
use Espo\ORM\Query\Part\WhereClause;
use Espo\ORM\Type\AttributeType;
use Espo\Tools\Stream\Service as StreamService;
use Espo\Entities\User;

use stdClass;
use InvalidArgumentException;
use LogicException;
use RuntimeException;

use const E_USER_DEPRECATED;

/**
 * The layer between a controller and ORM repository. For CRUD and other operations with records.
 * Access control is processed here.
 *
 * Extending is not recommended. Use composition with metadata > recordDefs.
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
    Di\AssignmentCheckerManagerAware
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

    protected string $entityType;
    protected bool $getEntityBeforeUpdate = false;

    protected bool $maxSelectTextAttributeLengthDisabled = false;
    protected ?int $maxSelectTextAttributeLength = null;

    private ?StreamService $streamService = null;

    /**
     * @deprecated As of v8.2. Use metadata > recordDefs > relationships > {link} > linkRequiredAccess.
     * @todo Remove in v9.0.
     */
    protected bool $noEditAccessRequiredForLink = false;

    /**
     * @var string[]
     * @deprecated As of v8.2. Use metadata > recordDefs > relationships > {link} > linkRequiredAccess.
     * @todo Remove in v9.0.
     */
    protected array $noEditAccessRequiredLinkList = [];

    /**
     * @var array<string, string[]>
     * @deprecated As of v8.2. Use metadata > recordDefs > relationships > {link} > mandatoryAttributeList.
     * @todo Remove in v9.0.
     */
    protected array $linkMandatorySelectAttributeList = [];

    /**
     * @var string[]
     * @deprecated As of v8.2. Use metadata > recordDefs > duplicateLinkList.
     * @todo Remove in v9.0.
     */
    protected array $duplicatingLinkList = [];

    /**
     * @var bool
     * @deprecated As of v8.2. Use metadata > recordDefs > forceSelectAllAttributes.
     * @todo Remove in v9.0.
     */
    protected $forceSelectAllAttributes = false;

    /**
     * @var string[]
     * @deprecated As of v8.2. Use metadata > entityAcl.
     * @todo Remove in v9.0.
     */
    protected array $forbiddenAttributeList = [];

    /**
     * @var string[]
     * @deprecated As of v8.2. Use metadata > entityAcl.
     * @todo Remove in v9.0.
     */
    protected array $internalAttributeList = [];

    /**
     * @var string[]
     * @deprecated As of v8.2. Use metadata > entityAcl.
     * @todo Remove in v9.0.
     */
    protected $onlyAdminAttributeList = [];

    /**
     * @var string[]
     * @deprecated As of v8.2. Use metadata > entityAcl.
     * @todo Remove in v9.0.
     */
    protected $readOnlyAttributeList = [];

    /**
     * @var string[]
     * @deprecated As of v8.2. Use metadata > entityAcl.
     * @todo Remove in v9.0.
     */
    protected $nonAdminReadOnlyAttributeList = [];

    /**
     * @var string[]
     * @deprecated As of v8.2. Use metadata > entityAcl.
     * @todo Remove in v9.0.
     */
    protected $forbiddenLinkList = [];

    /**
     * @var string[]
     * @deprecated As of v8.2. Use metadata > entityAcl.
     * @todo Remove in v9.0.
     */
    protected $internalLinkList = [];

    /**
     * @var string[]
     * @deprecated As of v8.2. Use metadata > entityAcl.
     * @todo Remove in v9.0.
     */
    protected $readOnlyLinkList = [];

    /**
     * @var string[]
     * @deprecated As of v8.2. Use metadata > entityAcl.
     * @todo Remove in v9.0.
     */
    protected $nonAdminReadOnlyLinkList = [];

    /**
     * @var string[]
     * @deprecated As of v8.2. Use metadata > entityAcl.
     * @todo Remove in v9.0.
     */
    protected $onlyAdminLinkList = [];

    /**
     * @deprecated As of v8.2. Use 'duplicateIgnore' metadata or field duplicators.
     * @todo Remove in v9.0.
     * @var string[]
     */
    protected $duplicateIgnoreAttributeList = [];

    /**
     * @var string[]
     * @deprecated As of v8.0. Use `suppressValidationList` metadata parameter.
     * @todo Remove in v9.0.
     */
    protected $validateSkipFieldList = [];

    /**
     * @var string[]
     * @deprecated As of v8.2. Use recordDefs > mandatoryAttributeList.
     * @todo Remove in v10.0. Fix usages.
     */
    protected $mandatorySelectAttributeList = [];

    /**
     * @var bool
     * @deprecated As of v8.2. Use metadata > recordDefs.
     * @todo Remove in v9.0.
     */
    protected $checkForDuplicatesInUpdate = false;

    /**
     * @var bool
     * @deprecated As of v8.2. Use metadata > recordDefs.
     * @todo Remove in v9.0.
     */
    protected $actionHistoryDisabled = false;

    private ?ListLoadProcessor $listLoadProcessor = null;
    private ?DuplicateFinder $duplicateFinder = null;
    private ?LinkCheck $linkCheck = null;
    private ?ActionLogger $actionLogger = null;
    /** @var ?DefaultsPopulator<Entity> */
    private ?DefaultsPopulator $defaultsPopulator = null;
    private ?HookManager $recordHookManager = null;
    /** @var ?Filter[] */
    private ?array $createFilterList = null;
    /** @var ?Filter[] */
    private ?array $updateFilterList = null;
    /** @var ?Output\Filter<Entity>[] */
    private ?array $outputFilterList = null;

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
     * @param Action::* $action
     * @noinspection PhpDocSignatureInspection
     */
    public function processActionHistoryRecord(string $action, Entity $entity): void
    {
        /** @noinspection PhpDeprecationInspection */
        if (
            $this->actionHistoryDisabled ||
            $this->config->get('actionHistoryDisabled') ||
            $this->metadata->get("recordDefs.$this->entityType.actionHistoryDisabled")
        ) {
            return;
        }

        $this->getActionLogger()->log($action, $entity);
    }

    private function getActionLogger(): ActionLogger
    {
        if (!$this->actionLogger) {
            $this->actionLogger = $this->injectableFactory->createResolved(ActionLogger::class);
        }

        return $this->actionLogger;
    }

    /**
     * Read a record by ID. Access control check is performed.
     *
     * @param non-empty-string $id
     * @return TEntity
     * @throws NotFoundSilent If not found.
     * @throws Forbidden If no read access.
     * @noinspection PhpDocSignatureInspection
     */
    public function read(string $id, ReadParams $params): Entity
    {
        if ($id === '') {
            throw new InvalidArgumentException();
        }

        if (!$this->acl->check($this->entityType, AclTable::ACTION_READ)) {
            throw new ForbiddenSilent("No read access.");
        }

        $entity = $this->getEntity($id);

        if (!$entity) {
            throw new NotFoundSilent("Record $id does not exist.");
        }

        $this->getRecordHookManager()->processBeforeRead($entity, $params);
        $this->processActionHistoryRecord(Action::READ, $entity);

        return $entity;
    }

    /**
     * Get an entity by ID. Access control check is performed.
     *
     * @throws Forbidden If no read access.
     * @return ?TEntity
     * @noinspection PhpDocSignatureInspection
     */
    public function getEntity(string $id): ?Entity
    {
        try {
            $builder = $this->selectBuilderFactory
                ->create()
                ->from($this->entityType)
                ->withSearchParams(
                    SearchParams::create()
                        ->withSelect(['*'])
                        ->withPrimaryFilter(One::NAME)
                )
                ->withAdditionalApplierClassNameList(
                    $this->createSelectApplierClassNameListProvider()->get($this->entityType)
                );

            // @todo Apply access control filter. If a parameter enabled? Check compatibility.

            $query = $builder
                ->buildQueryBuilder()
                ->order([])
                ->build();
        }
        catch (BadRequest $e) {
            throw new RuntimeException($e->getMessage());
        }

        $entity = $this->getRepository()
            ->clone($query)
            ->where(['id' => $id])
            ->findOne();

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

        /** @noinspection PhpDeprecationInspection */
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
        return $this->injectableFactory->createWithBinding(ReadLoadProcessor::class, $this->createBinding());
    }

    private function getListLoadProcessor(): ListLoadProcessor
    {
        if (!$this->listLoadProcessor) {
            $this->listLoadProcessor =
                $this->injectableFactory->createWithBinding(ListLoadProcessor::class, $this->createBinding());
        }

        return $this->listLoadProcessor;
    }

    /**
     * @param TEntity $entity
     * @return void
     * @todo Add void return type in v9.0.
     * @noinspection PhpDocSignatureInspection
     */
    public function loadAdditionalFields(Entity $entity)
    {
        $loadProcessor = $this->createReadLoadProcessor();

        $loadProcessor->process($entity);
    }

    /**
     * @param Entity $entity
     * @todo Make private in v9.0
     * @deprecated
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
     * Validate an entity.
     *
     * @param TEntity $entity An entity.
     * @param stdClass $data Raw input data.
     * @throws BadRequest
     * @noinspection PhpDocSignatureInspection
     */
    public function processValidation(Entity $entity, stdClass $data): void
    {
        /** @noinspection PhpDeprecationInspection */
        $params = FieldValidationParams
            ::create()
            ->withSkipFieldList($this->validateSkipFieldList);

        /** @noinspection PhpDeprecationInspection */
        if (!empty($this->validateSkipFieldList)) {
            trigger_error(
                '$validateSkipFieldList is deprecated and will be removed in v9.0.',
                E_USER_DEPRECATED
            );
        }

        $this->fieldValidationManager->process($entity, $data, $params);
    }

    /**
     * @param TEntity $entity
     * @throws Forbidden
     * @noinspection PhpDocSignatureInspection
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
     * @noinspection PhpDocSignatureInspection
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
                        /** @noinspection PhpDeprecationInspection */
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
     * Sanitize input data.
     *
     * @param stdClass $data Input data.
     * @since 8.1.0
     */
    public function sanitizeInput(stdClass $data): void
    {
        $manager = $this->injectableFactory->create(SanitizeManager::class);

        $manager->process($this->entityType, $data);
    }

    protected function filterInput(stdClass $data): void
    {
        /** @noinspection PhpDeprecationInspection */
        foreach($this->readOnlyAttributeList as $attribute) {
            unset($data->$attribute);
        }

        /** @noinspection PhpDeprecationInspection */
        foreach ($this->forbiddenAttributeList as $attribute) {
            unset($data->$attribute);
        }

        if (!$this->user->isAdmin()) {
            /** @noinspection PhpDeprecationInspection */
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
            /** @noinspection PhpDeprecationInspection */
            foreach ($this->nonAdminReadOnlyAttributeList as $attribute) {
                unset($data->$attribute);
            }
        }

        $this->filterInputForeignAttributes($data);
    }

    private function filterInputForeignAttributes(stdClass $data): void
    {
        $entityDefs = $this->entityManager->getDefs()->tryGetEntity($this->entityType);

        if (!$entityDefs) {
            return;
        }

        foreach ($entityDefs->getAttributeList() as $attributeDefs) {
            if (
                $attributeDefs->getType() !== AttributeType::FOREIGN &&
                !$attributeDefs->getParam(AttributeParam::IS_LINK_MULTIPLE_NAME_MAP)
            ) {
                continue;
            }

            if (
                // link-one
                $attributeDefs->getType() === AttributeType::FOREIGN &&
                $attributeDefs->getParam('attributeRole') === 'id'
            ) {
                continue;
            }

            $attribute = $attributeDefs->getName();

            unset($data->$attribute);
        }
    }

    private function filterInputSystemAttributes(stdClass $data): void
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
    }

    public function filterCreateInput(stdClass $data): void
    {
        $this->filterInputSystemAttributes($data);
        $this->filterInput($data);

        /** @noinspection PhpDeprecationInspection */
        $this->handleInput($data);
        /** @noinspection PhpDeprecationInspection */
        $this->handleCreateInput($data);

        $wrappedData = new Data($data);

        foreach ($this->getCreateFilterList() as $filter) {
            $filter->filter($wrappedData);
        }
    }

    public function filterUpdateInput(stdClass $data): void
    {
        $this->filterInputSystemAttributes($data);
        $this->filterInput($data);
        $this->filterReadOnlyAfterCreate($data);

        /** @noinspection PhpDeprecationInspection */
        $this->handleInput($data);

        $wrappedData = new Data($data);

        foreach ($this->getUpdateFilterList() as $filter) {
            $filter->filter($wrappedData);
        }
    }

    private function createFilterProvider(): FilterProvider
    {
        return $this->injectableFactory->createWithBinding(FilterProvider::class, $this->createBinding());
    }

    /**
     * @return Filter[]
     */
    private function getCreateFilterList(): array
    {
        if ($this->createFilterList === null) {
            $this->createFilterList = $this->createFilterProvider()->getForCreate($this->entityType);
        }

        return $this->createFilterList;
    }

    /**
     * @return Filter[]
     */
    private function getUpdateFilterList(): array
    {
        if ($this->updateFilterList === null) {
            $this->updateFilterList = $this->createFilterProvider()->getForUpdate($this->entityType);
        }

        return $this->updateFilterList;
    }

    private function filterReadOnlyAfterCreate(stdClass $data): void
    {
        $fieldDefsList = $this->entityManager
            ->getDefs()
            ->getEntity($this->entityType)
            ->getFieldList();

        foreach ($fieldDefsList as $fieldDefs) {
            if (!$fieldDefs->getParam('readOnlyAfterCreate')) {
                continue;
            }

            $attributeList = $this->fieldUtil->getAttributeList($this->entityType, $fieldDefs->getName());

            foreach ($attributeList as $attribute) {
                unset($data->$attribute);
            }
        }
    }

    /**
     * @deprecated As of v7.0. Use filterCreateInput or filterUpdateInput. Or better don't extend the class.
     * Use entityAcl, app > acl, roles to restrict write access for specific fields.
     * @todo Remove in v9.0.
     * @param stdClass $data
     * @return void
     */
    protected function handleCreateInput($data)
    {
    }

    /**
     * @deprecated As of v7.0. Use filterCreateInput or filterUpdateInput. Or better don't extend the class.
     * Use entityAcl, app > acl, roles to restrict write access for specific fields.
     * @todo Remove in v9.0.
     * @param stdClass $data
     * @return void
     */
    protected function handleInput($data)
    {
    }

    /**
     * @param TEntity $entity
     * @throws Conflict
     * @noinspection PhpDocSignatureInspection
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
     * @noinspection PhpDocSignatureInspection
     */
    protected function processDuplicateCheck(Entity $entity): void
    {
        $duplicates = $this->findDuplicates($entity);

        if (!$duplicates) {
            return;
        }

        foreach ($duplicates as $e) {
            /** @noinspection PhpDeprecationInspection */
            $this->prepareEntityForOutput($e);
        }

        throw ConflictSilent::createWithBody('duplicate', Json::encode($duplicates->getValueMapList()));
    }

    /**
     * @param TEntity $entity
     * @noinspection PhpDocSignatureInspection
     * @noinspection PhpUnusedParameterInspection
     */
    public function populateDefaults(Entity $entity, stdClass $data): void
    {
        $this->getDefaultsPopulator()->populate($entity);
    }

    /**
     * @return DefaultsPopulator<Entity>
     */
    private function getDefaultsPopulator(): DefaultsPopulator
    {
        if (!$this->defaultsPopulator) {
            $this->defaultsPopulator = $this->injectableFactory
                ->create(DefaultsPopulatorFactory::class)
                ->create($this->entityType);
        }

        return $this->defaultsPopulator;
    }

    /**
     * Create a record.
     *
     * @return TEntity
     * @throws BadRequest
     * @throws Forbidden If no create access.
     * @throws Conflict
     * @noinspection PhpDocSignatureInspection
     */
    public function create(stdClass $data, CreateParams $params): Entity
    {
        if (!$this->acl->check($this->entityType, AclTable::ACTION_CREATE)) {
            throw new ForbiddenSilent("No create access.");
        }

        $entity = $this->getRepository()->getNew();

        $this->filterCreateInput($data);
        $this->sanitizeInput($data);

        $entity->set($data);
        $this->populateDefaults($entity, $data);

        $this->processValidation($entity, $data);
        $this->checkEntityCreateAccess($entity);
        $this->processAssignmentCheck($entity);
        $this->getLinkCheck()->processFields($entity);

        if (!$params->skipDuplicateCheck()) {
            $this->processDuplicateCheck($entity);
        }

        $this->processApiBeforeCreateApiScript($entity, $params);
        $this->getRecordHookManager()->processBeforeCreate($entity, $params);
        /** @noinspection PhpDeprecationInspection */
        $this->beforeCreateEntity($entity, $data);

        $this->entityManager->saveEntity($entity, [
            SaveOption::API => true,
            SaveOption::KEEP_NEW => true,
            SaveOption::DUPLICATE_SOURCE_ID => $params->getDuplicateSourceId(),
        ]);

        $this->getRecordHookManager()->processAfterCreate($entity, $params);

        $entity->setAsNotNew();
        $entity->updateFetchedValues();

        /** @noinspection PhpDeprecationInspection */
        $this->afterCreateEntity($entity, $data);
        /** @noinspection PhpDeprecationInspection */
        $this->afterCreateProcessDuplicating($entity, $params);

        $this->loadAdditionalFields($entity);

        /** @noinspection PhpDeprecationInspection */
        $this->prepareEntityForOutput($entity);
        $this->processActionHistoryRecord(Action::CREATE, $entity);

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
     * @noinspection PhpDocSignatureInspection
     */
    public function update(string $id, stdClass $data, UpdateParams $params): Entity
    {
        if (!$this->acl->check($this->entityType, AclTable::ACTION_EDIT)) {
            throw new ForbiddenSilent("No edit access.");
        }

        if (!$id) {
            throw new BadRequest("ID is empty.");
        }

        $this->filterUpdateInput($data);
        $this->sanitizeInput($data);

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
        $this->getLinkCheck()->processFields($entity);

        /** @noinspection PhpDeprecationInspection */
        $checkForDuplicates =
            $this->metadata->get(['recordDefs', $this->entityType, 'updateDuplicateCheck']) ??
            $this->checkForDuplicatesInUpdate;

        if ($checkForDuplicates && !$params->skipDuplicateCheck()) {
            $this->processDuplicateCheck($entity);
        }

        $this->processApiBeforeUpdateApiScript($entity, $params);
        $this->getRecordHookManager()->processBeforeUpdate($entity, $params);
        /** @noinspection PhpDeprecationInspection */
        $this->beforeUpdateEntity($entity, $data);

        $this->entityManager->saveEntity($entity, [
            SaveOption::API => true,
            SaveOption::KEEP_DIRTY => true,
        ]);

        $this->getRecordHookManager()->processAfterUpdate($entity, $params);
        $entity->updateFetchedValues();

        /** @noinspection PhpDeprecationInspection */
        $this->afterUpdateEntity($entity, $data);

        if ($this->metadata->get(['recordDefs', $this->entityType, 'loadAdditionalFieldsAfterUpdate'])) {
            $this->loadAdditionalFields($entity);
        }

        /** @noinspection PhpDeprecationInspection */
        $this->prepareEntityForOutput($entity);
        $this->processActionHistoryRecord(Action::UPDATE, $entity);

        return $entity;
    }

    /**
     * Delete a record.
     *
     * @throws Forbidden
     * @throws BadRequest
     * @throws NotFound
     * @throws Conflict
     */
    public function delete(string $id, DeleteParams $params): void
    {
        if (!$this->acl->check($this->entityType, AclTable::ACTION_DELETE)) {
            throw new ForbiddenSilent("No delete access.");
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

        $this->getRecordHookManager()->processBeforeDelete($entity, $params);
        /** @noinspection PhpDeprecationInspection */
        $this->beforeDeleteEntity($entity);

        $this->getRepository()->remove($entity);

        /** @noinspection PhpDeprecationInspection */
        $this->afterDeleteEntity($entity);
        $this->getRecordHookManager()->processAfterDelete($entity, $params);
        $this->processActionHistoryRecord(Action::DELETE, $entity);
    }

    /**
     * Find records.
     *
     * @return RecordCollection<TEntity>
     * @throws Forbidden
     * @throws BadRequest
     */
    public function find(SearchParams $searchParams, ?FindParams $params = null): RecordCollection
    {
        if (!$this->acl->check($this->entityType, AclTable::ACTION_READ)) {
            throw new ForbiddenSilent("No read access.");
        }

        if (!$params) {
            $params = FindParams::create();
        }

        $disableCount = $params->noTotal() ||
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
            /** @noinspection PhpDeprecationInspection */
            $this->loadListAdditionalFields($entity, $preparedSearchParams);

            /** @noinspection PhpDeprecationInspection */
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

    private function createSelectApplierClassNameListProvider(): ApplierClassNameListProvider
    {
        return $this->injectableFactory->create(ApplierClassNameListProvider::class);
    }

    /**
     * @return TEntity|null
     * @noinspection PhpDocSignatureInspection
     */
    private function getEntityEvenDeleted(string $id): ?Entity
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
            throw new Forbidden("No 'deleted' attribute.");
        }

        $this->entityManager->getTransactionManager()
            ->run(function () use ($entity) {
                $this->getRepository()->restoreDeleted($entity->getId());

                if (
                    $entity->hasAttribute('deleteId') &&
                    $this->metadata->get("entityDefs.$this->entityType.deleteId")
                ) {
                    $this->entityManager->refreshEntity($entity);

                    $entity->set('deleteId', '0');
                    $this->getRepository()->save($entity, [SaveOption::SILENT => true]);
                }
            });
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
            throw new ForbiddenSilent("No read access.");
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

        $skipAcl = $this->metadata
            ->get("recordDefs.$this->entityType.relationships.$link.selectAccessControlDisabled") ?? false;

        if (!$skipAcl && !$this->acl->check($foreignEntityType, AclTable::ACTION_READ)) {
            throw new Forbidden();
        }

        $recordService = $this->recordServiceContainer->get($foreignEntityType);

        $disableCount = $this->metadata
            ->get("recordDefs.$this->entityType.relationships.$link.countDisabled") ?? false;

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
            ->withSearchParams($preparedSearchParams)
            ->withAdditionalApplierClassNameList(
                $this->createSelectApplierClassNameListProvider()->get($foreignEntityType)
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
            ->getRDBRepository($this->entityType)
            ->getRelation($entity, $link)
            ->clone($query)
            ->find();

        foreach ($collection as $itemEntity) {
            /** @noinspection PhpDeprecationInspection */
            $this->loadListAdditionalFields($itemEntity, $preparedSearchParams);

            /** @noinspection PhpDeprecationInspection */
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
     * @throws BadRequest
     * @todo Remove in v9.0.
     */
    private function processFindLinkedMethod(string $id, string $link, SearchParams $searchParams): ?RecordCollection
    {
        $methodName = 'findLinked' . ucfirst($link);

        if (method_exists($this, $methodName)) {
            trigger_error(
                "Usage of $methodName method is deprecated and will be removed in v9.0.",
                E_USER_DEPRECATED
            );


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

        $this->getRecordHookManager()->processBeforeLink($entity, $link, $foreignEntity);

        $this->getRepository()
            ->getRelation($entity, $link)
            ->relate($foreignEntity, null, [SaveOption::API => true]);

        $this->getRecordHookManager()->processAfterLink($entity, $link, $foreignEntity);
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

        $this->getLinkCheck()->processUnlink($entity, $link);

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

        $this->getLinkCheck()->processUnlinkForeign($entity, $link, $foreignEntity);

        $this->getRecordHookManager()->processBeforeUnlink($entity, $link, $foreignEntity);

        $this->getRepository()
            ->getRelation($entity, $link)
            ->unrelate($foreignEntity, [SaveOption::API => true]);

        $this->getRecordHookManager()->processAfterUnlink($entity, $link, $foreignEntity);
    }

    /**
     * @throws Forbidden
     * @throws NotFound
     * @todo Remove in v9.0.
     */
    private function processLinkMethod(string $id, string $link, string $foreignId): bool
    {
        $methodName = 'link' . ucfirst($link);

        if ($link !== 'entity' && $link !== 'entityMass' && method_exists($this, $methodName)) {
            $this->$methodName($id, $foreignId);

            trigger_error(
                "Usage of $methodName method is deprecated and will be removed in v9.0.",
                E_USER_DEPRECATED
            );

            return true;
        }

        return false;
    }

    /**
     * @throws Forbidden
     * @throws NotFound
     * @todo Remove in v9.0.
     */
    private function processUnlinkMethod(string $id, string $link, string $foreignId): bool
    {
        $methodName = 'unlink' . ucfirst($link);

        if ($link !== 'entity' && method_exists($this, $methodName)) {
            $this->$methodName($id, $foreignId);

            trigger_error(
                "Usage of $methodName method is deprecated and will be removed in v9.0.",
                E_USER_DEPRECATED
            );

            return true;
        }

        return false;
    }

    /**
     * @throws BadRequest
     * @throws Forbidden
     * @throws NotFound
     */
    public function massLink(string $id, string $link, SearchParams $searchParams): bool
    {
        if (!$this->acl->check($this->entityType, AclTable::ACTION_EDIT)) {
            throw new Forbidden();
        }

        if (!$this->metadata->get("recordDefs.$this->entityType.relationships.$link.massLink")) {
            throw new Forbidden("Mass link is not allowed.");
        }

        $this->processForbiddenLinkEditCheck($link);

        $entity = $this->getRepository()->getById($id);

        if (!$entity) {
            throw new NotFound();
        }

        $this->getLinkCheck()->processLink($entity, $link);

        // Not used link-check deliberately. Only edit access.
        if (!$this->acl->check($entity, AclTable::ACTION_EDIT)) {
            throw new Forbidden();
        }

        if (!$entity instanceof CoreEntity) {
            throw new LogicException("Only core entities are supported.");
        }

        $foreignEntityType = $entity->getRelationParam($link, 'entity');

        if (!$foreignEntityType) {
            throw new LogicException("Link '$link' has no 'entity'.");
        }

        $accessActionRequired = $this->metadata
            ->get("recordDefs.$this->entityType.relationships.$link.linkRequiredForeignAccess") ??
            AclTable::ACTION_EDIT;

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
                ->massRelate($query, [SaveOption::API => true]);

            return true;
        }

        // @todo Apply access control filter if $accessActionRequired === 'read'. For better performance.

        $countRelated = 0;

        $foreignCollection = $this->entityManager
            ->getRDBRepository($foreignEntityType)
            ->clone($query)
            ->sth()
            ->find();

        foreach ($foreignCollection as $foreignEntity) {
            if (!$this->acl->check($foreignEntity, $accessActionRequired)) {
                continue;
            }

            $this->getRepository()
                ->getRelation($entity, $link)
                ->relate($foreignEntity, [SaveOption::API => true]);

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

        /** @noinspection PhpDeprecationInspection */
        if (in_array($link, $this->forbiddenLinkList)) {
            throw new Forbidden();
        }

        /** @noinspection PhpDeprecationInspection */
        if (in_array($link, $this->internalLinkList)) {
            throw new Forbidden();
        }

        /** @noinspection PhpDeprecationInspection */
        if (!$this->user->isAdmin() && in_array($link, $this->onlyAdminLinkList)) {
            throw new Forbidden();
        }
    }

    /**
     * @throws Forbidden
     */
    protected function processForbiddenLinkEditCheck(string $link): void
    {
        $type = $this->entityManager
            ->getDefs()
            ->getEntity($this->entityType)
            ->tryGetRelation($link)
            ?->getType();

        if (
            $type &&
            !in_array($type, [
                Entity::MANY_MANY,
                Entity::HAS_MANY,
                Entity::HAS_CHILDREN,
            ])
        ) {
            throw new Forbidden("Only manyMany, hasMany & hasChildren relations are allowed.");
        }

        $forbiddenLinkList = $this->acl->getScopeForbiddenLinkList($this->entityType, AclTable::ACTION_EDIT);

        if (in_array($link, $forbiddenLinkList)) {
            throw new Forbidden();
        }

        /** @noinspection PhpDeprecationInspection */
        if (in_array($link, $this->forbiddenLinkList)) {
            throw new Forbidden();
        }

        /** @noinspection PhpDeprecationInspection */
        if (in_array($link, $this->readOnlyLinkList)) {
            throw new Forbidden();
        }

        /** @noinspection PhpDeprecationInspection */
        if (!$this->user->isAdmin() && in_array($link, $this->nonAdminReadOnlyLinkList)) {
            throw new Forbidden();
        }

        /** @noinspection PhpDeprecationInspection */
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
     * @noinspection PhpDocSignatureInspection
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
     * Do not extend. Prefer metadata recordDefs > outputFilterClassNameList.
     *
     * @param TEntity $entity
     * @return void
     * @todo Add void return type in v9.0.
     * @noinspection PhpDocSignatureInspection
     */
    public function prepareEntityForOutput(Entity $entity)
    {
        /** @noinspection PhpDeprecationInspection */
        if ($this->internalAttributeList !== []) {
            trigger_error(
                '$internalAttributeList is deprecated and will be removed in v9.0.',
                E_USER_DEPRECATED
            );
        }

        /** @noinspection PhpDeprecationInspection */
        if ($this->forbiddenAttributeList !== []) {
            trigger_error(
                '$forbiddenAttributeList is deprecated and will be removed in v9.0.',
                E_USER_DEPRECATED
            );
        }

        /** @noinspection PhpDeprecationInspection */
        if ($this->onlyAdminAttributeList !== []) {
            trigger_error(
                '$onlyAdminAttributeList is deprecated and will be removed in v9.0.',
                E_USER_DEPRECATED
            );
        }

        /** @noinspection PhpDeprecationInspection */
        foreach ($this->internalAttributeList as $attribute) {
            $entity->clear($attribute);
        }

        /** @noinspection PhpDeprecationInspection */
        foreach ($this->forbiddenAttributeList as $attribute) {
            $entity->clear($attribute);
        }

        if (!$this->user->isAdmin()) {
            /** @noinspection PhpDeprecationInspection */
            foreach ($this->onlyAdminAttributeList as $attribute) {
                $entity->clear($attribute);
            }
        }

        $forbiddenAttributeList = $this->acl->getScopeForbiddenAttributeList($entity->getEntityType());

        foreach ($forbiddenAttributeList as $attribute) {
            $entity->clear($attribute);
        }

        foreach ($this->getOutputFilterList() as $filter) {
            $filter->filter($entity);
        }
    }

    /**
     * @return Output\Filter<Entity>[]
     */
    private function getOutputFilterList(): array
    {
        if ($this->outputFilterList === null) {
            $this->outputFilterList =
                $this->injectableFactory->create(Output\FilterProvider::class)->get($this->entityType);
        }

        return $this->outputFilterList;
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

        /** @noinspection PhpDeprecationInspection */
        foreach ($this->duplicateIgnoreAttributeList as $attribute) {
            unset($attributes->$attribute);
        }

        if ($this->acl->getPermissionLevel(Acl\Permission::ASSIGNMENT) === AclTable::LEVEL_NO) {
            unset($attributes->assignedUserId);
            unset($attributes->assignedUserName);
            unset($attributes->assignedUsersIds);
        }

        return $attributes;
    }

    /**
     * @param TEntity $entity
     * @noinspection PhpDocSignatureInspection
     * @todo Make private in v9.0.
     * @deprecated As of v8.2. Use afterCreate record hook instead.
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

        /** @noinspection PhpDeprecationInspection */
        $this->duplicateLinks($entity, $duplicatingEntity);
    }

    /**
     * @param TEntity $entity
     * @param TEntity $duplicatingEntity
     * @noinspection PhpDocSignatureInspection
     * @todo Make private in v9.0.
     * @deprecated As of v8.2. Use afterCreate record hook instead.
     */
    protected function duplicateLinks(Entity $entity, Entity $duplicatingEntity): void
    {
        /** @noinspection PhpDeprecationInspection */
        if ($this->duplicatingLinkList !== []) {
            trigger_error(
                '$duplicatingLinkList is deprecated and will be removed in v9.0.',
                E_USER_DEPRECATED
            );
        }

        /** @noinspection PhpDeprecationInspection */
        $linkList = array_merge(
            $this->duplicatingLinkList,
            $this->metadata->get("recordDefs.$this->entityType.duplicateLinkList") ?? [],
        );

        foreach ($linkList as $link) {
            $linkedList = $this->getRepository()
                ->getRelation($duplicatingEntity, $link)
                ->find();

            foreach ($linkedList as $linked) {
                $this->getRepository()
                    ->getRelation($entity, $link)
                    ->relate($linked);
            }
        }
    }

    public function prepareSearchParams(SearchParams $searchParams): SearchParams
    {
        $searchParams = $this->prepareSearchParamsSelect($searchParams);

        if ($searchParams->getSelect() === null) {
            $searchParams = $searchParams->withSelect(['*']);
        }

        return $searchParams
            ->withMaxTextAttributeLength(
                $this->getMaxSelectTextAttributeLength()
            );
    }

    protected function prepareSearchParamsSelect(SearchParams $searchParams): SearchParams
    {
        /** @noinspection PhpDeprecationInspection */
        if ($this->forceSelectAllAttributes) {
            return $searchParams->withSelect(null);
        }

        if ($this->metadata->get("recordDefs.$this->entityType.forceSelectAllAttributes")) {
            return $searchParams->withSelect(null);
        }

        if ($searchParams->getSelect() === null) {
            return $searchParams;
        }

        /** @var string[] $mandatoryAttributeList */
        $mandatoryAttributeList = $this->metadata->get("recordDefs.$this->entityType.mandatoryAttributeList") ?? [];
        /** @noinspection PhpDeprecationInspection */
        $mandatoryAttributeList = array_merge($this->mandatorySelectAttributeList, $mandatoryAttributeList);

        if ($mandatoryAttributeList === []) {
            return $searchParams;
        }

        /** @noinspection PhpDeprecationInspection */
        $select = array_unique(
            array_merge(
                $searchParams->getSelect(),
                $mandatoryAttributeList
            )
        );

        return $searchParams->withSelect($select);
    }

    /**
     * Do not extend.
     * @internal
     */
    protected function prepareLinkSearchParams(SearchParams $searchParams, string $link): SearchParams
    {
        if ($searchParams->getSelect() === null) {
            return $searchParams;
        }

        /** @noinspection PhpDeprecationInspection */
        $list1 = $this->linkMandatorySelectAttributeList[$link] ?? [];
        $list2 = $this->metadata->get("recordDefs.$this->entityType.relationships.$link.mandatoryAttributeList") ?? [];

        if ($list1 === [] && $list2 === []) {
            return $searchParams;
        }

        $select = array_unique(
            array_merge(
                $searchParams->getSelect(),
                $list1,
                $list2
            )
        );

        return $searchParams->withSelect($select);
    }

    /**
     * @param TEntity $entity
     * @noinspection PhpDocSignatureInspection
     */
    private function processApiBeforeCreateApiScript(Entity $entity, CreateParams $params): void
    {
        $processor = $this->injectableFactory->create(FormulaProcessor::class);

        $processor->processBeforeCreate($entity, $params);
    }

    /**
     * @param TEntity $entity
     * @noinspection PhpDocSignatureInspection
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
     * @noinspection PhpDocSignatureInspection
     * @deprecated As of v8.2.
     * @todo Remove (or add types) in v10.0.
     */
    protected function beforeCreateEntity(Entity $entity, $data)
    {}

    /**
     * @param TEntity $entity
     * @param stdClass $data
     * @return void
     * @noinspection PhpDocSignatureInspection
     * @deprecated As of v8.2.
     * @todo Remove (or add types) in v10.0.
     */
    protected function afterCreateEntity(Entity $entity, $data)
    {}

    /**
     * @param TEntity $entity
     * @param stdClass $data
     * @return void
     * @noinspection PhpDocSignatureInspection
     * @deprecated As of v8.2.
     * @todo Remove (or add types) in v10.0.
     */
    protected function beforeUpdateEntity(Entity $entity, $data)
    {}

    /**
     * @param TEntity $entity
     * @param stdClass $data
     * @return void
     * @noinspection PhpDocSignatureInspection
     * @deprecated As of v8.2.
     * @todo Remove (or add types) in v10.0.
     */
    protected function afterUpdateEntity(Entity $entity, $data)
    {}

    /**
     * @param TEntity $entity
     * @return void
     * @noinspection PhpDocSignatureInspection
     * @deprecated As of v8.2.
     * @todo Remove (or add types) in v10.0.
     */
    protected function beforeDeleteEntity(Entity $entity)
    {}

    /**
     * @param TEntity $entity
     * @return void
     * @noinspection PhpDocSignatureInspection
     * @deprecated As of v8.2.
     * @todo Remove (or add types) in v10.0.
     */
    protected function afterDeleteEntity(Entity $entity)
    {}

    private function createBinding(): BindingContainer
    {
        return BindingContainerBuilder::create()
            ->bindInstance(User::class, $this->user)
            ->bindInstance(Acl::class, $this->acl)
            ->build();
    }

    private function getRecordHookManager(): HookManager
    {
        if (!$this->recordHookManager) {
            $this->recordHookManager =
                $this->injectableFactory->createWithBinding(HookManager::class, $this->createBinding());
        }

        return $this->recordHookManager;
    }

    /**
     * @throws Forbidden
     */
    private function checkEntityCreateAccess(Entity $entity): void
    {
        if (!$this->acl->check($entity, AclTable::ACTION_CREATE)) {
            throw new ForbiddenSilent("No create access.");
        }
    }
}
