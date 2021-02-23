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
    Exceptions\BadRequest,
    Exceptions\Error,
    Utils\Json,
    Di,
    Select\SearchParams,
};

use Espo\{
    ORM\Entity,
    Services\Record,
};

class Export
    implements
    Di\MetadataAware,
    Di\EntityManagerAware,
    Di\SelectBuilderFactoryAware,
    Di\AclAware,
    Di\InjectableFactoryAware
{
    use Di\MetadataSetter;
    use Di\EntityManagerSetter;
    use Di\SelectBuilderFactorySetter;
    use Di\AclSetter;
    use Di\InjectableFactorySetter;

    protected $entityType;

    protected $additionalAttributeList = [];

    protected $skipAttributeList = [];

    protected $recordService;

    protected $params = [];

    public function setRecordService(Record $recordService) : self
    {
        $this->recordService = $recordService;

        return $this;
    }

    public function setEntityType(string $entityType) : self
    {
        $this->entityType = $entityType;

        return $this;
    }

    public function setAdditionalAttributeList(array $additionalAttributeList) : self
    {
        $this->additionalAttributeList = $additionalAttributeList;

        return $this;
    }

    public function setSkipAttributeList(array $skipAttributeList) : self
    {
        $this->skipAttributeList = $skipAttributeList;

        return $this;
    }

    public function setParams(array $params) : self
    {
        $this->params = $params;

        return $this;
    }

    /**
     * Run export.
     *
     * @return An ID of a generated attachment.
     */
    public function run() : string
    {
        $params = $this->params;

        if (!$this->entityType) {
            throw new Error("Entity type is not specified.");
        }

        if (array_key_exists('format', $params)) {
            $format = $params['format'];
        } else {
            $format = 'csv';
        }

        if (!in_array($format, $this->metadata->get(['app', 'export', 'formatList']))) {
            throw new Error('Not supported export format.');
        }

        $className = $this->metadata->get(['app', 'export', 'exportFormatClassNameMap', $format]);

        if (empty($className)) {
            throw new Error();
        }

        $exportObj = $this->injectableFactory->create($className);

        $collection = null;

        $exportAllFields = !array_key_exists('fieldList', $params);

        if (array_key_exists('collection', $params)) {
            $collection = $params['collection'];
        }
        else {
            $selectBuilder = $this->selectBuilderFactory
                ->create()
                ->from($this->entityType)
                ->withStrictAccessControl();

            if (array_key_exists('ids', $params)) {
                $ids = $params['ids'];

                $queryBuilder = $selectBuilder
                    ->withDefaultOrder()
                    ->buildQueryBuilder()
                    ->where([
                        'id' => $ids,
                    ]);
            }
            else if (array_key_exists('where', $params)) {
                $where = $params['where'];

                $searchParams = [];

                $searchParams['where'] = $where;

                if (!empty($params['selectData']) && is_array($params['selectData'])) {
                    foreach ($params['selectData'] as $k => $v) {
                        $searchParams[$k] = $v;
                    }
                }

                unset($searchParams['select']);

                $queryBuilder = $selectBuilder
                    ->withSearchParams(SearchParams::fromRaw($searchParams))
                    ->buildQueryBuilder();
            }
            else {
                throw new BadRequest("Bad export parameters.");
            }

            $collection = $this->entityManager
                ->getRepository($this->entityType)
                ->clone($queryBuilder->build())
                ->sth()
                ->find();
        }

        $attributeListToSkip = [
            'deleted',
        ];

        foreach ($this->skipAttributeList as $attribute) {
            $attributeListToSkip[] = $attribute;
        }

        foreach ($this->acl->getScopeForbiddenAttributeList($this->entityType, 'read') as $attribute) {
            $attributeListToSkip[] = $attribute;
        }

        $attributeList = null;

        if (array_key_exists('attributeList', $params)) {
            $attributeList = [];

            $seed = $this->entityManager->getEntity($this->entityType);

            foreach ($params['attributeList'] as $attribute) {
                if (in_array($attribute, $attributeListToSkip)) {
                    continue;
                }

                if ($this->checkAttributeIsAllowedForExport($seed, $attribute)) {
                    $attributeList[] = $attribute;
                }
            }
        }

        if ($exportAllFields) {
            $fieldDefs = $this->metadata->get(['entityDefs', $this->entityType, 'fields'], []);
            $fieldList = array_keys($fieldDefs);

            array_unshift($fieldList, 'id');
        } else {
            $fieldList = $params['fieldList'];
        }

        foreach ($fieldList as $i => $field) {
            if ($this->metadata->get(['entityDefs', $this->entityType, 'fields', $field, 'exportDisabled'])) {
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
            $seed = $this->entityManager->getEntity($this->entityType);

            foreach ($seed->getAttributes() as $attribute => $defs) {
                if (in_array($attribute, $attributeListToSkip)) {
                    continue;
                }

                if ($this->checkAttributeIsAllowedForExport($seed, $attribute, true)) {
                    $attributeList[] = $attribute;
                }
            }

            foreach ($this->additionalAttributeList as $attribute) {
                $attributeList[] = $attribute;
            }
        }

        if (method_exists($exportObj, 'addAdditionalAttributes')) {
            $exportObj->addAdditionalAttributes($this->entityType, $attributeList, $fieldList);
        }

        $fp = fopen('php://temp', 'w');

        foreach ($collection as $entity) {
            if ($this->recordService) {
                $this->recordService->loadAdditionalFieldsForExport($entity);
            }

            if (method_exists($exportObj, 'loadAdditionalFields')) {
                $exportObj->loadAdditionalFields($entity, $fieldList);
            }

            $row = [];

            foreach ($attributeList as $attribute) {
                $value = $this->getAttributeFromEntity($entity, $attribute);

                $row[$attribute] = $value;
            }

            $line = base64_encode(serialize($row)) . \PHP_EOL;

            fwrite($fp, $line);
        }

        rewind($fp);

        if (is_null($attributeList)) {
            $attributeList = [];
        }

        $mimeType = $this->metadata->get(['app', 'export', 'formatDefs', $format, 'mimeType']);
        $fileExtension = $this->metadata->get(['app', 'export', 'formatDefs', $format, 'fileExtension']);

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

        $attachment = $this->entityManager->getEntity('Attachment');

        $attachment->set('name', $fileName);
        $attachment->set('role', 'Export File');
        $attachment->set('type', $mimeType);
        $attachment->set('contents', $contents);

        $this->entityManager->saveEntity($attachment);

        return $attachment->id;
    }

    protected function getAttributeFromEntity(Entity $entity, string $attribute)
    {
        $methodName = 'getAttribute' . ucfirst($attribute). 'FromEntity';

        if (method_exists($this, $methodName)) {
            return $this->$methodName($entity);
        }

        $defs = $entity->getAttributes();

        $type = $entity->getAttributeType($attribute);

        if ($type === Entity::FOREIGN) {
            $type = $this->getForeignAttributeType($entity, $attribute) ?? $type;
        }

        switch ($type) {
            case Entity::JSON_OBJECT:
                if (!empty($defs[$attribute]['isLinkMultipleNameMap'])) {
                    break;
                }

                $value = $entity->get($attribute);

                return Json::encode($value, \JSON_UNESCAPED_UNICODE);

            case Entity::JSON_ARRAY:
                if (!empty($defs[$attribute]['isLinkMultipleIdList'])) {
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

    private function getForeignAttributeType(Entity $entity, string $attribute) : ?string
    {
        $defs = $this->entityManager->getDefs();

        $entityDefs = $defs->getEntity($this->entityType);

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
        Entity $entity, string $attribute, bool $isExportAllFields = false
    ) : bool {

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
}
