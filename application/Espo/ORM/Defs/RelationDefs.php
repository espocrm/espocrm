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

use Espo\ORM\Entity;

use RuntimeException;

/**
 * Relation definitions.
 */
class RelationDefs
{
    private $data;

    private $name;

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
     * Get a name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get a type.
     */
    public function getType(): string
    {
        $type = $this->data['type'] ?? null;

        if ($type === null) {
            throw new RuntimeException("Relation '{$this->name}' has no type.");
        }

        return $type;
    }

    /**
     * Whether is Many-to-Many.
     */
    public function isManyToMany(): bool
    {
        return $this->getType() === Entity::MANY_MANY;
    }

    /**
     * Whether is Has-Many (One-to-Many).
     */
    public function isHasMany(): bool
    {
        return $this->getType() === Entity::HAS_MANY;
    }

    /**
     * Whether is Has-One (Many-to-One or One-to-One).
     */
    public function isHasOne(): bool
    {
        return $this->getType() === Entity::HAS_ONE;
    }

    /**
     * Whether is Has-Children (Parent-to-Children).
     */
    public function isHasChildren(): bool
    {
        return $this->getType() === Entity::HAS_CHILDREN;
    }

    /**
     * Whether is Belongs-to (Many-to-One).
     */
    public function isBelongsTo(): bool
    {
        return $this->getType() === Entity::BELONGS_TO;
    }

    /**
     * Whether is Belongs-to-Parent (Children-to-Parent).
     */
    public function isBelongsToParent(): bool
    {
        return $this->getType() === Entity::BELONGS_TO_PARENT;
    }

    /**
     * Whether has a foreign entity type is defined.
     */
    public function hasForeignEntityType(): bool
    {
        return isset($this->data['entity']);
    }

    /**
     * Get a foreign entity type.
     * @throws RuntimeException
     */
    public function getForeignEntityType(): string
    {
        if (!$this->hasForeignEntityType()) {
            throw new RuntimeException(
                "No 'entity' paramater defined in the relation '{$this->name}'."
            );
        }

        return $this->data['entity'];
    }

    /**
     * Whether has a foreign relation name.
     */
    public function hasForeignRelationName(): bool
    {
        return isset($this->data['foreign']);
    }

    /**
     * Get a foreign relation name.
     * @throws RuntimeException
     */
    public function getForeignRelationName(): string
    {
        if (!$this->hasForeignRelationName()) {
            throw new RuntimeException(
                "No 'foreign' paramater defined in the relation '{$this->name}'."
            );
        }

        return $this->data['foreign'];
    }

    /**
     * Whether a foreign key is defined.
     */
    public function hasForeignKey(): bool
    {
        return isset($this->data['foreignKey']);
    }

    /**
     * Get a foreign key.
     * @throws RuntimeException
     */
    public function getForeignKey(): string
    {
        if (!$this->hasForeignKey()) {
            throw new RuntimeException(
                "No 'foreignKey' paramater defined in the relation '{$this->name}'."
            );
        }

        return $this->data['foreignKey'];
    }

    /**
     * Whether a key is defined.
     */
    public function hasKey(): bool
    {
        return isset($this->data['key']);
    }

    /**
     * Get a key.
     * @throws RuntimeException
     */
    public function getKey(): string
    {
        if (!$this->hasKey()) {
            throw new RuntimeException(
                "No 'key' paramater defined in the relation '{$this->name}'."
            );
        }

        return $this->data['key'];
    }

    /**
     * Whether a mid key is defined. For Many-to-Many relationships only.
     */
    public function hasMidKey(): bool
    {
        return !is_null($this->data['midKeys'][0] ?? null);
    }

    /**
     * Get a mid key. For Many-to-Many relationships only.
     * @throws RuntimeException
     */
    public function getMidKey(): string
    {
        if (!$this->hasMidKey()) {
            throw new RuntimeException(
                "No 'midKey' paramater defined in the relation '{$this->name}'."
            );
        }

        return $this->data['midKeys'][0];
    }

    /**
     * Whether a foreign mid key is defined. For Many-to-Many relationships only.
     * @throws RuntimeException
     */
    public function hasForeignMidKey(): bool
    {
        return !is_null($this->data['midKeys'][1] ?? null);
    }

    /**
     * Get a foreign mid key. For Many-to-Many relationships only.
     * @throws RuntimeException
     */
    public function getForeignMidKey(): string
    {
        if (!$this->hasForeignMidKey()) {
            throw new RuntimeException(
                "No 'foreignMidKey' paramater defined in the relation '{$this->name}'."
            );
        }

        return $this->data['midKeys'][1];
    }

    /**
     * Whether a relationship name is defined.
     */
    public function hasRelationshipName(): bool
    {
        return isset($this->data['relationName']);
    }

    /**
     * Get a relationship name.
     * @throws RuntimeException
     */
    public function getRelationshipName(): string
    {
        if (!$this->hasRelationshipName()) {
            throw new RuntimeException(
                "No 'relationName' paramater defined in the relation '{$this->name}'."
            );
        }

        return $this->data['relationName'];
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
     * @return mixed
     */
    public function getParam(string $name)
    {
        return $this->data[$name] ?? null;
    }
}
