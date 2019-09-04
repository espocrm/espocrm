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

namespace Espo\Core\ORM\Repositories;

use \Espo\ORM\EntityManager;
use \Espo\ORM\EntityFactory;
use \Espo\ORM\Entity;
use \Espo\ORM\IEntity;
use Espo\Core\Utils\Util;

use \Espo\Core\Interfaces\Injectable;

class RDB extends \Espo\ORM\Repositories\RDB implements Injectable
{
    protected $dependencyList = [
        'metadata',
        'config',
        'fieldManagerUtil'
    ];

    protected $dependencies = []; // for backward compatibility

    protected $injections = [];

    private $restoreData = null;

    protected $hooksDisabled = false;

    protected $processFieldsAfterSaveDisabled = false;

    protected $processFieldsBeforeSaveDisabled = false;

    protected $processFieldsAfterRemoveDisabled = false;

    protected function addDependency($name)
    {
        $this->dependencyList[] = $name;
    }

    protected function addDependencyList(array $list)
    {
        foreach ($list as $item) {
            $this->addDependency($item);
        }
    }

    public function inject($name, $object)
    {
        $this->injections[$name] = $object;
    }

    protected function getInjection($name)
    {
        return $this->injections[$name];
    }

    public function getDependencyList()
    {
        return array_merge($this->dependencyList, $this->dependencies);
    }

    protected function getMetadata()
    {
        return $this->getInjection('metadata');
    }

    protected function getConfig()
    {
        return $this->getInjection('config');
    }

    protected function getFieldManagerUtil()
    {
        return $this->getInjection('fieldManagerUtil');
    }

    public function __construct($entityType, EntityManager $entityManager, EntityFactory $entityFactory)
    {
        parent::__construct($entityType, $entityManager, $entityFactory);
        $this->init();
    }

    protected function init()
    {
    }

    public function handleSelectParams(&$params)
    {
    }

    protected function handleCurrencyParams(&$params)
    {
        $entityType = $this->entityType;

        $metadata = $this->getMetadata();

        if (!$metadata) {
            return;
        }

        $defs = $metadata->get(['entityDefs', $entityType]);

        foreach ($defs['fields'] as $field => $d) {
            if (isset($d['type']) && $d['type'] == 'currency') {
                if (!empty($d['notStorable'])) continue;
                if (empty($params['leftJoins'])) $params['leftJoins'] = [];
                $alias = $field . 'CurrencyRate';

                $params['leftJoins'][] = ['Currency', $alias, [
                    $alias . '.id:' => $field . 'Currency'
                ]];
            }
        }

    }

    protected function handleEmailAddressParams(&$params)
    {
        $defs = $this->getEntityManager()->getMetadata()->get($this->entityType);
        if (!empty($defs['relations']) && array_key_exists('emailAddresses', $defs['relations'])) {
            if (empty($params['leftJoins'])) $params['leftJoins'] = [];
            $params['leftJoins'][] = ['emailAddresses', null, [
                'primary' => 1
            ]];
        }
    }

    protected function handlePhoneNumberParams(&$params)
    {
        $defs = $this->getEntityManager()->getMetadata()->get($this->entityType);
        if (!empty($defs['relations']) && array_key_exists('phoneNumbers', $defs['relations'])) {
            if (empty($params['leftJoins'])) $params['leftJoins'] = [];
            $params['leftJoins'][] = ['phoneNumbers', null, [
                'primary' => 1
            ]];
        }
    }

    protected function beforeRemove(Entity $entity, array $options = [])
    {
        parent::beforeRemove($entity, $options);
        if (!$this->hooksDisabled && empty($options['skipHooks'])) {
            $this->getEntityManager()->getHookManager()->process($this->entityType, 'beforeRemove', $entity, $options);
        }

        $nowString = date('Y-m-d H:i:s', time());
        if ($entity->hasAttribute('modifiedAt')) {
            $entity->set('modifiedAt', $nowString);
        }
        if ($entity->hasAttribute('modifiedById')) {
            if ($this->getEntityManager()->getUser()) {
                $entity->set('modifiedById', $this->getEntityManager()->getUser()->id);
            }
        }
    }

    protected function afterRemove(Entity $entity, array $options = [])
    {
        parent::afterRemove($entity, $options);

        if (!$this->processFieldsAfterRemoveDisabled) {
            $this->processArrayFieldsRemove($entity);
        }

        if (!$this->hooksDisabled && empty($options['skipHooks'])) {
            $this->getEntityManager()->getHookManager()->process($this->entityType, 'afterRemove', $entity, $options);
        }
    }

    protected function afterMassRelate(Entity $entity, $relationName, array $params = [], array $options = [])
    {
        if (!$this->hooksDisabled && empty($options['skipHooks'])) {
            $hookData = [
                'relationName' => $relationName,
                'relationParams' => $params,
            ];
            $this->getEntityManager()->getHookManager()->process($this->entityType, 'afterMassRelate', $entity, $options, $hookData);
        }
    }

    public function remove(Entity $entity, array $options = [])
    {
        $result = parent::remove($entity, $options);
        return $result;
    }

    protected function afterRelate(Entity $entity, $relationName, $foreign, $data = null, array $options = [])
    {
        parent::afterRelate($entity, $relationName, $foreign, $data, $options);

        if (!$this->hooksDisabled && empty($options['skipHooks'])) {
            if (is_string($foreign)) {
                $foreignId = $foreign;
                $foreignEntityType = $entity->getRelationParam($relationName, 'entity');
                if ($foreignEntityType) {
                    $foreign = $this->getEntityManager()->getEntity($foreignEntityType);
                    $foreign->id = $foreignId;
                    $foreign->setAsFetched();
                }
            }

            if ($foreign instanceof Entity) {
                $hookData = [
                    'relationName' => $relationName,
                    'relationData' => $data,
                    'foreignEntity' => $foreign,
                    'foreignId' => $foreign->id,
                ];
                $this->getEntityManager()->getHookManager()->process($this->entityType, 'afterRelate', $entity, $options, $hookData);
            }
        }
    }

    protected function afterUnrelate(Entity $entity, $relationName, $foreign, array $options = [])
    {
        parent::afterUnrelate($entity, $relationName, $foreign, $options);

        if (!$this->hooksDisabled && empty($options['skipHooks'])) {
            if (is_string($foreign)) {
                $foreignId = $foreign;
                $foreignEntityType = $entity->getRelationParam($relationName, 'entity');
                if ($foreignEntityType) {
                    $foreign = $this->getEntityManager()->getEntity($foreignEntityType);
                    $foreign->id = $foreignId;
                    $foreign->setAsFetched();
                }
            }

            if ($foreign instanceof Entity) {
                $hookData = [
                    'relationName' => $relationName,
                    'foreignEntity' => $foreign,
                    'foreignId' => $foreign->id,
                ];
                $this->getEntityManager()->getHookManager()->process($this->entityType, 'afterUnrelate', $entity, $options, $hookData);
            }
        }
    }

    protected function beforeSave(Entity $entity, array $options = [])
    {
        parent::beforeSave($entity, $options);

        if (!$this->hooksDisabled && empty($options['skipHooks'])) {
            $this->getEntityManager()->getHookManager()->process($this->entityType, 'beforeSave', $entity, $options);
        }

        if (!$this->processFieldsBeforeSaveDisabled) {
            $this->processCurrencyFieldsBeforeSave($entity);
        }
    }

    protected function afterSave(Entity $entity, array $options = [])
    {
        if (!empty($this->restoreData)) {
            $entity->set($this->restoreData);
            $this->restoreData = null;
        }
        parent::afterSave($entity, $options);

        if (!$this->processFieldsAfterSaveDisabled) {
            $this->processEmailAddressSave($entity);
            $this->processPhoneNumberSave($entity);
            $this->processSpecifiedRelationsSave($entity, $options);
            $this->processFileFieldsSave($entity);
            $this->processArrayFieldsSave($entity);
            $this->processWysiwygFieldsSave($entity);
        }

        if (!$this->hooksDisabled && empty($options['skipHooks'])) {
            $this->getEntityManager()->getHookManager()->process($this->entityType, 'afterSave', $entity, $options);
        }
    }

    public function save(Entity $entity, array $options = [])
    {
        $nowString = date('Y-m-d H:i:s', time());
        $restoreData = [];

        if ($entity->isNew()) {
            if (!$entity->has('id')) {
                $entity->set('id', Util::generateId());
            }
        }

        if (empty($options['skipAll'])) {
            if ($entity->isNew()) {
                if ($entity->hasAttribute('createdAt')) {
                    if (empty($options['import']) || !$entity->has('createdAt')) {
                        $entity->set('createdAt', $nowString);
                    }
                }
                if ($entity->hasAttribute('modifiedAt')) {
                    $entity->set('modifiedAt', $nowString);
                }
                if ($entity->hasAttribute('createdById')) {
                    if (empty($options['skipCreatedBy']) && (empty($options['import']) || !$entity->has('createdById'))) {
                        if ($this->getEntityManager()->getUser()) {
                            $entity->set('createdById', $this->getEntityManager()->getUser()->id);
                        }
                    }
                }
            } else {
                if (empty($options['silent']) && empty($options['skipModifiedBy'])) {
                    if ($entity->hasAttribute('modifiedAt')) {
                        $entity->set('modifiedAt', $nowString);
                    }
                    if ($entity->hasAttribute('modifiedById')) {
                        if ($this->getEntityManager()->getUser()) {
                            $entity->set('modifiedById', $this->getEntityManager()->getUser()->id);
                            $entity->set('modifiedByName', $this->getEntityManager()->getUser()->get('name'));
                        }
                    }
                }
            }
        }

        $this->restoreData = $restoreData;

        $result = parent::save($entity, $options);

        return $result;
    }

    protected function getFieldByTypeList($type)
    {
        return $this->getFieldManagerUtil()->getFieldByTypeList($this->entityType, $type);
    }

    protected function processCurrencyFieldsBeforeSave(Entity $entity)
    {
        foreach ($this->getFieldByTypeList('currency') as $field) {
            $currencyAttribute = $field . 'Currency';
            $defaultCurrency = $this->getConfig()->get('defaultCurrency');
            if ($entity->isNew()) {
                if ($entity->get($field) && !$entity->get($currencyAttribute)) {
                    $entity->set($currencyAttribute, $defaultCurrency);
                }
            } else {
                if ($entity->isAttributeChanged($field) && $entity->has($currencyAttribute) && !$entity->get($currencyAttribute)) {
                    $entity->set($currencyAttribute, $defaultCurrency);
                }
            }
        }
    }

    protected function processFileFieldsSave(Entity $entity)
    {
        foreach ($entity->getRelations() as $name => $defs) {
            if (!isset($defs['type']) || !isset($defs['entity'])) continue;
            if (!($defs['type'] === $entity::BELONGS_TO && $defs['entity'] === 'Attachment')) continue;

            $attribute = $name . 'Id';
            if (!$entity->hasAttribute($attribute)) continue;
            if (!$entity->get($attribute)) continue;
            if (!$entity->isAttributeChanged($attribute)) continue;

            $attachment = $this->getEntityManager()->getEntity('Attachment', $entity->get($attribute));
            if (!$attachment) continue;
            $attachment->set(array(
                'relatedId' => $entity->id,
                'relatedType' => $entity->getEntityType()
            ));
            $this->getEntityManager()->saveEntity($attachment);
        }

        if (!$entity->isNew()) {

            foreach ($this->getMetadata()->get(['entityDefs', $entity->getEntityType(), 'fields']) as $name => $defs) {
                if (!empty($defs['type']) && in_array($defs['type'], ['file', 'image'])) {
                    $attribute = $name . 'Id';
                    if ($entity->isAttributeChanged($attribute)) {
                        $previousAttachmentId = $entity->getFetched($attribute);
                        if ($previousAttachmentId) {
                            $attachment = $this->getEntityManager()->getEntity('Attachment', $previousAttachmentId);
                            if ($attachment) {
                                $this->getEntityManager()->removeEntity($attachment);
                            }
                        }
                    }
                }
            }
        }
    }

    protected function processArrayFieldsSave(Entity $entity)
    {
        foreach ($entity->getAttributes() as $attribute => $defs) {
            if (!isset($defs['type']) || $defs['type'] !== Entity::JSON_ARRAY) continue;
            if (!$entity->has($attribute)) continue;
            if (!$entity->isAttributeChanged($attribute)) continue;
            if (!$entity->getAttributeParam($attribute, 'storeArrayValues')) continue;
            if ($entity->getAttributeParam($attribute, 'notStorable')) continue;
            $this->getEntityManager()->getRepository('ArrayValue')->storeEntityAttribute($entity, $attribute);
        }
    }

    protected function processWysiwygFieldsSave(Entity $entity)
    {
        if (!$entity->isNew()) return;

        $fieldsDefs = $this->getMetadata()->get(['entityDefs', $entity->getEntityType(), 'fields'], []);
        foreach ($fieldsDefs as $field => $defs) {
            if (!empty($defs['type']) && $defs['type'] === 'wysiwyg') {
                $content = $entity->get($field);
                if (!$content) continue;
                if (preg_match_all("/\?entryPoint=attachment&amp;id=([^&=\"']+)/", $content, $matches)) {
                    if (!empty($matches[1]) && is_array($matches[1])) {
                        foreach ($matches[1] as $id) {
                            $attachment = $this->getEntityManager()->getEntity('Attachment', $id);
                            if ($attachment) {
                                if (!$attachment->get('relatedId') && !$attachment->get('sourceId')) {
                                    $attachment->set([
                                        'relatedId' => $entity->id,
                                        'relatedType' => $entity->getEntityType()
                                    ]);
                                    $this->getEntityManager()->saveEntity($attachment);
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    protected function processArrayFieldsRemove(Entity $entity)
    {
        foreach ($entity->getAttributes() as $attribute => $defs) {
            if (!isset($defs['type']) || $defs['type'] !== Entity::JSON_ARRAY) continue;
            if (!$entity->getAttributeParam($attribute, 'storeArrayValues')) continue;
            if ($entity->getAttributeParam($attribute, 'notStorable')) continue;
            $this->getEntityManager()->getRepository('ArrayValue')->deleteEntityAttribute($entity, $attribute);
        }
    }

    protected function processEmailAddressSave(Entity $entity)
    {
        if ($entity->hasRelation('emailAddresses') && $entity->hasAttribute('emailAddress')) {
            $this->getEntityManager()->getRepository('EmailAddress')->storeEntityEmailAddress($entity);
        }
    }

    protected function processPhoneNumberSave(Entity $entity)
    {
        if ($entity->hasRelation('phoneNumbers') && $entity->hasAttribute('phoneNumber')) {
            $this->getEntityManager()->getRepository('PhoneNumber')->storeEntityPhoneNumber($entity);
        }
    }

    public function processLinkMultipleFieldSave(Entity $entity, $link, array $options = [])
    {
        $name = $link;

        $idListAttribute = $link . 'Ids';
        $columnsAttribute = $link . 'Columns';

        if ($this->getMetadata()->get("entityDefs." . $entity->getEntityType() . ".fields.{$name}.noSave")) {
            return;
        }

        $skipCreate = false;
        $skipRemove = false;
        $skipUpdate = false;
        if (!empty($options['skipLinkMultipleCreate'])) $skipCreate = true;
        if (!empty($options['skipLinkMultipleRemove'])) $skipRemove = true;
        if (!empty($options['skipLinkMultipleUpdate'])) $skipUpdate = true;

        if ($entity->isNew()) {
            $skipRemove = true;
            $skipUpdate = true;
        }

        if ($entity->has($idListAttribute)) {
            $specifiedIdList = $entity->get($idListAttribute);
        } else if ($entity->has($columnsAttribute)) {
            $skipRemove = true;
            $specifiedIdList = [];
            foreach ($entity->get($columnsAttribute) as $id => $d) {
                $specifiedIdList[] = $id;
            }
        } else {
            return;
        }

        if (!is_array($specifiedIdList)) return;

        $toRemoveIdList = [];
        $existingIdList = [];
        $toUpdateIdList = [];
        $toCreateIdList = [];
        $existingColumnsData = (object)[];

        $defs = [];
        $columns = $this->getMetadata()->get("entityDefs." . $entity->getEntityType() . ".fields.{$name}.columns");
        if (!empty($columns)) {
            $columnData = $entity->get($columnsAttribute);
            $defs['additionalColumns'] = $columns;
        }

        if (!$skipRemove && !$skipUpdate) {
            $foreignEntityList = $entity->get($name, $defs);
            if ($foreignEntityList) {
                foreach ($foreignEntityList as $foreignEntity) {
                    $existingIdList[] = $foreignEntity->id;
                    if (!empty($columns)) {
                        $data = (object)[];
                        foreach ($columns as $columnName => $columnField) {
                            $foreignId = $foreignEntity->id;
                            $data->$columnName = $foreignEntity->get($columnField);
                        }
                        $existingColumnsData->$foreignId = $data;
                        if (!$entity->isNew()) {
                            $entity->setFetched($columnsAttribute, $existingColumnsData);
                        }
                    }
                }
            }
        }

        if (!$entity->isNew()) {
            if ($entity->has($idListAttribute) && !$entity->hasFetched($idListAttribute)) {
                $entity->setFetched($idListAttribute, $existingIdList);
            }
            if ($entity->has($columnsAttribute) && !empty($columns)) {
                $entity->setFetched($columnsAttribute, $existingColumnsData);
            }
        }

        foreach ($existingIdList as $id) {
            if (!in_array($id, $specifiedIdList)) {
                if (!$skipRemove) {
                    $toRemoveIdList[] = $id;
                }
            } else {
                if (!$skipUpdate && !empty($columns)) {
                    foreach ($columns as $columnName => $columnField) {
                        if (isset($columnData->$id) && is_object($columnData->$id)) {
                            if (
                                property_exists($columnData->$id, $columnName)
                                &&
                                (
                                    !property_exists($existingColumnsData->$id, $columnName)
                                    ||
                                    $columnData->$id->$columnName !== $existingColumnsData->$id->$columnName
                                )
                            ) {
                                $toUpdateIdList[] = $id;
                            }
                        }
                    }
                }
            }
        }

        if (!$skipCreate) {
            foreach ($specifiedIdList as $id) {
                if (!in_array($id, $existingIdList)) {
                    $toCreateIdList[] = $id;
                }
            }
        }

        foreach ($toCreateIdList as $id) {
            $data = null;
            if (!empty($columns) && isset($columnData->$id)) {
                $data = $columnData->$id;
            }
            $this->relate($entity, $name, $id, $data);
        }

        foreach ($toRemoveIdList as $id) {
            $this->unrelate($entity, $name, $id);
        }

        foreach ($toUpdateIdList as $id) {
            $data = $columnData->$id;
            $this->updateRelation($entity, $name, $id, $data);
        }
    }

    protected function processSpecifiedRelationsSave(Entity $entity, array $options = [])
    {
        $relationTypeList = [$entity::HAS_MANY, $entity::MANY_MANY, $entity::HAS_CHILDREN];
        foreach ($entity->getRelations() as $name => $defs) {
            if (in_array($defs['type'], $relationTypeList)) {
                $idListAttribute = $name . 'Ids';
                $columnsAttribute = $name . 'Columns';
                if ($entity->has($idListAttribute) || $entity->has($columnsAttribute)) {
                    $this->processLinkMultipleFieldSave($entity, $name, $options);
                }
            } else if ($defs['type'] === $entity::HAS_ONE) {
                if (empty($defs['entity']) || empty($defs['foreignKey'])) continue;

                if ($this->getMetadata()->get("entityDefs." . $entity->getEntityType() . ".fields.{$name}.noSave")) {
                    continue;
                }

                $foreignEntityType = $defs['entity'];
                $foreignKey = $defs['foreignKey'];
                $idAttribute = $name . 'Id';

                if (!$entity->has($idAttribute)) continue;

                $where = [];
                $where[$foreignKey] = $entity->id;
                $previousForeignEntity = $this->getEntityManager()->getRepository($foreignEntityType)->where($where)->findOne();
                if ($previousForeignEntity) {
                    if (!$entity->isNew()) {
                        $entity->setFetched($idAttribute, $previousForeignEntity->id);
                    }
                    if ($previousForeignEntity->id !== $entity->get($idAttribute)) {
                        $previousForeignEntity->set($foreignKey, null);
                        $this->getEntityManager()->saveEntity($previousForeignEntity);
                    }
                } else {
                    if (!$entity->isNew()) {
                        $entity->setFetched($idAttribute, null);
                    }
                }

                if ($entity->get($idAttribute)) {
                    $newForeignEntity = $this->getEntityManager()->getEntity($foreignEntityType, $entity->get($idAttribute));
                    if ($newForeignEntity) {
                        $newForeignEntity->set($foreignKey, $entity->id);
                        $this->getEntityManager()->saveEntity($newForeignEntity);
                    } else {
                        $entity->set($idAttribute, null);
                    }
                }
            }
        }
    }
}
