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

namespace Espo\Tools\Export;

use Espo\Core\{
    Exceptions\Error,
    Utils\Json,
    Select\SelectBuilderFactory,
    Acl,
    Acl\Table,
    Record\ServiceContainer,
    Utils\Metadata,
    FileStorage\Manager as FileStorageManager,
};

use Espo\{
    ORM\Entity,
    ORM\Collection,
    ORM\EntityManager,
};

class Export
{
    private $params;

    private $collection = null;

    private $processorFactory;

    private $selectBuilderFactory;

    private $serviceContainer;

    private $acl;

    private $entityManager;

    private $metadata;

    private $fileStorageManager;

    public function __construct(
        ProcessorFactory $processorFactory,
        SelectBuilderFactory $selectBuilderFactor,
        ServiceContainer $serviceContainer,
        Acl $acl,
        EntityManager $entityManager,
        Metadata $metadata,
        FileStorageManager $fileStorageManager
    ) {
        $this->processorFactory = $processorFactory;
        $this->selectBuilderFactory = $selectBuilderFactor;
        $this->serviceContainer = $serviceContainer;
        $this->acl = $acl;
        $this->entityManager = $entityManager;
        $this->metadata = $metadata;
        $this->fileStorageManager = $fileStorageManager;
    }

    public function setParams(Params $params): self
    {
        $this->params = $params;

        return $this;
    }

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
            throw new Error("No params set.");
        }

        $params = $this->params;

        $entityType = $params->getEntityType();

        $format = $params->getFormat() ?? 'csv';

        $processor = $this->processorFactory->create($format);

        $exportAllFields = $params->getFieldList() === null;

        $collection = $this->getCollection($params);

        $attributeListToSkip = $this->acl->getScopeForbiddenAttributeList($entityType, Table::ACTION_READ);

        $attributeListToSkip[] = 'deleted';

        $attributeList = null;

        if ($params->getAttributeList() !== null) {
            $attributeList = [];

            $seed = $this->entityManager->getEntity($entityType);

            foreach ($params->getAttributeList() as $attribute) {
                if (in_array($attribute, $attributeListToSkip)) {
                    continue;
                }

                if (!$this->checkAttributeIsAllowedForExport($seed, $attribute)) {
                    continue;
                }

                $attributeList[] = $attribute;
            }
        }

        $entityDefs = $this->entityManager
            ->getDefs()
            ->getEntity($entityType);

        if ($exportAllFields) {
            $fieldList = $entityDefs->getFieldNameList();

            array_unshift($fieldList, 'id');
        }
        else {
            $fieldList = $params->getFieldList();
        }

        foreach ($fieldList as $i => $field) {
            if ($field === 'id') {
                continue;
            }

            if ($entityDefs->getField($field)->getParam('exportDisabled')) {
                unset($fieldList[$i]);
            }
        }

        $fieldList = array_values($fieldList);

        if (method_exists($processor, 'filterFieldList')) {
            $fieldList = $processor->filterFieldList($entityType, $fieldList, $exportAllFields);
        }

        if (is_null($attributeList)) {
            $attributeList = [];

            $seed = $this->entityManager->getEntity($entityType);

            foreach ($entityDefs->getAttributeNameList() as $attribute) {
                if (in_array($attribute, $attributeListToSkip)) {
                    continue;
                }

                if (!$this->checkAttributeIsAllowedForExport($seed, $attribute, true)) {
                    continue;
                }

                $attributeList[] = $attribute;
            }
        }

        if (method_exists($processor, 'addAdditionalAttributes')) {
            $processor->addAdditionalAttributes($entityType, $attributeList, $fieldList);
        }

        $dataResource = fopen('php://temp', 'w');

        $recordService = $this->serviceContainer->get($entityType);

        foreach ($collection as $entity) {
            $recordService->loadAdditionalFieldsForExport($entity);

            if (method_exists($processor, 'loadAdditionalFields')) {
                $processor->loadAdditionalFields($entity, $fieldList);
            }

            $row = [];

            foreach ($attributeList as $attribute) {
                $value = $this->getAttributeFromEntity($entity, $attribute);

                $row[$attribute] = $value;
            }

            $line = base64_encode(serialize($row)) . \PHP_EOL;

            fwrite($dataResource, $line);
        }

        rewind($dataResource);

        if (is_null($attributeList)) {
            $attributeList = [];
        }

        $mimeType = $this->metadata->get(['app', 'export', 'formatDefs', $format, 'mimeType']);
        $fileExtension = $this->metadata->get(['app', 'export', 'formatDefs', $format, 'fileExtension']);

        $fileName = $params->getFileName();

        if ($fileName !== null) {
            $fileName = trim($fileName);
        }

        if ($fileName) {
            $fileName = $fileName . '.' . $fileExtension;
        }
        else {
            $fileName = "Export_{$entityType}." . $fileExtension;
        }

        $processorParams =
            (new ProcessorParams($fileName, $attributeList, $fieldList))
                ->withName($params->getName())
                ->withEntityType($params->getEntityType());

        $processorData = new ProcessorData($dataResource);

        $stream = $processor->process($processorParams, $processorData);

        fclose($dataResource);

        $attachment = $this->entityManager->getEntity('Attachment');

        $attachment->set('name', $fileName);
        $attachment->set('role', 'Export File');
        $attachment->set('type', $mimeType);
        $attachment->set('size', $stream->getSize());

        $this->entityManager->saveEntity($attachment);

        $this->fileStorageManager->putStream($attachment, $stream);

        return new Result($attachment->getId());
    }

    protected function getAttributeFromEntity(Entity $entity, string $attribute)
    {
        $methodName = 'getAttribute' . ucfirst($attribute). 'FromEntity';

        if (method_exists($this, $methodName)) {
            return $this->$methodName($entity);
        }

        $type = $entity->getAttributeType($attribute);

        if ($type === Entity::FOREIGN) {
            $type = $this->getForeignAttributeType($entity, $attribute) ?? $type;
        }

        switch ($type) {
            case Entity::JSON_OBJECT:
                if ($entity->getAttributeParam($attribute, 'isLinkMultipleNameMap')) {
                    break;
                }

                $value = $entity->get($attribute);

                return Json::encode($value, \JSON_UNESCAPED_UNICODE);

            case Entity::JSON_ARRAY:
                if ($entity->getAttributeParam($attribute, 'isLinkMultipleIdList')) {
                    break;
                }

                $value = $entity->get($attribute);

                if (is_array($value)) {
                    return Json::encode($value, \JSON_UNESCAPED_UNICODE);
                }

                return null;

            case Entity::PASSWORD:
                return null;
        }

        return $entity->get($attribute);
    }

    private function getForeignAttributeType(Entity $entity, string $attribute): ?string
    {
        $defs = $this->entityManager->getDefs();

        $entityDefs = $defs->getEntity($entity->getEntityType());

        $relation = $entity->getAttributeParam($attribute, 'relation');
        $foreign = $entity->getAttributeParam($attribute, 'foreign');

        if (!$relation) {
            return null;
        }

        if (!$foreign) {
            return null;
        }

        if (!is_string($foreign)) {
            return self::VARCHAR;
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

    protected function checkAttributeIsAllowedForExport(
        Entity $entity,
        string $attribute,
        bool $isExportAllFields = false
    ): bool {

        if (!$isExportAllFields) {
            return true;
        }

        if ($entity->getAttributeParam($attribute, 'notExportable')) {
            return false;
        }

        if ($entity->getAttributeParam($attribute, 'isLinkMultipleIdList')) {
            return false;
        }

        if ($entity->getAttributeParam($attribute, 'isLinkMultipleNameMap')) {
            return false;
        }

        if ($entity->getAttributeParam($attribute, 'isLinkStub')) {
            return false;
        }

        return true;
    }

    private function getCollection(Params $params): Collection
    {
        if ($this->collection) {
            return $this->collection;
        }

        $entityType = $params->getEntityType();

        $searchParams = $params->getSearchParams();

        $query = $this->selectBuilderFactory
            ->create()
            ->from($entityType)
            ->withSearchParams($searchParams)
            ->withStrictAccessControl()
            ->build();

        return $this->entityManager
            ->getRepository($entityType)
            ->clone($query)
            ->sth()
            ->find();
    }
}
