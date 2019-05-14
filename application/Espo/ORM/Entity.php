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

namespace Espo\ORM;

abstract class Entity implements IEntity
{
    public $id = null;

    private $isNew = false;

    private $isSaved = false;

    protected $entityType;

    public $fields = [];


    public $relations = [];

    /**
     * @var array Field-Value pairs.
     */
    protected $valuesContainer = [];

    /**
     * @var array Field-Value pairs of initial values (fetched from DB).
     */
    protected $fetchedValuesContainer = [];

    /**
     * @var EntityManager Entity Manager.
     */
    protected $entityManager;

    protected $isFetched = false;

    protected $isBeingSaved = false;

    public function __construct($defs = [], ?EntityManager $entityManager = null)
    {
        if (empty($this->entityType)) {
            $classNames = explode('\\', get_class($this));
            $this->entityType = end($classNames);
        }

        $this->entityManager = $entityManager;

        if (!empty($defs['fields'])) {
            $this->fields = $defs['fields'];
        }

        if (!empty($defs['relations'])) {
            $this->relations = $defs['relations'];
        }
    }

    public function clear($name = null)
    {
        if (is_null($name)) {
            $this->reset();
        }
        unset($this->valuesContainer[$name]);
    }

    public function reset()
    {
        $this->valuesContainer = [];
    }

    protected function setValue($name, $value)
    {
        $this->valuesContainer[$name] = $value;
    }

    public function set($p1, $p2 = null)
    {
        if (is_array($p1) || is_object($p1)) {
            if (is_object($p1)) {
                $p1 = get_object_vars($p1);
            }
            if ($p2 === null) {
                $p2 = false;
            }
            $this->populateFromArray($p1, $p2);
        } else if (is_string($p1)) {
            $name = $p1;
            $value = $p2;
            if ($name == 'id') {
                $this->id = $value;
            }
            if ($this->hasAttribute($name)) {
                $method = '_set' . ucfirst($name);
                if (method_exists($this, $method)) {
                    $this->$method($value);
                } else {
                    $this->valuesContainer[$name] = $value;
                }
            }
        }
    }

    public function get($name, $params = [])
    {
        if ($name == 'id') {
            return $this->id;
        }
        $method = '_get' . ucfirst($name);
        if (method_exists($this, $method)) {
            return $this->$method();
        }

        if ($this->hasAttribute($name) && isset($this->valuesContainer[$name])) {
            return $this->valuesContainer[$name];
        }

        if ($this->hasRelation($name) && $this->id) {
            $value = $this->entityManager->getRepository($this->getEntityType())->findRelated($this, $name, $params);
            return $value;
        }

        return null;
    }

    public function has($name)
    {
        if ($name == 'id') {
            return !!$this->id;
        }
        $method = '_has' . ucfirst($name);
        if (method_exists($this, $method)) {
            return $this->$method();
        }

        if (array_key_exists($name, $this->valuesContainer)) {
            return true;
        }
        return false;
    }

    public function populateFromArray(array $data, $onlyAccessible = true, $reset = false)
    {
        if ($reset) {
            $this->reset();
        }

        foreach ($this->getAttributes() as $attribute => $defs) {
            if (array_key_exists($attribute, $data)) {
                if ($attribute == 'id') {
                    $this->id = $data[$attribute];
                    continue;
                }
                if ($onlyAccessible) {
                    if (isset($defs['notAccessible']) && $defs['notAccessible'] == true) {
                        continue;
                    }
                }

                $value = $data[$attribute];

                if (!is_null($value)) {
                    $valueType = $defs['type'];
                    if ($valueType === self::FOREIGN) {
                        $relation = $this->getAttributeParam($attribute, 'relation');
                        $foreign = $this->getAttributeParam($attribute, 'foreign');
                        if (is_string($foreign)) {
                            $foreignEntityType = $this->getRelationParam($relation, 'entity');
                            if ($foreignEntityType) {
                                $valueType = $this->entityManager->getMetadata()->get($foreignEntityType, ['fields', $foreign, 'type']);
                            }
                        }
                    }
                    switch ($valueType) {
                        case self::VARCHAR:
                            break;
                        case self::BOOL:
                            $value = ($value === 'true' || $value === '1' || $value === true);
                            break;
                        case self::INT:
                            $value = intval($value);
                            break;
                        case self::FLOAT:
                            $value = floatval($value);
                            break;
                        case self::JSON_ARRAY:
                            $value = is_string($value) ? json_decode($value) : $value;
                            if (!is_array($value)) {
                                $value = null;
                            }
                            break;
                        case self::JSON_OBJECT:
                            $value = is_string($value) ? json_decode($value) : $value;
                            if (!($value instanceof \stdClass) && !is_array($value)) {
                                $value = null;
                            }
                            break;
                        default:
                            break;
                    }
                }

                $method = '_set' . ucfirst($attribute);
                if (method_exists($this, $method)) {
                    $this->$method($value);
                } else {
                    $this->valuesContainer[$attribute] = $value;
                }
            }
        }
    }

    public function isNew()
    {
        return $this->isNew;
    }

    public function setIsNew($isNew)
    {
        $this->isNew = $isNew;
    }

    public function isSaved()
    {
        return $this->isSaved;
    }

    public function setIsSaved($isSaved)
    {
        $this->isSaved = $isSaved;
    }

    public function getEntityName()
    {
        return $this->getEntityType();
    }

    public function getEntityType()
    {
        return $this->entityType;
    }

    public function hasField($fieldName)
    {
        return isset($this->fields[$fieldName]);
    }

    public function hasAttribute($name)
    {
        return isset($this->fields[$name]);
    }

    public function hasRelation($relationName)
    {
        return isset($this->relations[$relationName]);
    }

    public function getAttributeList()
    {
        return array_keys($this->getAttributes());
    }

    public function getRelationList()
    {
        return array_keys($this->getRelations());
    }

    public function getValues()
    {
        return $this->toArray();
    }

    public function toArray()
    {
        $arr = [];
        if (isset($this->id)) {
            $arr['id'] = $this->id;
        }
        foreach ($this->fields as $field => $defs) {
            if ($field == 'id') {
                continue;
            }
            if ($this->has($field)) {
                $arr[$field] = $this->get($field);
            }

        }
        return $arr;
    }

    public function getValueMap()
    {
        $array = $this->toArray();
        return (object) $array;
    }

    public function getFields()
    {
        return $this->fields;
    }

    public function getAttributes()
    {
        return $this->fields;
    }

    public function getRelations()
    {
        return $this->relations;
    }

    public function getAttributeType($attribute)
    {
        if (isset($this->fields[$attribute]) && isset($this->fields[$attribute]['type'])) {
            return $this->fields[$attribute]['type'];
        }
        return null;
    }

    public function getRelationType($relation)
    {
        if (isset($this->relations[$relation]) && isset($this->relations[$relation]['type'])) {
            return $this->relations[$relation]['type'];
        }
        return null;
    }

    public function getAttributeParam($attribute, $name)
    {
        if (isset($this->fields[$attribute]) && isset($this->fields[$attribute][$name])) {
            return $this->fields[$attribute][$name];
        }
        return null;
    }

    public function getRelationParam($relation, $name)
    {
        if (isset($this->relations[$relation]) && isset($this->relations[$relation][$name])) {
            return $this->relations[$relation][$name];
        }
        return null;
    }

    public function isFetched()
    {
        return $this->isFetched;
    }

    public function isFieldChanged($name)
    {
        return $this->has($name) && ($this->get($name) != $this->getFetched($name));
    }

    public function isAttributeChanged($name)
    {
        if (!$this->has($name)) return false;

        if (!$this->hasFetched($name)) {
            return true;
        }
        return !self::areValuesEqual(
            $this->getAttributeType($name),
            $this->get($name),
            $this->getFetched($name),
            $this->getAttributeParam($name, 'isUnordered')
        );
    }

    public static function areValuesEqual($type, $v1, $v2, $isUnordered = false)
    {
        if ($type === self::JSON_ARRAY) {
            if (is_array($v1) && is_array($v2)) {
                if ($isUnordered) {
                    sort($v1);
                    sort($v2);
                }
                if ($v1 != $v2) {
                    return false;
                }
                foreach ($v1 as $i => $itemValue) {
                    if (is_object($v1[$i]) && is_object($v2[$i])) {
                        if (!self::areValuesEqual(self::JSON_OBJECT, $v1[$i], $v2[$i])) {
                            return false;
                        }
                        continue;
                    }
                    if ($v1[$i] !== $v2[$i]) {
                        return false;
                    }
                }
                return true;
            }
        } else if ($type === self::JSON_OBJECT) {
            if (is_object($v1) && is_object($v2)) {
                if ($v1 != $v2) {
                    return false;
                }
                $a1 = get_object_vars($v1);
                $a2 = get_object_vars($v2);
                foreach ($v1 as $key => $itemValue) {
                    if (is_object($a1[$key]) && is_object($a2[$key])) {
                        if (!self::areValuesEqual(self::JSON_OBJECT, $a1[$key], $a2[$key])) {
                            return false;
                        }
                        continue;
                    }
                    if (is_array($a1[$key]) && is_array($a2[$key])) {
                        if (!self::areValuesEqual(self::JSON_ARRAY, $a1[$key], $a2[$key])) {
                            return false;
                        }
                        continue;
                    }
                    if ($a1[$key] !== $a2[$key]) {
                        return false;
                    }
                }
                return true;
            }
        }

        return $v1 === $v2;
    }

    public function setFetched($name, $value)
    {
        $this->fetchedValuesContainer[$name] = $value;
    }

    public function getFetched($name)
    {
        if ($name === 'id') {
            return $this->id;
        }
        if (isset($this->fetchedValuesContainer[$name])) {
            return $this->fetchedValuesContainer[$name];
        }
        return null;
    }

    public function hasFetched($name)
    {
        if ($name === 'id') {
            return !!$this->id;
        }
        return array_key_exists($name, $this->fetchedValuesContainer);
    }

    public function resetFetchedValues()
    {
        $this->fetchedValuesContainer = [];
    }

    public function updateFetchedValues()
    {
        $this->fetchedValuesContainer = $this->valuesContainer;
    }

    public function setAsFetched()
    {
        $this->isFetched = true;
        $this->fetchedValuesContainer = $this->valuesContainer;
    }

    public function setAsNotFetched()
    {
        $this->isFetched = false;
        $this->resetFetchedValues();
    }

    public function isBeingSaved()
    {
        return $this->isBeingSaved;
    }

    public function setAsBeingSaved()
    {
        $this->isBeingSaved = true;
    }

    public function setAsNotBeingSaved()
    {
        $this->isBeingSaved = false;
    }

    public function populateDefaults()
    {
        foreach ($this->fields as $field => $defs) {
            if (array_key_exists('default', $defs)) {
                $this->valuesContainer[$field] = $defs['default'];
            }
        }
    }

    protected function getEntityManager()
    {
        return $this->entityManager;
    }
}
