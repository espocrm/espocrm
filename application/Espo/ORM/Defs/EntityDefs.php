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

namespace Espo\ORM\Defs;

use RuntimeException;

class EntityDefs
{
    private $data;

    private $name;

    private $attributeCache = [];

    private $relationCache = [];

    private $indexCache = [];

    private $fieldCache = [];

    private function __construct()
    {
    }

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
        return array_keys($this->data['attributes'] ?? $this->data['fields'] ?? []);
    }

    /**
     * Get a relation name list.
     *
     * @return string[]
     */
    public function getRelationNameList(): array
    {
        return array_keys($this->data['relations'] ?? []);
    }

    /**
     * Get an index name list.
     *
     * @return string[]
     */
    public function getIndexNameList(): array
    {
        return array_keys($this->data['indexes'] ?? []);
    }

    /**
     * Get a field name list.
     *
     * @return string[]
     */
    public function getFieldNameList(): array
    {
        return array_keys($this->data['vFields'] ?? []);
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
     * Whether has an attribute.
     */
    public function hasAttribute(string $name): bool
    {
        $this->cacheAttribute($name);

        return !is_null($this->attributeCache[$name]);
    }

    /**
     * Whether has a relation.
     */
    public function hasRelation(string $name): bool
    {
        $this->cacheRelation($name);

        return !is_null($this->relationCache[$name]);
    }

    /**
     * Whether has an index.
     */
    public function hasIndex(string $name): bool
    {
        $this->cacheIndex($name);

        return !is_null($this->indexCache[$name]);
    }

    /**
     * Whether has a field.
     */
    public function hasField(string $name): bool
    {
        $this->cacheField($name);

        return !is_null($this->fieldCache[$name]);
    }

    /**
     * Get an attribute definitions.
     * @throws RuntimeException
     */
    public function getAttribute(string $name): AttributeDefs
    {
        $this->cacheAttribute($name);

        if (!$this->hasAttribute($name)) {
            throw new RuntimeException("Attribute '{$name}' does not exist.");
        }

        return $this->attributeCache[$name];
    }

    /**
     * Get a relation definitions.
     * @throws RuntimeException
     */
    public function getRelation(string $name): RelationDefs
    {
        $this->cacheRelation($name);

        if (!$this->hasRelation($name)) {
            throw new RuntimeException("Relation '{$name}' does not exist.");
        }

        return $this->relationCache[$name];
    }

    /**
     * Get an index definitions.
     * @throws RuntimeException
     */
    public function getIndex(string $name): IndexDefs
    {
        $this->cacheIndex($name);

        if (!$this->hasIndex($name)) {
            throw new RuntimeException("Index '{$name}' does not exist.");
        }

        return $this->indexCache[$name];
    }

    /**
     * Get a field definitions.
     * @throws RuntimeException
     */
    public function getField(string $name): FieldDefs
    {
        $this->cacheField($name);

        if (!$this->hasField($name)) {
            throw new RuntimeException("Field '{$name}' does not exist.");
        }

        return $this->fieldCache[$name];
    }

    /**
     * Whether a parameter is set.
     */
    public function hasParam(string $name): bool
    {
        return array_key_exists($name, $this->data);
    }

    /*
     * Get a parameter value by a name.
     *
     * @return mixed
     */
    public function getParam(string $name)
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
        $raw = $this->data['attributes'][$name] ?? $this->data['fields'][$name] ?? null;

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
        $raw = $this->data['relations'][$name] ?? null;

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
        $raw = $this->data['indexes'][$name] ?? null;

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
        $raw = $this->data['vFields'][$name] ?? /*$this->data['fields'][$name] ??*/ null;

        if (!$raw) {
            return null;
        }

        return FieldDefs::fromRaw($raw, $name);
    }
}
