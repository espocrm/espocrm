<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Tools\Export;

use Espo\Core\ORM\Defs\AttributeParam;
use Espo\Core\ORM\Repository\Option\SaveOption;
use Espo\Core\Record\Select\ApplierClassNameListProvider;
use Espo\ORM\Defs\Params\AttributeParam as OrmAttributeParam;
use Espo\ORM\Name\Attribute;
use Espo\Tools\Export\Collection as ExportCollection;
use Espo\Tools\Export\Processor\Params as ProcessorParams;
use Espo\ORM\Entity;
use Espo\ORM\BaseEntity;
use Espo\Entities\User;
use Espo\Entities\Attachment;
use Espo\Core\Acl;
use Espo\Core\Acl\GlobalRestriction;
use Espo\Core\FieldProcessing\ListLoadProcessor;
use Espo\Core\FieldProcessing\Loader\Params as LoaderParams;
use Espo\Core\FileStorage\Manager as FileStorageManager;
use Espo\Core\Record\ServiceContainer;
use Espo\Core\Select\SelectBuilderFactory;
use Espo\Core\Utils\FieldUtil;
use Espo\Core\Utils\Metadata;
use Espo\ORM\Collection;
use Espo\ORM\EntityManager;

use RuntimeException;
use LogicException;

class Export
{
    private const DEFAULT_FORMAT = 'csv';

    /** @var ?Params */
    private ?Params $params = null;
    /** @var ?Collection<Entity> */
    private ?Collection $collection = null;

    public function __construct(
        private ProcessorFactory $processorFactory,
        private ProcessorParamsHandlerFactory $processorParamsHandlerFactory,
        private AdditionalFieldsLoaderFactory $additionalFieldsLoaderFactory,
        private SelectBuilderFactory $selectBuilderFactory,
        private ServiceContainer $serviceContainer,
        private Acl $acl,
        private EntityManager $entityManager,
        private Metadata $metadata,
        private FileStorageManager $fileStorageManager,
        private ListLoadProcessor $listLoadProcessor,
        private FieldUtil $fieldUtil,
        private User $user,
        private ApplierClassNameListProvider $applierClassNameListProvider
    ) {}

    public function setParams(Params $params): self
    {
        $this->params = $params;

        return $this;
    }

    /**
     * @param Collection<Entity> $collection
     */
    public function setCollection(Collection $collection): self
    {
        $this->collection = $collection;

        return $this;
    }

    /**
     * Run export.
     */
    public function run(): Result
    {
        if (!$this->params) {
            throw new LogicException("No params set.");
        }

        $params = $this->params;

        $entityType = $params->getEntityType();
        $format = $params->getFormat() ?? self::DEFAULT_FORMAT;
        $collection = $this->getCollection($params);

        $processor = $this->processorFactory->create($format);

        $processorParams = $this->createProcessorParams($params)
            ->withAttributeList($this->getAttributeList($params))
            ->withFieldList($this->getFieldList($params));

        if ($this->processorParamsHandlerFactory->isCreatable($format)) {
            $processorParams = $this->processorParamsHandlerFactory
                ->create($format)
                ->handle($params, $processorParams);
        }

        $loaderParams = LoaderParams::create()
            ->withSelect($processorParams->getAttributeList());

        $recordService = $this->serviceContainer->get($entityType);

        $loader = $this->additionalFieldsLoaderFactory->isCreatable($format) ?
            $this->additionalFieldsLoaderFactory->create($format) : null;

        $exportCollection = new ExportCollection(
            collection: $collection,
            listLoadProcessor: $this->listLoadProcessor,
            loaderParams: $loaderParams,
            additionalFieldsLoader: $loader,
            recordService: $recordService,
            processorParams: $processorParams
        ) ;

        $stream = $processor->process($processorParams, $exportCollection);

        $mimeType = $this->metadata->get(['app', 'export', 'formatDefs', $format, 'mimeType']);

        /** @var Attachment $attachment */
        $attachment = $this->entityManager->getRepositoryByClass(Attachment::class)->getNew();

        $attachment
            ->setName($processorParams->getFileName())
            ->setRole(Attachment::ROLE_EXPORT_FILE)
            ->setType($mimeType)
            ->setSize($stream->getSize());

        $this->entityManager->saveEntity($attachment, [
            SaveOption::CREATED_BY_ID => $this->user->getId(),
        ]);

        $this->fileStorageManager->putStream($attachment, $stream);

        return new Result($attachment->getId());
    }

    private function createProcessorParams(Params $params): ProcessorParams
    {
        $fileName = $params->getFileName();
        $format = $params->getFormat() ?? self::DEFAULT_FORMAT;
        $entityType = $params->getEntityType();
        $attributeList = $params->getAttributeList() ?? [];
        $fieldList = $params->getFieldList();

        $fileExtension = $this->metadata->get(['app', 'export', 'formatDefs', $format, 'fileExtension']);

        if ($fileName !== null) {
            $fileName = trim($fileName);
        }

        $fileName = $fileName ?
            $fileName . '.' . $fileExtension :
            "Export_$entityType.$fileExtension";

        $processorParams = (new ProcessorParams($fileName, $attributeList, $fieldList))
            ->withName($params->getName())
            ->withEntityType($params->getEntityType());

        foreach ($params->getParamList() as $n) {
            $processorParams = $processorParams->withParam($n, $params->getParam($n));
        }

        return $processorParams;
    }

    private function getForeignAttributeType(Entity $entity, string $attribute): ?string
    {
        $defs = $this->entityManager->getDefs();
        $entityDefs = $defs->getEntity($entity->getEntityType());

        [$relation, $foreign] = str_contains($attribute, '_') ?
            explode('_', $attribute) :
            [
                $this->getAttributeParam($entity, $attribute, OrmAttributeParam::RELATION),
                $this->getAttributeParam($entity, $attribute, 'foreign')
            ];

        if (!$relation) {
            return null;
        }

        if (!$foreign) {
            return null;
        }

        if (!is_string($foreign)) {
            return Entity::VARCHAR;
        }

        if (!$entityDefs->hasRelation($relation)) {
            return null;
        }

        if (!$entityDefs->getRelation($relation)->hasForeignEntityType()) {
            return null;
        }

        $entityType = $entityDefs->getRelation($relation)->getForeignEntityType();

        if (!$defs->hasEntity($entityType)) {
            return null;
        }

        $foreignEntityDefs = $defs->getEntity($entityType);

        if (!$foreignEntityDefs->hasAttribute($foreign)) {
            return null;
        }

        return $foreignEntityDefs->getAttribute($foreign)->getType();
    }

    private function checkAttributeIsAllowedForExport(
        Entity $entity,
        string $attribute,
        bool $exportAllFields = false
    ): bool {

        $type = $entity->getAttributeType($attribute);

        if ($type === Entity::FOREIGN || str_contains($attribute, '_')) {
            $type = $this->getForeignAttributeType($entity, $attribute) ?? $type;
        }

        if ($type === Entity::PASSWORD) {
            return false;
        }

        if ($this->getAttributeParam($entity, $attribute, AttributeParam::NOT_EXPORTABLE)) {
            return false;
        }

        if (!$exportAllFields) {
            return true;
        }

        if ($this->getAttributeParam($entity, $attribute, AttributeParam::IS_LINK_MULTIPLE_ID_LIST)) {
            return false;
        }

        if ($this->getAttributeParam($entity, $attribute, AttributeParam::IS_LINK_MULTIPLE_NAME_MAP)) {
            return false;
        }

        // Revise.
        if ($this->getAttributeParam($entity, $attribute, 'isLinkStub')) {
            return false;
        }

        return true;
    }

    /**
     * @return Collection<Entity>
     */
    private function getCollection(Params $params): Collection
    {
        if ($this->collection) {
            return $this->collection;
        }

        $entityType = $params->getEntityType();

        $searchParams = $params->getSearchParams();

        $builder = $this->selectBuilderFactory
            ->create()
            ->forUser($this->user)
            ->from($entityType)
            ->withAdditionalApplierClassNameList(
                $this->applierClassNameListProvider->get($entityType)
            )
            ->withSearchParams($searchParams);

        if ($params->applyAccessControl()) {
            $builder->withStrictAccessControl();
        }

        $query = $builder->build();

        /** @var Collection<Entity> */
        return $this->entityManager
            ->getRDBRepository($entityType)
            ->clone($query)
            ->sth()
            ->find();
    }

    /**
     * @return string[]
     */
    private function getAttributeList(Params $params): array
    {
        $list = [];

        $entityType = $params->getEntityType();

        $entityDefs = $this->entityManager
            ->getDefs()
            ->getEntity($entityType);

        $attributeListToSkip = $params->applyAccessControl() ?
            $this->acl->getScopeForbiddenAttributeList($entityType) :
            $this->acl->getScopeRestrictedAttributeList($entityType, [
                GlobalRestriction::TYPE_FORBIDDEN,
                GlobalRestriction::TYPE_INTERNAL,
            ]);

        $attributeListToSkip[] = Attribute::DELETED;

        $initialAttributeList = $params->getAttributeList();

        if (
            $params->getAttributeList() === null &&
            $params->getFieldList() !== null
        ) {
            $initialAttributeList = $this->getAttributeListFromFieldList($params);
        }

        if (
            $params->getAttributeList() === null &&
            $params->getFieldList() === null
        ) {
            $initialAttributeList = $entityDefs->getAttributeNameList();
        }

        assert($initialAttributeList !== null);

        $seed = $this->entityManager->getNewEntity($entityType);

        foreach ($initialAttributeList as $attribute) {
            if (in_array($attribute, $attributeListToSkip)) {
                continue;
            }

            if (!$this->checkAttributeIsAllowedForExport($seed, $attribute, $params->allFields())) {
                continue;
            }

            $list[] = $attribute;
        }

        return $list;
    }

    /**
     * @return string[]
     * @throws RuntimeException
     */
    private function getAttributeListFromFieldList(Params $params): array
    {
        $entityType = $params->getEntityType();

        $fieldList = $params->getFieldList();

        if ($fieldList === null) {
            throw new RuntimeException();
        }

        $attributeList = [];

        foreach ($fieldList as $field) {
            $attributeList = array_merge(
                $attributeList,
                $this->fieldUtil->getAttributeList($entityType, $field)
            );
        }

        return $attributeList;
    }

    /**
     * @return ?string[]
     */
    private function getFieldList(Params $params): ?array
    {
        $entityDefs = $this->entityManager
            ->getDefs()
            ->getEntity($params->getEntityType());

        $fieldList = $params->getFieldList();

        if ($params->allFields()) {
            $fieldList = $entityDefs->getFieldNameList();

            array_unshift($fieldList, 'id');
        }

        if ($fieldList === null) {
            return null;
        }

        foreach ($fieldList as $i => $field) {
            if ($field === 'id') {
                continue;
            }

            if (!$entityDefs->hasField($field)) {
                continue;
            }

            if ($entityDefs->getField($field)->getParam('exportDisabled')) {
                unset($fieldList[$i]);
            }
        }

        $fieldList = array_filter($fieldList, function ($item) use ($params) {
            return $this->acl->checkField($params->getEntityType(), $item);
        });

        return array_values($fieldList);
    }

    private function getAttributeParam(Entity $entity, string $attribute, string $param): mixed
    {
        if ($entity instanceof BaseEntity) {
            return $entity->getAttributeParam($attribute, $param);
        }

        $entityDefs = $this->entityManager
            ->getDefs()
            ->getEntity($entity->getEntityType());

        if (!$entityDefs->hasAttribute($attribute)) {
            return null;
        }

        return $entityDefs->getAttribute($attribute)->getParam($param);
    }
}
