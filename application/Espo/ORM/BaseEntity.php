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

namespace Espo\ORM;

use StdClass;

abstract class BaseEntity implements Entity
{
    public $id = null;

    private $isNew = false;

    private $isSaved = false;

    protected $entityType;

    /**
     * @todo Make private.
     */
    public $fields = [];

    /**
     * @todo Make private.
     */
    public $relations = [];

    /**
     * @var array Field-Value pairs.
     */
    protected $valuesContainer = [];

    /**
     * @var array Field-Value pairs of initial values (fetched from DB).
     */
    protected $fetchedValuesContainer = [];

    protected $entityManager;

    protected $isFetched = false;

    protected $isBeingSaved = false;

    public function __construct(array $defs = [], ?EntityManager $entityManager = null)
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

    public function clear(?string $name = null)
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

    public function get(string $name, $params = [])
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

    public function has(string $name) : bool
    {
        if ($name == 'id') {
            return (bool) $this->id;
        }
        $method = '_has' . ucfirst($name);
        if (method_exists($this, $method)) {
            return (bool) $this->$method();
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
                            if (!($value instanceof StdClass) && !is_array($value)) {
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

    /**
     * Is an entity new.
     */
    public function isNew()
    {
        return $this->isNew;
    }

    /**
     * Set as new.
     */
    public function setIsNew(bool $isNew)
    {
        $this->isNew = $isNew;
    }

    /**
     * Whether an entity was saved.
     */
    public function isSaved() : bool
    {
        return $this->isSaved;
    }

    /**
     * Set as saved.
     */
    public function setIsSaved(bool $isSaved)
    {
        $this->isSaved = $isSaved;
    }

    /**
     * @deprecated
     */
    public function getEntityName()
    {
        return $this->getEntityType();
    }

    /**
     * Get an entity type.
     */
    public function getEntityType()
    {
        return $this->entityType;
    }

    /**
     * @deprecated
     */
    public function hasField($fieldName)
    {
        return isset($this->fields[$fieldName]);
    }

    /**
     * Whether an entity type has an attribute defined.
     */
    public function hasAttribute(string $name) : bool
    {
        return isset($this->fields[$name]);
    }

    /**
     * Whether an entity type has a relation defined.
     */
    public function hasRelation(string $relationName) : bool
    {
        return isset($this->relations[$relationName]);
    }

    /**
     * Get attribute list defined for an entity type.
     */
    public function getAttributeList() : array
    {
        return array_keys($this->getAttributes());
    }

    /**
     * Get relation list defined for an entity type.
     */
    public function getRelationList()
    {
        return array_keys($this->getRelations());
    }

    /**
     * @deprecated
     */
    public function getValues()
    {
        return $this->toArray();
    }

    /**
     * @deprecated
     */
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

    /**
     * Get values.
     */
    public function getValueMap() : StdClass
    {
        $array = $this->toArray();
        return (object) $array;
    }

    /**
     * @deprecated
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Get attribute definitions.
     */
    public function getAttributes()
    {
        return $this->fields;
    }

    /**
     * Get relation definitions.
     */
    public function getRelations()
    {
        return $this->relations;
    }

    /**
     * Get an attribute type.
     */
    public function getAttributeType(string $attribute) : ?string
    {
        if (isset($this->fields[$attribute]) && isset($this->fields[$attribute]['type'])) {
            return $this->fields[$attribute]['type'];
        }
        return null;
    }

    /**
     * Get a relation type.
     */
    public function getRelationType(string $relation) : ?string
    {
        if (isset($this->relations[$relation]) && isset($this->relations[$relation]['type'])) {
            return $this->relations[$relation]['type'];
        }
        return null;
    }

    /**
     * Get an attribute parameter.
     */
    public function getAttributeParam(string $attribute, string $name)
    {
        if (isset($this->fields[$attribute]) && isset($this->fields[$attribute][$name])) {
            return $this->fields[$attribute][$name];
        }
        return null;
    }

    /**
     * Get a relation parameter.
     */
    public function getRelationParam(string $relation, string $name)
    {
        if (isset($this->relations[$relation]) && isset($this->relations[$relation][$name])) {
            return $this->relations[$relation][$name];
        }
        return null;
    }

    /**
     * Whether is fetched from DB.
     */
    public function isFetched() : bool
    {
        return $this->isFetched;
    }

    /**
     * @deprecated
     */
    public function isFieldChanged($name)
    {
        return $this->has($name) && ($this->get($name) != $this->getFetched($name));
    }

    /**
     * Whether an attribute was changed (since syncing with DB).
     */
    public function isAttributeChanged(string $name) : bool
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

    /**
     * Set a fetched value for a specific attribute.
     */
    public function setFetched(string $name, $value)
    {
        if ($value) {
            $type = $this->getAttributeType($name);
            if ($type === self::JSON_OBJECT) {
                $value = self::cloneObject($value);
            } else if ($type === self::JSON_ARRAY) {
                $value = self::cloneArray($value);
            }
        }

        $this->fetchedValuesContainer[$name] = $value;
    }

    /**
     * Get a fetched value of a specific attribute.
     */
    public function getFetched(string $name)
    {
        if ($name === 'id') {
            return $this->id;
        }
        if (isset($this->fetchedValuesContainer[$name])) {
            return $this->fetchedValuesContainer[$name];
        }
        return null;
    }

    /**
     * Whether a fetched value is set for a specific attribute.
     */
    public function hasFetched(string $name)
    {
        if ($name === 'id') {
            return !!$this->id;
        }
        return array_key_exists($name, $this->fetchedValuesContainer);
    }

    /**
     * Clear all set fetched values.
     */
    public function resetFetchedValues()
    {
        $this->fetchedValuesContainer = [];
    }

    /**
     * Copy all current values to fetched values.
     */
    public function updateFetchedValues()
    {
        $this->fetchedValuesContainer = $this->valuesContainer;

        foreach ($this->fetchedValuesContainer as $attribute => $value) {
            $this->setFetched($attribute, $value);
        }
    }

    /**
     * Set an entity as fetched.
     */
    public function setAsFetched()
    {
        $this->isFetched = true;
        $this->updateFetchedValues();
    }

    /**
     * Set an entity as not fetched.
     */
    public function setAsNotFetched()
    {
        $this->isFetched = false;
        $this->resetFetchedValues();
    }

    /**
     * Whether an entity is being saved.
     */
    public function isBeingSaved() : bool
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

    /**
     * Set defined default values.
     */
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

    protected function cloneArray($value)
    {
        if (is_array($value)) {
            $copy = [];
            foreach ($value as $v) {
                if (is_object($v)) {
                    $v = clone $v;
                }
                $copy[] = $v;
            }
            return $copy;
        }

        return $value;
    }

    protected function cloneObject($value)
    {
        if (is_array($value)) {
            $copy = [];
            foreach ($value as $v) {
                $copy[] = self::cloneObject($v);
            }
            return $copy;
        }

        if (is_object($value)) {
            $copy = (object) [];
            foreach (get_object_vars($value) as $k => $v) {
                $key = $k;
                if (!is_string($key)) {
                    $key = strval($key);
                }
                $copy->$key = self::cloneObject($v);
            }
            return $copy;
        }

        return $value;
    }
}
