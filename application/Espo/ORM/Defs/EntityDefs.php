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

namespace Espo\ORM\Defs;

use Espo\ORM\Defs\Params\EntityParam;
use RuntimeException;

class EntityDefs
{
    /** @var array<string, array<string, mixed>|mixed> */
    private array $data;
    private string $name;
    /** @var array<string, ?AttributeDefs> */
    private $attributeCache = [];
    /** @var array<string, ?RelationDefs> */
    private $relationCache = [];
    /** @var array<string, ?IndexDefs> */
    private $indexCache = [];
    /** @var array<string, ?FieldDefs> */
    private $fieldCache = [];

    private function __construct()
    {}

    /**
     * @param array<string, mixed> $raw
     */
    public static function fromRaw(array $raw, string $name): self
    {
        $obj = new self();
        $obj->data = $raw;
        $obj->name = $name;

        return $obj;
    }

    /**
     * Get an entity name (entity type).
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get an attribute name list.
     *
     * @return string[]
     */
    public function getAttributeNameList(): array
    {
        /** @var string[] */
        return array_keys($this->data[EntityParam::ATTRIBUTES] ?? []);
    }

    /**
     * Get a relation name list.
     *
     * @return string[]
     */
    public function getRelationNameList(): array
    {
        /** @var string[] */
        return array_keys($this->data[EntityParam::RELATIONS] ?? []);
    }

    /**
     * Get an index name list.
     *
     * @return string[]
     */
    public function getIndexNameList(): array
    {
        /** @var string[] */
        return array_keys($this->data[EntityParam::INDEXES] ?? []);
    }

    /**
     * Get a field name list.
     *
     * @return string[]
     */
    public function getFieldNameList(): array
    {
        /** @var string[] */
        return array_keys($this->data[EntityParam::FIELDS] ?? []);
    }

    /**
     * Get an attribute definitions list.
     *
     * @return AttributeDefs[]
     */
    public function getAttributeList(): array
    {
        $list = [];

        foreach ($this->getAttributeNameList() as $name) {
            $list[] = $this->getAttribute($name);
        }

        return $list;
    }

    /**
     * Get a relation definitions list.
     *
     * @return RelationDefs[]
     */
    public function getRelationList(): array
    {
        $list = [];

        foreach ($this->getRelationNameList() as $name) {
            $list[] = $this->getRelation($name);
        }

        return $list;
    }

    /**
     * Get an index definitions list.
     *
     * @return IndexDefs[]
     */
    public function getIndexList(): array
    {
        $list = [];

        foreach ($this->getIndexNameList() as $name) {
            $list[] = $this->getIndex($name);
        }

        return $list;
    }

    /**
     * Get a field definitions list.
     *
     * @return FieldDefs[]
     */
    public function getFieldList(): array
    {
        $list = [];

        foreach ($this->getFieldNameList() as $name) {
            $list[] = $this->getField($name);
        }

        return $list;
    }

    /**
     * Has an attribute.
     */
    public function hasAttribute(string $name): bool
    {
        $this->cacheAttribute($name);

        return !is_null($this->attributeCache[$name]);
    }

    /**
     * Has a relation.
     */
    public function hasRelation(string $name): bool
    {
        $this->cacheRelation($name);

        return !is_null($this->relationCache[$name]);
    }

    /**
     * Has an index.
     */
    public function hasIndex(string $name): bool
    {
        $this->cacheIndex($name);

        return !is_null($this->indexCache[$name]);
    }

    /**
     * Has a field.
     */
    public function hasField(string $name): bool
    {
        $this->cacheField($name);

        return !is_null($this->fieldCache[$name]);
    }

    /**
     * Get attribute definitions.
     *
     * @throws RuntimeException
     */
    public function getAttribute(string $name): AttributeDefs
    {
        $this->cacheAttribute($name);

        if (!$this->hasAttribute($name)) {
            throw new RuntimeException("Attribute '{$name}' does not exist.");
        }

        /** @var AttributeDefs */
        return $this->attributeCache[$name];
    }

    /**
     * Get relation definitions.
     *
     * @throws RuntimeException
     */
    public function getRelation(string $name): RelationDefs
    {
        $this->cacheRelation($name);

        if (!$this->hasRelation($name)) {
            throw new RuntimeException("Relation '{$name}' does not exist.");
        }

        /** @var RelationDefs */
        return $this->relationCache[$name];
    }

    /**
     * Get index definitions.
     *
     * @throws RuntimeException
     */
    public function getIndex(string $name): IndexDefs
    {
        $this->cacheIndex($name);

        if (!$this->hasIndex($name)) {
            throw new RuntimeException("Index '{$name}' does not exist.");
        }

        /** @var IndexDefs */
        return $this->indexCache[$name];
    }

    /**
     * Get field definitions.
     *
     * @throws RuntimeException
     */
    public function getField(string $name): FieldDefs
    {
        $this->cacheField($name);

        if (!$this->hasField($name)) {
            throw new RuntimeException("Field '{$name}' does not exist.");
        }

        /** @var FieldDefs */
        return $this->fieldCache[$name];
    }

    /**
     * Try to get attribute definitions.
     */
    public function tryGetAttribute(string $name): ?AttributeDefs
    {
        if (!$this->hasAttribute($name)) {
            return null;
        }

        return $this->getAttribute($name);
    }

    /**
     * Try to get field definitions.
     */
    public function tryGetField(string $name): ?FieldDefs
    {
        if (!$this->hasField($name)) {
            return null;
        }

        return $this->getField($name);
    }

    /**
     * Try to get relation definitions.
     */
    public function tryGetRelation(string $name): ?RelationDefs
    {
        if (!$this->hasRelation($name)) {
            return null;
        }

        return $this->getRelation($name);
    }

    /**
     * Try to get index definitions.
     */
    public function tryGetIndex(string $name): ?IndexDefs
    {
        if (!$this->hasIndex($name)) {
            return null;
        }

        return $this->getIndex($name);
    }

    /**
     * Whether a parameter is set.
     */
    public function hasParam(string $name): bool
    {
        return array_key_exists($name, $this->data);
    }

    /**
     * Get a parameter value by a name.
     */
    public function getParam(string $name): mixed
    {
        return $this->data[$name] ?? null;
    }

    private function cacheAttribute(string $name): void
    {
        if (array_key_exists($name, $this->attributeCache)) {
            return;
        }

        $this->attributeCache[$name] = $this->loadAttribute($name);
    }

    private function loadAttribute(string $name): ?AttributeDefs
    {
        $raw = $this->data[EntityParam::ATTRIBUTES][$name] ?? null;

        if (!$raw) {
            return null;
        }

        return AttributeDefs::fromRaw($raw, $name);
    }

    private function cacheRelation(string $name): void
    {
        if (array_key_exists($name, $this->relationCache)) {
            return;
        }

        $this->relationCache[$name] = $this->loadRelation($name);
    }

    private function loadRelation(string $name): ?RelationDefs
    {
        $raw = $this->data[EntityParam::RELATIONS][$name] ?? null;

        if (!$raw) {
            return null;
        }

        return RelationDefs::fromRaw($raw, $name);
    }

    private function cacheIndex(string $name): void
    {
        if (array_key_exists($name, $this->indexCache)) {
            return;
        }

        $this->indexCache[$name] = $this->loadIndex($name);
    }

    private function loadIndex(string $name): ?IndexDefs
    {
        $raw = $this->data[EntityParam::INDEXES][$name] ?? null;

        if (!$raw) {
            return null;
        }

        return IndexDefs::fromRaw($raw, $name);
    }

    private function cacheField(string $name): void
    {
        if (array_key_exists($name, $this->fieldCache)) {
            return;
        }

        $this->fieldCache[$name] = $this->loadField($name);
    }

    private function loadField(string $name): ?FieldDefs
    {
        $raw = $this->data[EntityParam::FIELDS][$name] ?? null;

        if (!$raw) {
            return null;
        }

        return FieldDefs::fromRaw($raw, $name);
    }
}
