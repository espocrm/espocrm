<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2020 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

namespace Espo\Tools\ListExport;

use Espo\Core\{
    Exceptions\NotFound,
    Exceptions\BadRequest,
    Exceptions\Error,
    Utils\Util,
    Utils\Json,
    Di,
};

use Espo\{
    ORM\Entity,
    ORM\QueryParams\Select,
    Services\Record,
};

class ListExport
    implements Di\MetadataAware, Di\EntityManagerAware, Di\SelectManagerFactoryAware, Di\AclAware, Di\InjectableFactoryAware
{
    use Di\MetadataSetter;
    use Di\EntityManagerSetter;
    use Di\SelectManagerFactorySetter;
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

        if (array_key_exists('collection', $params)) {
            $collection = $params['collection'];
        } else {
            $selectManager = $this->selectManagerFactory->create($this->entityType);

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
            }
            else if (array_key_exists('where', $params)) {
                $where = $params['where'];

                $p = [];
                $p['where'] = $where;
                if (!empty($params['selectData']) && is_array($params['selectData'])) {
                    foreach ($params['selectData'] as $k => $v) {
                        $p[$k] = $v;
                    }
                }
                $selectParams = $this->getSelectParams($p);
            }
            else {
                throw new BadRequest();
            }

            $orderBy = $this->metadata->get(['entityDefs', $this->entityType, 'collection', 'orderBy']);
            $order = $this->metadata->get(['entityDefs', $this->entityType, 'collection', 'order']);

            if ($orderBy) {
                $selectManager->applyOrder($orderBy, $order, $selectParams);
            }

            $select = Select::fromRaw($selectParams);

            $collection = $this->entityManager->getRepository($this->entityType)
                ->clone($select)
                ->sth()
                ->find();
        }

        $attributeListToSkip = [
            'deleted'
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

        if (!array_key_exists('fieldList', $params)) {
            $exportAllFields = true;

            $fieldDefs = $this->metadata->get(['entityDefs', $this->entityType, 'fields'], []);
            $fieldList = array_keys($fieldDefs);

            array_unshift($fieldList, 'id');
        } else {
            $exportAllFields = false;
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

        switch ($type) {
            case 'jsonObject':
                if (!empty($defs[$attribute]['isLinkMultipleNameMap'])) {
                    break;
                }

                $value = $entity->get($attribute);

                return Json::encode($value, \JSON_UNESCAPED_UNICODE);

            case 'jsonArray':
                if (!empty($defs[$attribute]['isLinkMultipleIdList'])) {
                    break;
                }

                $value = $entity->get($attribute);

                if (is_array($value)) {
                    return Json::encode($value, \JSON_UNESCAPED_UNICODE);
                }

                return null;

            case 'password':
                return null;
        }

        return $entity->get($attribute);
    }

    protected function checkAttributeIsAllowedForExport(Entity $entity, string $attribute, bool $isExportAllFields = false) : bool
    {
        if (!$isExportAllFields) {
            return true;
        }

        if ($entity->getAttributeParam($attribute, 'notExportable')) return false;
        if ($entity->getAttributeParam($attribute, 'isLinkMultipleIdList')) return false;
        if ($entity->getAttributeParam($attribute, 'isLinkMultipleNameMap')) return false;
        if ($entity->getAttributeParam($attribute, 'isLinkStub')) return false;

        return true;
    }
}
