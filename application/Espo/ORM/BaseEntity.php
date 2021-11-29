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

namespace Espo\ORM;

use Espo\ORM\Value\ValueAccessorFactory;

use const E_USER_DEPRECATED;

use stdClass;
use InvalidArgumentException;
use RuntimeException;

class BaseEntity implements Entity
{
    public $id = null;

    protected $entityType;

    private $isNotNew = false;

    private $isSaved = false;

    private $isFetched = false;

    private $isBeingSaved = false;

    /**
     * @todo Make private. Rename to `attributes`.
     * @deprecated
     */
    public $fields = [];

    private $attributes = [];

    /**
     * @todo Make private.
     * @deprecated
     */
    protected $relations = [];

    /**
     * An attribute-value map.
     *
     * @deprecated
     * @todo Make private.
     */
    protected $valuesContainer = [];

    /**
     * An attribute-value map of values fetched from DB (before changed).
     *
     * @deprecated
     * @todo Make private.
     */
    private $fetchedValuesContainer = [];

    /**
     * @var EntityManager|null
     */
    protected $entityManager;

    /**
     * @var Value\ValueAccessor|null
     */
    private $valueAccessor = null;

    public function __construct(
        string $entityType,
        array $defs,
        ?EntityManager $entityManager = null,
        ?ValueAccessorFactory $valueAccessorFactory = null
    ) {
        $this->entityType = $entityType;

        $this->entityManager = $entityManager;

        $this->attributes = $defs['attributes'] ?? $defs['fields'] ?? $this->attributes;
        $this->relations = $defs['relations'] ?? $this->relations;

        $this->fields = $this->attributes;

        if ($valueAccessorFactory) {
            $this->valueAccessor = $valueAccessorFactory->create($this);
        }
    }

    /**
     * Get an entity ID.
     */
    public function getId(): ?string
    {
        return $this->get('id');
    }

    /**
     * Clear an attribute value.
     */
    public function clear(string $attribute): void
    {
        unset($this->valuesContainer[$attribute]);
    }

    /**
     * Reset all attributes (empty an entity).
     */
    public function reset(): void
    {
        $this->valuesContainer = [];
    }

    /**
     * @deprecated Use `setInContainer` method.
     */
    protected function setValue($attribute, $value): void
    {
        $this->setInContainer($attribute, $value);
    }

    /**
     * Set an attribute value or multiple attribute values.
     *
     * Two usage options:
     * * `set(string $attribute, mixed $value)`
     * * `set(array|object $valueMap)`
     *
     * @param string|object|array $attribute
     * @param mixed $value
     */
    public function set($attribute, $value = null): void
    {
        $p1 = $attribute;
        $p2 = $value;

        /** @phpstan-var mixed $p1 */
        /** @phpstan-var mixed $p2 */

        if (is_array($p1) || is_object($p1)) {
            if (is_object($p1)) {
                $p1 = get_object_vars($p1);
            }

            if ($p2 === null) {
                $p2 = false;
            }

            if ($p2) {
                trigger_error(
                    'Second parameter is deprecated in Entity::set(array, onlyAccessible).',
                    E_USER_DEPRECATED
                );
            }

            $this->populateFromArray($p1, $p2);

            return;
        }

        if (is_string($p1)) {
            $name = $p1;
            $value = $p2;

            if ($name == 'id') {
                $this->id = $value;
            }

            if (!$this->hasAttribute($name)) {
                return;
            }

            $method = '_set' . ucfirst($name);

            if (method_exists($this, $method)) {
                $this->$method($value);

                return;
            }

            $this->populateFromArray([
                $name => $value,
            ]);

            return;
        }

        throw new InvalidArgumentException();
    }

    /**
     * Get an attribute value.
     *
     * @param array $params @deprecated
     *
     * @retrun mixed
     */
    public function get(string $attribute, $params = [])
    {
        if ($attribute === 'id') {
            return $this->id;
        }

        $method = '_get' . ucfirst($attribute);

        if (method_exists($this, $method)) {
            return $this->$method();
        }

        if ($this->hasAttribute($attribute) && $this->hasInContainer($attribute)) {
            return $this->getFromContainer($attribute);
        }

        // @todo Remove this.
        if (!empty($params)) {
            trigger_error(
                'Second parameter will be removed from the method Entity::get.',
                E_USER_DEPRECATED
            );
        }

        // @todo Remove this.
        if ($this->hasRelation($attribute) && $this->id && $this->entityManager) {
            trigger_error(
                "Accessing related records with Entity::get is deprecated. " .
                "Use \$repository->getRelation(...)->find()",
                E_USER_DEPRECATED
            );

            /** @phpstan-ignore-next-line */
            return $this->entityManager
                ->getRepository($this->getEntityType())
                ->findRelated($this, $attribute, $params);
        }

        return null;
    }

    /**
     * Set a value in the container.
     *
     * @param mixed $value
     */
    protected function setInContainer(string $attribute, $value): void
    {
        $this->valuesContainer[$attribute] = $value;
    }

    /**
     * whether an attribute is set in the container.
     */
    protected function hasInContainer(string $attribute): bool
    {
        return array_key_exists($attribute, $this->valuesContainer);
    }

    /**
     * Get a value from the container.
     *
     * @return mixed
     */
    protected function getFromContainer(string $attribute)
    {
        if (!$this->hasInContainer($attribute)) {
            return null;
        }

        $value = $this->valuesContainer[$attribute] ?? null;

        if ($value === null) {
            return $value;
        }

        $type = $this->getAttributeType($attribute);

        if ($type === self::JSON_ARRAY) {
            return $this->cloneArray($value);
        }

        if ($type === self::JSON_OBJECT) {
            return $this->cloneObject($value);
        }

        return $value;
    }

    /**
     * whether an attribute is set in the fetched-container.
     */
    protected function hasInFetchedContainer(string $attribute): bool
    {
        return array_key_exists($attribute, $this->fetchedValuesContainer);
    }

    /**
     * Get a value from the fetched-container.
     *
     * @return mixed
     */
    protected function getFromFetchedContainer(string $attribute)
    {
        if (!$this->hasInFetchedContainer($attribute)) {
            return null;
        }

        $value = $this->fetchedValuesContainer[$attribute] ?? null;

        if ($value === null) {
            return $value;
        }

        $type = $this->getAttributeType($attribute);

        if ($type === self::JSON_ARRAY) {
            return $this->cloneArray($value);
        }

        if ($type === self::JSON_OBJECT) {
            return $this->cloneObject($value);
        }

        return $value;
    }

    /**
     * Whether an attribute value is set.
     */
    public function has(string $attribute): bool
    {
        if ($attribute == 'id') {
            return (bool) $this->id;
        }

        $method = '_has' . ucfirst($attribute);

        if (method_exists($this, $method)) {
            return (bool) $this->$method();
        }

        if (array_key_exists($attribute, $this->valuesContainer)) {
            return true;
        }

        return false;
    }

    /**
     * Whether a value object for a field can be gotten.
     */
    public function isValueObjectGettable(string $field): bool
    {
        if (!$this->valueAccessor) {
            throw new RuntimeException("No ValueAccessor.");
        }

        return $this->valueAccessor->isGettable($field);
    }

    /**
     * Get a value object for a field. NULL can be returned.
     */
    public function getValueObject(string $field): ?object
    {
        if (!$this->valueAccessor) {
            throw new RuntimeException("No ValueAccessor.");
        }

        return $this->valueAccessor->get($field);
    }

    /**
     * Set a value object for a field. NULL can be set.
     *
     * @throws RuntimeException
     */
    public function setValueObject(string $field, ?object $value): void
    {
        if (!$this->valueAccessor) {
            throw new RuntimeException("No ValueAccessor.");
        }

        $this->valueAccessor->set($field, $value);
    }

    /**
     * @deprecated
     * @todo Make protected.
     */
    public function populateFromArray(array $data, bool $onlyAccessible = true, bool $reset = false): void
    {
        if ($reset) {
            $this->reset();
        }

        foreach ($this->getAttributeList() as $attribute) {
            if (!array_key_exists($attribute, $data)) {
                continue;
            }

            if ($attribute == 'id') {
                $this->id = $data[$attribute];

                continue;
            }

            if ($onlyAccessible && $this->getAttributeParam($attribute, 'notAccessible')) {
                continue;
            }

            $value = $data[$attribute];

            $this->populateFromArrayItem($attribute, $value);
        }
    }

    protected function populateFromArrayItem(string $attribute, $value): void
    {
        $preparedValue = $this->prepareAttributeValue($attribute, $value);

        $method = '_set' . ucfirst($attribute);

        if (method_exists($this, $method)) {
            $this->$method($preparedValue);

            return;
        }

        $this->setInContainer($attribute, $preparedValue);
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    protected function prepareAttributeValue(string $attribute, $value)
    {
        if (is_null($value)) {
            return $value;
        }

        $attributeType = $this->getAttributeType($attribute);

        if ($attributeType === self::FOREIGN) {
            $attributeType = $this->getForeignAttributeType($attribute) ?? $attributeType;
        }

        switch ($attributeType) {
            case self::VARCHAR:
                return $value;

            case self::BOOL:
                return ($value === 1 || $value === '1' || $value === true || $value === 'true');

            case self::INT:
                return intval($value);

            case self::FLOAT:
                return floatval($value);

            case self::JSON_ARRAY:
                return $this->prepareArrayAttributeValue($value);

            case self::JSON_OBJECT:
                return $this->prepareObjectAttributeValue($value);

            default:
                break;
        }

        return $value;
    }

    private function prepareArrayAttributeValue($value): ?array
    {
        if (is_string($value)) {
            $preparedValue = json_decode($value);

            if (!is_array($preparedValue)) {
                return null;
            }

            return $preparedValue;
        }

        if (!is_array($value)) {
            return null;
        }

        return $this->cloneArray($value);
    }

    private function prepareObjectAttributeValue($value): ?stdClass
    {
        if (is_string($value)) {
            $preparedValue = json_decode($value);

            if (!$preparedValue instanceof stdClass) {
                return null;
            }

            return $preparedValue;
        }

        $preparedValue = $value;

        if (is_array($value)) {
            $preparedValue = json_decode(json_encode($value));

            if ($preparedValue instanceof stdClass) {
                return $preparedValue;
            }
        }

        if (!$preparedValue instanceof stdClass) {
            return null;
        }

        return $this->cloneObject($preparedValue);
    }

    private function getForeignAttributeType(string $attribute): ?string
    {
        if (!$this->entityManager) {
            return null;
        }

        $defs = $this->entityManager->getDefs();

        $entityDefs = $defs->getEntity($this->entityType);

        // This should not be removed for compatibility reasons.
        if (!$entityDefs->hasAttribute($attribute)) {
            return null;
        }

        $relation = $entityDefs->getAttribute($attribute)->getParam('relation');
        $foreign = $entityDefs->getAttribute($attribute)->getParam('foreign');

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

    /**
     * Whether an entity is new.
     */
    public function isNew(): bool
    {
        return !$this->isNotNew;
    }

    /**
     * Set as not new. Meaning the entity is fetched or already saved.
     */
    public function setAsNotNew(): void
    {
        $this->isNotNew = true;
    }

    /**
     * Whether an entity has been saved. An entity can be already saved but not yet set as not-new.
     * To prevent inserting second time if save is called in an after-save hook.
     */
    public function isSaved(): bool
    {
        return $this->isSaved;
    }

    /**
     * Set as saved.
     */
    public function setAsSaved(): void
    {
        $this->isSaved = true;
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
    public function getEntityType(): string
    {
        return $this->entityType;
    }

    /**
     * @deprecated
     */
    public function hasField($name)
    {
        return $this->hasAttribute($name);
    }

    /**
     * Whether an entity type has an attribute defined.
     */
    public function hasAttribute(string $attibute): bool
    {
        return isset($this->attributes[$attibute]);
    }

    /**
     * Whether an entity type has a relation defined.
     */
    public function hasRelation(string $relation): bool
    {
        return isset($this->relations[$relation]);
    }

    /**
     * Get attribute list defined for an entity type.
     */
    public function getAttributeList(): array
    {
        return array_keys($this->getAttributes());
    }

    /**
     * Get relation list defined for an entity type.
     */
    public function getRelationList(): array
    {
        return array_keys($this->getRelations());
    }

    /**
     * @deprecated Use `getValueMap`.
     */
    public function getValues()
    {
        return $this->toArray();
    }

    /**
     * @deprecated Use `getValueMap`.
     * @todo Make protected.
     */
    public function toArray()
    {
        $arr = [];

        if (isset($this->id)) {
            $arr['id'] = $this->id;
        }

        foreach ($this->getAttributeList() as $attribute) {
            if ($attribute === 'id') {
                continue;
            }

            if ($this->has($attribute)) {
                $arr[$attribute] = $this->get($attribute);
            }
        }

        return $arr;
    }

    /**
     * Get values.
     */
    public function getValueMap(): stdClass
    {
        $array = $this->toArray();

        return (object) $array;
    }

    /**
     * @deprecated
     */
    public function getFields()
    {
        return $this->getAttributes();
    }

    /**
     * @deprecated
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * @deprecated
     */
    public function getRelations()
    {
        return $this->relations;
    }

    /**
     * Get an attribute type.
     */
    public function getAttributeType(string $attribute): ?string
    {
        if (!isset($this->attributes[$attribute])) {
            return null;
        }

        return $this->attributes[$attribute]['type'] ?? null;
    }

    /**
     * Get a relation type.
     */
    public function getRelationType(string $relation): ?string
    {
        if (!isset($this->relations[$relation])) {
            return null;
        }

        return $this->relations[$relation]['type'] ?? null;
    }

    /**
     * Get an attribute parameter.
     *
     * @return mixed
     */
    public function getAttributeParam(string $attribute, string $name)
    {
        if (!isset($this->attributes[$attribute])) {
            return null;
        }

        return $this->attributes[$attribute][$name] ?? null;
    }

    /**
     * Get a relation parameter.
     *
     * @return mixed
     */
    public function getRelationParam(string $relation, string $name)
    {
        if (!isset($this->relations[$relation])) {
            return null;
        }

        return $this->relations[$relation][$name] ?? null;
    }

    /**
     * Whether is fetched from DB.
     */
    public function isFetched(): bool
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
    public function isAttributeChanged(string $name): bool
    {
        if (!$this->has($name)) {
            return false;
        }

        if (!$this->hasFetched($name)) {
            return true;
        }

        return !self::areValuesEqual(
            $this->getAttributeType($name),
            $this->get($name),
            $this->getFetched($name),
            $this->getAttributeParam($name, 'isUnordered') ?? false
        );
    }

    protected static function areValuesEqual(string $type, $v1, $v2, bool $isUnordered = false): bool
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
        }
        else if ($type === self::JSON_OBJECT) {
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
    public function setFetched(string $attribute, $value): void
    {
        $preparedValue = $this->prepareAttributeValue($attribute, $value);

        $this->fetchedValuesContainer[$attribute] = $preparedValue;
    }

    /**
     * Get a fetched value of a specific attribute.
     *
     * @return mixed
     */
    public function getFetched(string $attribute)
    {
        if ($attribute === 'id') {
            return $this->id;
        }

        if ($this->hasInFetchedContainer($attribute)) {
            return $this->getFromFetchedContainer($attribute);
        }

        return null;
    }

    /**
     * Whether a fetched value is set for a specific attribute.
     */
    public function hasFetched(string $attribute): bool
    {
        if ($attribute === 'id') {
            return !is_null($this->id);
        }

        return $this->hasInFetchedContainer($attribute);
    }

    /**
     * Clear all set fetched values.
     */
    public function resetFetchedValues(): void
    {
        $this->fetchedValuesContainer = [];
    }

    /**
     * Copy all current values to fetched values. All current attribute values will beset as those
     * that are fetched from DB.
     */
    public function updateFetchedValues(): void
    {
        $this->fetchedValuesContainer = $this->valuesContainer;

        foreach ($this->fetchedValuesContainer as $attribute => $value) {
            $this->setFetched($attribute, $value);
        }
    }

    /**
     * Set an entity as fetched. All current attribute values will be set as those that are fetched
     * from DB.
     */
    public function setAsFetched(): void
    {
        $this->isFetched = true;

        $this->setAsNotNew();

        $this->updateFetchedValues();
    }

    /**
     * Whether an entity is being saved.
     */
    public function isBeingSaved(): bool
    {
        return $this->isBeingSaved;
    }

    public function setAsBeingSaved(): void
    {
        $this->isBeingSaved = true;
    }

    public function setAsNotBeingSaved(): void
    {
        $this->isBeingSaved = false;
    }

    /**
     * Set defined default values.
     */
    public function populateDefaults(): void
    {
        foreach ($this->attributes as $attribute => $defs) {
            if (!array_key_exists('default', $defs)) {
                continue;
            }

            $this->setInContainer($attribute, $defs['default']);
        }
    }

    /**
     * @deprecated Use `$this->entityManager`.
     */
    protected function getEntityManager(): EntityManager
    {
        return $this->entityManager;
    }

    /**
     * Clone an array value.
     */
    protected function cloneArray(?array $value): ?array
    {
        if ($value === null) {
            return null;
        }

        $toClone = false;

        foreach ($value as $item) {
            if (is_object($item) || is_array($item)) {
                $toClone = true;

                break;
            }
        }

        if (!$toClone) {
            return $value;
        }

        $copy = [];

        foreach ($value as $i => $item) {
            if (is_object($item)) {
                $copy[$i] = $this->cloneObject($item);

                continue;
            }

            if (is_array($item)) {
                $copy[$i] = $this->cloneArray($item);

                continue;
            }

            $copy[$i] = $item;
        }

        return $copy;
    }

    /**
     * Clone an object value.
     */
    protected function cloneObject(?stdClass $value): ?stdClass
    {
        if ($value === null) {
            return null;
        }

        $copy = (object) [];

        foreach (get_object_vars($value) as $k => $item) {
            $key = $k;

            if (!is_string($key)) {
                $key = strval($key);
            }

            if (is_object($item)) {
                $copy->$key = $this->cloneObject($item);

                continue;
            }

            if (is_array($item)) {
                $copy->$key = $this->cloneArray($item);

                continue;
            }

            $copy->$key = $item;
        }

        return $copy;
    }
}
