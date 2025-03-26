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

namespace Espo\ORM;

use Doctrine\DBAL\Types\Types;
use Espo\ORM\DataLoader\EmptyLoader;
use Espo\ORM\DataLoader\Loader;
use Espo\ORM\Defs\Params\AttributeParam;
use Espo\ORM\Defs\Params\EntityParam;
use Espo\ORM\Defs\Params\RelationParam;
use Espo\ORM\Name\Attribute;
use Espo\ORM\Relation\EmptyRelations;
use Espo\ORM\Relation\Relations;
use Espo\ORM\Type\AttributeType;
use Espo\ORM\Type\RelationType;
use Espo\ORM\Value\ValueAccessorFactory;
use Espo\ORM\Value\ValueAccessor;

use stdClass;
use InvalidArgumentException;
use RuntimeException;

use const E_USER_DEPRECATED;
use const JSON_THROW_ON_ERROR;

class BaseEntity implements Entity
{
    /** @var string */
    protected $entityType;

    private bool $isNotNew = false;
    private bool $isSaved = false;
    private bool $isFetched = false;
    private bool $isBeingSaved = false;

    protected ?EntityManager $entityManager;
    private ?ValueAccessor $valueAccessor = null;
    readonly protected Relations $relations;
    readonly private Loader $loader;

    /** @var array<string, bool> */
    private array $writtenMap = [];
    /** @var array<string, array<string, mixed>> */
    private array $attributesDefs = [];
    /** @var array<string, array<string, mixed>> */
    private array $relationsDefs = [];
    /** @var array<string, mixed> */
    private array $fetchedValuesContainer = [];
    /** @var array<string, mixed> */
    private array $valuesContainer = [];

    private bool $isPartiallyLoaded = false;

    /**
     * An ID.
     */
    protected ?string $id = null;

    /**
     * @param array{
     *   attributes?: array<string, array<string, mixed>>,
     *   relations?: array<string, array<string, mixed>>,
     *   fields?: array<string, array<string, mixed>>
     * } $defs
     */
    public function __construct(
        string $entityType,
        array $defs,
        ?EntityManager $entityManager = null,
        ?ValueAccessorFactory $valueAccessorFactory = null,
        ?Relations $relations = null,
        ?Loader $loader = null,
    ) {
        $this->entityType = $entityType;
        $this->entityManager = $entityManager;

        $this->attributesDefs = $defs['attributes'] ?? $this->attributesDefs;
        $this->relationsDefs = $defs[EntityParam::RELATIONS] ?? $this->relationsDefs;

        if ($valueAccessorFactory) {
            $this->valueAccessor = $valueAccessorFactory->create($this);
        }

        $this->relations = $relations ?? new EmptyRelations();
        $this->loader = $loader ?? new EmptyLoader();
    }

    /**
     * Get an entity ID.
     */
    public function getId(): string
    {
        /** @var ?string $id */
        $id = $this->get(Attribute::ID);

        if ($id === null) {
            throw new RuntimeException("Entity ID is not set.");
        }

        if ($id === '') {
            throw new RuntimeException("Entity ID is empty.");
        }

        return $id;
    }

    public function hasId(): bool
    {
        return $this->id !== null;
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

        $this->relations->resetAll();
    }

    /**
     * Set an attribute value or multiple attribute values.
     *
     * Two usage options:
     * * `set(string $attribute, mixed $value)`
     * * `set(array|object $valueMap)`
     *
     * @param string|stdClass|array<string, mixed> $attribute
     * @param mixed $value
     */
    public function set($attribute, $value = null): static
    {
        $arg = $attribute;

        /**
         * @var mixed $arg
         */

        if (is_array($arg) || is_object($arg)) {
            if (is_object($arg)) {
                $arg = get_object_vars($arg);
            }

            $this->populateFromArray($arg, false);

            return $this;
        }

        if (is_string($arg)) {
            $name = $arg;

            if ($name == Attribute::ID) {
                $this->id = $value;
            }

            if (!$this->hasAttribute($name)) {
                return $this;
            }

            $method = '_set' . ucfirst($name);

            if (method_exists($this, $method)) {
                $this->$method($value);

                return $this;
            }

            $this->populateFromArray([
                $name => $value,
            ]);

            return $this;
        }

        throw new InvalidArgumentException();
    }

    /**
     * Set multiple attributes.
     *
     * @param array<string, mixed>|stdClass $valueMap Values.
     * @since v8.1.0.
     */
    public function setMultiple(array|stdClass $valueMap): static
    {
        return $this->set($valueMap);
    }

    /**
     * Get an attribute value.
     *
     * @retrun mixed
     */
    public function get(string $attribute): mixed
    {
        if ($attribute === Attribute::ID) {
            return $this->id;
        }

        // Legacy.
        $method = '_get' . ucfirst($attribute);

        if (method_exists($this, $method)) {
            if ($this->isPartiallyLoaded) {
                $this->loadPartiallyLoaded();
            }

            return $this->$method();
        }

        if ($this->checkAttributeToFullyLoad($attribute)) {
            $this->loadPartiallyLoaded();
        }

        if ($this->hasAttribute($attribute) && $this->hasInContainer($attribute)) {
            return $this->getFromContainer($attribute);
        }

        // @todo Remove support in v10.0.
        if ($this->hasRelation($attribute) && $this->id) {
            trigger_error(
                "Accessing related records with Entity::get is deprecated. " .
                "Use `\$entityManager->getRelation(...)->find()`.",
                E_USER_DEPRECATED
            );

            $isMany = in_array($this->getRelationType($attribute), [
                RelationType::MANY_MANY,
                RelationType::HAS_MANY,
                RelationType::HAS_CHILDREN,
            ]);

            return $isMany ?
                $this->relations->getMany($attribute) :
                $this->relations->getOne($attribute);
        }

        return null;
    }

    /**
     * Set a value in the container. To be used wisely. Use `set` instead.
     */
    protected function setInContainer(string $attribute, mixed $value): void
    {
        $this->valuesContainer[$attribute] = $value;

        $this->writtenMap[$attribute] = true;
    }

    /**
     * Not to be used. To be used internally for lazy-loading purpose.
     *
     * @internal
     * @since 9.1.0
     */
    protected function setInContainerNotWritten(string $attribute, mixed $value): void
    {
        $this->valuesContainer[$attribute] = $value;

        unset($this->writtenMap[$attribute]);
    }

    /**
     * Whether an attribute is set in the container.
     */
    protected function hasInContainer(string $attribute): bool
    {
        return array_key_exists($attribute, $this->valuesContainer);
    }

    /**
     * Get a value from the container.
     */
    protected function getFromContainer(string $attribute): mixed
    {
        if (!$this->hasInContainer($attribute)) {
            return null;
        }

        $value = $this->valuesContainer[$attribute] ?? null;

        if ($value === null) {
            return null;
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
     */
    protected function getFromFetchedContainer(string $attribute): mixed
    {
        if (!$this->hasInFetchedContainer($attribute)) {
            return null;
        }

        $value = $this->fetchedValuesContainer[$attribute] ?? null;

        if ($value === null) {
            return null;
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
        if ($attribute == Attribute::ID) {
            return (bool) $this->id;
        }

        // Legacy.
        $method = '_has' . ucfirst($attribute);

        if (method_exists($this, $method)) {
            if ($this->isPartiallyLoaded) {
                $this->loadPartiallyLoaded();
            }

            return (bool) $this->$method();
        }

        if ($this->checkAttributeToFullyLoad($attribute)) {
            $this->loadPartiallyLoaded();
        }

        return $this->hasInContainer($attribute);
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
    public function setValueObject(string $field, ?object $value): static
    {
        if (!$this->valueAccessor) {
            throw new RuntimeException("No ValueAccessor.");
        }

        $this->valueAccessor->set($field, $value);

        return $this;
    }

    private function populateFromArrayItem(string $attribute, mixed $value): void
    {
        $preparedValue = $this->prepareAttributeValue($attribute, $value);

        // Legacy.
        $method = '_set' . ucfirst($attribute);

        if (method_exists($this, $method)) {
            $this->$method($preparedValue);

            return;
        }

        $this->setInContainer($attribute, $preparedValue);

        $type = $this->getAttributeType($attribute);

        if (
            $type === AttributeType::FOREIGN_ID ||
            $type === AttributeType::FOREIGN &&
            $this->isAttributeHasOneForeignId($attribute)
            // @todo Move the logic for hasOne to Espo\Core\ORM\Entity ?
        ) {
            $this->relations->reset(substr($attribute, 0, -2));
        } else if ($type === AttributeType::FOREIGN_TYPE) {
            $this->relations->reset(substr($attribute, 0, -4));
        }
    }

    private function isAttributeHasOneForeignId(string $attribute): bool
    {
        $type = $this->getAttributeType($attribute);

        return $type === AttributeType::FOREIGN &&
            $this->getAttributeParam($attribute, AttributeParam::RELATION) === substr($attribute, 0, -2) &&
            $this->getAttributeParam($attribute, AttributeParam::FOREIGN) === Attribute::ID &&
            str_ends_with($attribute, 'Id');
    }

    protected function prepareAttributeValue(string $attribute, mixed $value): mixed
    {
        if (is_null($value)) {
            return null;
        }

        $attributeType = $this->getAttributeType($attribute);

        if (
            $attributeType === self::VARCHAR &&
            $this->getAttributeParam($attribute, AttributeParam::DB_TYPE) === Types::DECIMAL
        ) {
            return $this->prepareAttributeValueDecimal($attribute, $value);
        }

        if ($attributeType === self::FOREIGN) {
            $attributeType = $this->getForeignAttributeType($attribute) ?? $attributeType;
        }

        switch ($attributeType) {
            case self::VARCHAR:
            case self::TEXT:
                if (is_object($value)) {
                    // Prevents an error.
                    // @todo Remove in v10.0.
                    return 'Object';
                }

                if (is_array($value)) {
                    // Prevents an error.
                    // @todo Remove in v10.0.
                    return 'Array';
                }

                return strval($value);

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

    /**
     * @param mixed $value
     * @return mixed[]|null
     */
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

    /**
     * @param mixed $value
     */
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
            $preparedValue = json_decode(json_encode($value, JSON_THROW_ON_ERROR));

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

        $relation = $entityDefs->getAttribute($attribute)->getParam(AttributeParam::RELATION);
        $foreign = $entityDefs->getAttribute($attribute)->getParam(AttributeParam::FOREIGN);

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
     * Get an entity type.
     */
    public final function getEntityType(): string
    {
        return $this->entityType;
    }

    /**
     * Whether an entity type has an attribute defined.
     */
    public function hasAttribute(string $attribute): bool
    {
        return isset($this->attributesDefs[$attribute]);
    }

    /**
     * Whether an entity type has a relation defined.
     */
    public function hasRelation(string $relation): bool
    {
        return isset($this->relationsDefs[$relation]);
    }

    /**
     * Get attribute list defined for an entity type.
     */
    public function getAttributeList(): array
    {
        return array_keys($this->attributesDefs);
    }

    /**
     * Get relation list defined for an entity type.
     */
    public function getRelationList(): array
    {
        return array_keys($this->relationsDefs);
    }

    /**
     * Get values.
     */
    public function getValueMap(): stdClass
    {
        $map = [];

        if (isset($this->id)) {
            $map[Attribute::ID] = $this->id;
        }

        foreach ($this->getAttributeList() as $attribute) {
            if ($attribute === Attribute::ID) {
                continue;
            }

            if ($this->has($attribute)) {
                $map[$attribute] = $this->get($attribute);
            }
        }

        return (object) $map;
    }

    /**
     * Get an attribute type.
     */
    public function getAttributeType(string $attribute): ?string
    {
        if (!isset($this->attributesDefs[$attribute])) {
            return null;
        }

        return $this->attributesDefs[$attribute][AttributeParam::TYPE] ?? null;
    }

    /**
     * Get a relation type.
     */
    public function getRelationType(string $relation): ?string
    {
        if (!isset($this->relationsDefs[$relation])) {
            return null;
        }

        return $this->relationsDefs[$relation][RelationParam::TYPE] ?? null;
    }

    /**
     * Get an attribute parameter.
     *
     * @return mixed
     */
    public function getAttributeParam(string $attribute, string $name)
    {
        if (!isset($this->attributesDefs[$attribute])) {
            return null;
        }

        return $this->attributesDefs[$attribute][$name] ?? null;
    }

    /**
     * Get a relation parameter.
     *
     * @return mixed
     */
    public function getRelationParam(string $relation, string $name)
    {
        if (!isset($this->relationsDefs[$relation])) {
            return null;
        }

        return $this->relationsDefs[$relation][$name] ?? null;
    }

    /**
     * Whether is fetched from DB.
     */
    public function isFetched(): bool
    {
        return $this->isFetched;
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

        /** @var string $type */
        $type = $this->getAttributeType($name);

        return !self::areValuesEqual(
            $type,
            $this->get($name),
            $this->getFetched($name),
            $this->getAttributeParam($name, 'isUnordered') ?? false
        );
    }

    /**
     * Whether an attribute was written (since syncing with DB) regardless being changed.
     */
    public function isAttributeWritten(string $name): bool
    {
        return $this->writtenMap[$name] ?? false;
    }

    /**
     * @param mixed $v1
     * @param mixed $v2
     */
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
                    if (is_object($itemValue) && is_object($v2[$i])) {
                        if (!self::areValuesEqual(self::JSON_OBJECT, $itemValue, $v2[$i])) {
                            return false;
                        }

                        continue;
                    }

                    if ($itemValue !== $v2[$i]) {
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

                foreach (get_object_vars($v1) as $key => $itemValue) {
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
    public function setFetched(string $attribute, $value): static
    {
        $preparedValue = $this->prepareAttributeValue($attribute, $value);

        $this->fetchedValuesContainer[$attribute] = $preparedValue;

        return $this;
    }

    /**
     * Get a fetched value of a specific attribute.
     *
     * @return mixed
     */
    public function getFetched(string $attribute)
    {
        if ($attribute === Attribute::ID) {
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
        if ($attribute === Attribute::ID) {
            return !is_null($this->id);
        }

        if ($this->checkAttributeFetchedToFullyLoad($attribute)) {
            $this->loadPartiallyLoaded();
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
     * Copy all current values to fetched values. All current attribute values will be set as those
     * that are fetched from DB.
     */
    public function updateFetchedValues(): void
    {
        $this->fetchedValuesContainer = $this->valuesContainer;

        foreach ($this->fetchedValuesContainer as $attribute => $value) {
            $this->setFetched($attribute, $value);
        }

        $this->writtenMap = [];
    }

    /**
     * Set as partially loaded. For internal use.
     *
     * @internal
     * @since 9.0.0
     */
    public function setAsPartiallyLoaded(): void
    {
        $this->isPartiallyLoaded = true;
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
        foreach ($this->attributesDefs as $attribute => $defs) {
            if (!array_key_exists(AttributeParam::DEFAULT, $defs)) {
                continue;
            }

            $wasSet = $this->hasInContainer($attribute);

            $this->setInContainer($attribute, $defs[AttributeParam::DEFAULT]);

            $this->writtenMap[$attribute] = $wasSet;
        }
    }

    /**
     * Clone an array value.
     *
     * @param mixed[]|null $value
     * @return mixed[]
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

        /** @var array<int, stdClass|mixed[]|scalar|null> $value */

        foreach ($value as $i => $item) {
            if (is_object($item)) {
                $copy[$i] = $this->cloneObject($item);

                continue;
            }

            if (is_array($item)) {
                if (!array_is_list($item)) {
                    $copy[$i] = $this->cloneObject((object) $item);

                    continue;
                }

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

        foreach (get_object_vars($value) as $key => $item) {
            /** @var stdClass|mixed[]|scalar|null $item */

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

    /**
     * @param array<string, mixed> $data
     */
    protected function populateFromArray(array $data, bool $onlyAccessible = true, bool $reset = false): void
    {
        if ($reset) {
            $this->reset();
        }

        foreach ($this->getAttributeList() as $attribute) {
            if (!array_key_exists($attribute, $data)) {
                continue;
            }

            if ($attribute === Attribute::ID) {
                $this->id = $data[$attribute];

                continue;
            }

            // @todo Revise. To remove?
            if ($onlyAccessible && $this->getAttributeParam($attribute, 'notAccessible')) {
                continue;
            }

            $value = $data[$attribute];

            $this->populateFromArrayItem($attribute, $value);
        }
    }

    private function checkAttributeToFullyLoad(string $attribute): bool
    {
        return
            !$this->isNew() &&
            $this->isPartiallyLoaded &&
            $this->hasAttribute($attribute) &&
            !$this->hasInContainer($attribute);
    }

    private function checkAttributeFetchedToFullyLoad(string $attribute): bool
    {
        return
            !$this->isNew() &&
            $this->isPartiallyLoaded &&
            $this->hasAttribute($attribute) &&
            !$this->hasInFetchedContainer($attribute);
    }

    private function loadPartiallyLoaded(): void
    {
        $this->isPartiallyLoaded = false;

        $this->loader->load($this);
    }

    private function prepareAttributeValueDecimal(string $attribute, mixed $value): ?string
    {
        if (!is_scalar($value) || !is_numeric($value)) {
            return null;
        }

        $scale = $this->getAttributeParam($attribute, AttributeParam::SCALE);

        $value = strval($value);

        $parts = explode('.', $value, 2);

        $left = $parts[0];
        $right = $parts[1] ?? '';

        if (strlen($right) < $scale) {
            $value = $left . '.' . str_pad($right, $scale, '0');
        }

        return $value;
    }
}
