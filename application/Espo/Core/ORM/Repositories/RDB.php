<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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
        'metadata'
    );

    protected $injections = array();

    private $restoreData = null;

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

    public function handleSelectParams(&$params)
    {
        $this->handleEmailAddressParams($params);
        $this->handlePhoneNumberParams($params);
        $this->handleCurrencyParams($params);
    }

    protected function handleCurrencyParams(&$params)
    {
        $entityName = $this->entityName;

        $metadata = $this->getMetadata();

        if (!$metadata) {
            return;
        }

        $defs = $metadata->get('entityDefs.' . $entityName);

        foreach ($defs['fields'] as $field => $d) {
            if (isset($d['type']) && $d['type'] == 'currency') {
                if (!empty($d['notStorable'])) {
                    continue;
                }
                if (empty($params['customJoin'])) {
                    $params['customJoin'] = '';
                }
                $alias = Util::toUnderScore($field) . "_currency_alias";
                $params['customJoin'] .= "
                    LEFT JOIN currency AS `{$alias}` ON {$alias}.id = ".Util::toUnderScore($entityName).".".Util::toUnderScore($field)."_currency
                ";
            }
        }

    }

    protected function handleEmailAddressParams(&$params)
    {
        $entityName = $this->entityName;

        $defs = $this->getEntityManager()->getMetadata()->get($entityName);
        if (!empty($defs['relations']) && array_key_exists('emailAddresses', $defs['relations'])) {
            if (empty($params['leftJoins'])) {
                $params['leftJoins'] = array();
            }
            if (empty($params['whereClause'])) {
                $params['whereClause'] = array();
            }
            if (empty($params['joinConditions'])) {
                $params['joinConditions'] = array();
            }
            $params['leftJoins'][] = 'emailAddresses';
            $params['joinConditions']['emailAddresses'] = array(
                'primary' => 1
            );
        }
    }

    protected function handlePhoneNumberParams(&$params)
    {
        $entityName = $this->entityName;

        $defs = $this->getEntityManager()->getMetadata()->get($entityName);
        if (!empty($defs['relations']) && array_key_exists('phoneNumbers', $defs['relations'])) {
            if (empty($params['leftJoins'])) {
                $params['leftJoins'] = array();
            }
            if (empty($params['whereClause'])) {
                $params['whereClause'] = array();
            }
            if (empty($params['joinConditions'])) {
                $params['joinConditions'] = array();
            }
            $params['leftJoins'][] = 'phoneNumbers';
            $params['joinConditions']['phoneNumbers'] = array(
                'primary' => 1
            );
        }
    }

    protected function beforeRemove(Entity $entity, array $options = array())
    {
        parent::beforeRemove($entity, $options);
        $this->getEntityManager()->getHookManager()->process($this->entityName, 'beforeRemove', $entity, $options);

        $nowString = date('Y-m-d H:i:s', time());
        if ($entity->hasField('modifiedAt')) {
            $entity->set('modifiedAt', $nowString);
        }
        if ($entity->hasField('modifiedById')) {
            $entity->set('modifiedById', $this->getEntityManager()->getUser()->id);
        }
    }

    protected function afterRemove(Entity $entity, array $options = array())
    {
        parent::afterRemove($entity, $options);
        $this->getEntityManager()->getHookManager()->process($this->entityName, 'afterRemove', $entity, $options);
    }

    public function remove(Entity $entity, array $options = array())
    {
        $this->getEntityManager()->getHookManager()->process($this->entityName, 'beforeRemove', $entity, $options);

        $result = parent::remove($entity, $options);
        if ($result) {
            $this->getEntityManager()->getHookManager()->process($this->entityName, 'afterRemove', $entity, $options);
        }
        return $result;
    }

    protected function beforeSave(Entity $entity, array $options = array())
    {
        parent::beforeSave($entity, $options);

        $this->getEntityManager()->getHookManager()->process($this->entityName, 'beforeSave', $entity, $options);
    }

    protected function afterSave(Entity $entity, array $options = array())
    {
        if (!empty($this->restoreData)) {
            $entity->set($this->restoreData);
            $this->restoreData = null;
        }
        parent::afterSave($entity, $options);

        $this->handleEmailAddressSave($entity);
        $this->handlePhoneNumberSave($entity);
        $this->handleSpecifiedRelations($entity);

        $this->getEntityManager()->getHookManager()->process($this->entityName, 'afterSave', $entity, $options);
    }

    public function save(Entity $entity, array $options = array())
    {
        $nowString = date('Y-m-d H:i:s', time());
        $restoreData = array();

        if ($entity->isNew()) {
            if (!$entity->has('id')) {
                $entity->set('id', Util::generateId());
            }

            if ($entity->hasField('createdAt')) {
                $entity->set('createdAt', $nowString);
            }
            if ($entity->hasField('modifiedAt')) {
                $entity->set('modifiedAt', $nowString);
            }
            if ($entity->hasField('createdById')) {
                $entity->set('createdById', $this->entityManager->getUser()->id);
            }

            if ($entity->has('modifiedById')) {
                $restoreData['modifiedById'] = $entity->get('modifiedById');
            }
            if ($entity->has('modifiedAt')) {
                $restoreData['modifiedAt'] = $entity->get('modifiedAt');
            }
            $entity->clear('modifiedById');
        } else {
            if (empty($options['silent'])) {
                if ($entity->hasField('modifiedAt')) {
                    $entity->set('modifiedAt', $nowString);
                }
                if ($entity->hasField('modifiedById')) {
                    $entity->set('modifiedById', $this->entityManager->getUser()->id);
                }
            }

            if ($entity->has('createdById')) {
                $restoreData['createdById'] = $entity->get('createdById');
            }
            if ($entity->has('createdAt')) {
                $restoreData['createdAt'] = $entity->get('createdAt');
            }
            $entity->clear('createdById');
            $entity->clear('createdAt');
        }
        $this->restoreData = $restoreData;

        $result = parent::save($entity);

        return $result;
    }

    protected function handleEmailAddressSave(Entity $entity)
    {
        if ($entity->hasRelation('emailAddresses') && $entity->hasField('emailAddress')) {
            $emailAddressRepository = $this->getEntityManager()->getRepository('EmailAddress')->storeEntityEmailAddress($entity);
        }
    }

    protected function handlePhoneNumberSave(Entity $entity)
    {
        if ($entity->hasRelation('phoneNumbers') && $entity->hasField('phoneNumber')) {
            $emailAddressRepository = $this->getEntityManager()->getRepository('PhoneNumber')->storeEntityPhoneNumber($entity);
        }
    }

    protected function handleSpecifiedRelations(Entity $entity)
    {
        $relationTypes = array($entity::HAS_MANY, $entity::MANY_MANY, $entity::HAS_CHILDREN);
        foreach ($entity->getRelations() as $name => $defs) {
            if (in_array($defs['type'], $relationTypes)) {
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
                                    $entity->setFetched($columnsFieldsName, $existingColumnsData);
                                }

                            }
                        }

                        if ($entity->has($fieldName)) {
                            $entity->setFetched($fieldName, $existingIds);
                        }
                        if ($entity->has($columnsFieldsName) && !empty($columns)) {
                            $entity->setFetched($columnsFieldsName, $existingColumnsData);
                        }

                        foreach ($existingIds as $id) {
                            if (!in_array($id, $specifiedIds)) {
                                $toRemoveIds[] = $id;
                            } else {
                                if (!empty($columns)) {
                                    foreach ($columns as $columnName => $columnField) {
                                        if ($columnData->$id->$columnName != $existingColumnsData->$id->$columnName) {
                                            $toUpdateIds[] = $id;
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
            }
        }
    }
}

