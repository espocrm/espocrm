<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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
    protected $dependencies = array(
        'metadata',
        'config',
        'fieldManagerUtil'
    );

    protected $injections = array();

    private $restoreData = null;

    protected $hooksDisabled = false;

    protected $processFieldsAfterSaveDisabled = false;

    protected $processFieldsBeforeSaveDisabled = false;

    protected $processFieldsAfterRemoveDisabled = false;

    protected function addDependency($name)
    {
        $this->dependencies[] = $name;
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
        return $this->dependencies;
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
        $this->handleEmailAddressParams($params);
        $this->handlePhoneNumberParams($params);
        $this->handleCurrencyParams($params);
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

    protected function beforeRemove(Entity $entity, array $options = array())
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
            $entity->set('modifiedById', $this->getEntityManager()->getUser()->id);
        }
    }

    protected function afterRemove(Entity $entity, array $options = array())
    {
        parent::afterRemove($entity, $options);

        if (!$this->processFieldsAfterRemoveDisabled) {
            $this->processArrayFieldsRemove($entity);
        }

        if (!$this->hooksDisabled && empty($options['skipHooks'])) {
            $this->getEntityManager()->getHookManager()->process($this->entityType, 'afterRemove', $entity, $options);
        }
    }

    protected function afterMassRelate(Entity $entity, $relationName, array $params = array(), array $options = array())
    {
        if (!$this->hooksDisabled && empty($options['skipHooks'])) {
            $hookData = array(
                'relationName' => $relationName,
                'relationParams' => $params
            );
            $this->getEntityManager()->getHookManager()->process($this->entityType, 'afterMassRelate', $entity, $options, $hookData);
        }
    }

    public function remove(Entity $entity, array $options = array())
    {
        $result = parent::remove($entity, $options);
        return $result;
    }

    protected function afterRelate(Entity $entity, $relationName, $foreign, $data = null, array $options = array())
    {
        parent::afterRelate($entity, $relationName, $foreign, $data, $options);

        if ($foreign instanceof Entity) {
            $foreignEntity = $foreign;
            if (!$this->hooksDisabled && empty($options['skipHooks'])) {
                $hookData = array(
                    'relationName' => $relationName,
                    'relationData' => $data,
                    'foreignEntity' => $foreignEntity
                );
                $this->getEntityManager()->getHookManager()->process($this->entityType, 'afterRelate', $entity, $options, $hookData);
            }
        }
    }

    protected function afterUnrelate(Entity $entity, $relationName, $foreign, array $options = array())
    {
        parent::afterUnrelate($entity, $relationName, $foreign, $options);

        if ($foreign instanceof Entity) {
            $foreignEntity = $foreign;
            if (!$this->hooksDisabled && empty($options['skipHooks'])) {
                $hookData = array(
                    'relationName' => $relationName,
                    'foreignEntity' => $foreignEntity
                );
                $this->getEntityManager()->getHookManager()->process($this->entityType, 'afterUnrelate', $entity, $options, $hookData);
            }
        }
    }

    protected function beforeSave(Entity $entity, array $options = array())
    {
        parent::beforeSave($entity, $options);

        if (!$this->hooksDisabled && empty($options['skipHooks'])) {
            $this->getEntityManager()->getHookManager()->process($this->entityType, 'beforeSave', $entity, $options);
        }

        if (!$this->processFieldsBeforeSaveDisabled) {
            $this->processCurrencyFieldsBeforeSave($entity);
        }
    }

    protected function afterSave(Entity $entity, array $options = array())
    {
        if (!empty($this->restoreData)) {
            $entity->set($this->restoreData);
            $this->restoreData = null;
        }
        parent::afterSave($entity, $options);

        if (!$this->processFieldsAfterSaveDisabled) {
            $this->processEmailAddressSave($entity);
            $this->processPhoneNumberSave($entity);
            $this->processSpecifiedRelationsSave($entity);
            $this->processFileFieldsSave($entity);
            $this->processArrayFieldsSave($entity);
            $this->processWysiwygFieldsSave($entity);
        }

        if (!$this->hooksDisabled && empty($options['skipHooks'])) {
            $this->getEntityManager()->getHookManager()->process($this->entityType, 'afterSave', $entity, $options);
        }
    }

    public function save(Entity $entity, array $options = array())
    {
        $nowString = date('Y-m-d H:i:s', time());
        $restoreData = array();

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

    protected function processSpecifiedRelationsSave(Entity $entity)
    {
        $relationTypeList = [$entity::HAS_MANY, $entity::MANY_MANY, $entity::HAS_CHILDREN];
        foreach ($entity->getRelations() as $name => $defs) {
            if (in_array($defs['type'], $relationTypeList)) {
                $fieldName = $name . 'Ids';
                $columnsFieldsName = $name . 'Columns';


                if ($entity->has($fieldName) || $entity->has($columnsFieldsName)) {
                    if ($this->getMetadata()->get("entityDefs." . $entity->getEntityType() . ".fields.{$name}.noSave")) {
                        continue;
                    }

                    if ($entity->has($fieldName)) {
                        $specifiedIds = $entity->get($fieldName);
                    } else {
                        $specifiedIds = array();
                        foreach ($entity->get($columnsFieldsName) as $id => $d) {
                            $specifiedIds[] = $id;
                        }
                    }
                    if (is_array($specifiedIds)) {
                        $toRemoveIds = array();
                        $existingIds = array();
                        $toUpdateIds = array();
                        $existingColumnsData = new \stdClass();

                        $defs = array();
                        $columns = $this->getMetadata()->get("entityDefs." . $entity->getEntityType() . ".fields.{$name}.columns");
                        if (!empty($columns)) {
                            $columnData = $entity->get($columnsFieldsName);
                            $defs['additionalColumns'] = $columns;
                        }

                        $foreignCollection = $entity->get($name, $defs);
                        if ($foreignCollection) {
                            foreach ($foreignCollection as $foreignEntity) {
                                $existingIds[] = $foreignEntity->id;
                                if (!empty($columns)) {
                                    $data = new \stdClass();
                                    foreach ($columns as $columnName => $columnField) {
                                        $foreignId = $foreignEntity->id;
                                        $data->$columnName = $foreignEntity->get($columnField);
                                    }
                                    $existingColumnsData->$foreignId = $data;
                                    if (!$entity->isNew()) {
                                        $entity->setFetched($columnsFieldsName, $existingColumnsData);
                                    }
                                }

                            }
                        }

                        if (!$entity->isNew()) {
                            if ($entity->has($fieldName)) {
                                $entity->setFetched($fieldName, $existingIds);
                            }
                            if ($entity->has($columnsFieldsName) && !empty($columns)) {
                                $entity->setFetched($columnsFieldsName, $existingColumnsData);
                            }
                        }

                        foreach ($existingIds as $id) {
                            if (!in_array($id, $specifiedIds)) {
                                $toRemoveIds[] = $id;
                            } else {
                                if (!empty($columns)) {
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
                                                $toUpdateIds[] = $id;
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        foreach ($specifiedIds as $id) {
                            if (!in_array($id, $existingIds)) {
                                $data = null;
                                if (!empty($columns) && isset($columnData->$id)) {
                                    $data = $columnData->$id;
                                }
                                $this->relate($entity, $name, $id, $data);
                            }
                        }
                        foreach ($toRemoveIds as $id) {
                            $this->unrelate($entity, $name, $id);
                        }
                        if (!empty($columns)) {
                            foreach ($toUpdateIds as $id) {
                                $data = $columnData->$id;
                                $this->updateRelation($entity, $name, $id, $data);
                            }
                        }
                    }
                }
            } else if ($defs['type'] === $entity::HAS_ONE) {
                if (empty($defs['entity']) || empty($defs['foreignKey'])) continue;

                if ($this->getMetadata()->get("entityDefs." . $entity->getEntityType() . ".fields.{$name}.noSave")) {
                    continue;
                }

                $foreignEntityType = $defs['entity'];
                $foreignKey = $defs['foreignKey'];
                $idFieldName = $name . 'Id';
                $nameFieldName = $name . 'Name';

                if (!$entity->has($idFieldName)) continue;

                $where = array();
                $where[$foreignKey] = $entity->id;
                $previousForeignEntity = $this->getEntityManager()->getRepository($foreignEntityType)->where($where)->findOne();
                if ($previousForeignEntity) {
                    if (!$entity->isNew()) {
                        $entity->setFetched($idFieldName, $previousForeignEntity->id);
                    }
                    if ($previousForeignEntity->id !== $entity->get($idFieldName)) {
                        $previousForeignEntity->set($foreignKey, null);
                        $this->getEntityManager()->saveEntity($previousForeignEntity);
                    }
                } else {
                    if (!$entity->isNew()) {
                        $entity->setFetched($idFieldName, null);
                    }
                }

                if ($entity->get($idFieldName)) {
                    $newForeignEntity = $this->getEntityManager()->getEntity($foreignEntityType, $entity->get($idFieldName));
                    if ($newForeignEntity) {
                        $newForeignEntity->set($foreignKey, $entity->id);
                        $this->getEntityManager()->saveEntity($newForeignEntity);
                    } else {
                        $entity->set($idFieldName, null);
                    }
                }
            }
        }
    }
}

