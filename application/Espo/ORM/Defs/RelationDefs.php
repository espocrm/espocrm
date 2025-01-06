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

use Espo\ORM\Defs\Params\RelationParam;
use Espo\ORM\Entity;

use RuntimeException;

/**
 * Relation definitions.
 */
class RelationDefs
{
    /** @var array<string, mixed> */
    private array $data;
    private string $name;

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
        $type = $this->data[RelationParam::TYPE] ?? null;

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
     * Whether it has a foreign entity type is defined.
     */
    public function hasForeignEntityType(): bool
    {
        return isset($this->data[RelationParam::ENTITY]);
    }

    /**
     * Get a foreign entity type.
     *
     * @throws RuntimeException
     */
    public function getForeignEntityType(): string
    {
        if (!$this->hasForeignEntityType()) {
            throw new RuntimeException("No 'entity' parameter defined in the relation '{$this->name}'.");
        }

        return $this->data[RelationParam::ENTITY];
    }

    /**
     * Get a foreign entity type.
     */
    public function tryGetForeignEntityType(): ?string
    {
        if (!$this->hasForeignEntityType()) {
            return null;
        }

        return $this->getForeignEntityType();
    }

    /**
     * Whether it has a foreign relation name.
     */
    public function hasForeignRelationName(): bool
    {
        return isset($this->data[RelationParam::FOREIGN]);
    }

    /**
     * Try to get a foreign relation name.
     *
     * @since 8.3.0
     */
    public function tryGetForeignRelationName(): ?string
    {
        if (!$this->hasForeignRelationName()) {
            return null;
        }

        return $this->getForeignRelationName();
    }

    /**
     * Get a foreign relation name.
     *
     * @throws RuntimeException
     */
    public function getForeignRelationName(): string
    {
        if (!$this->hasForeignRelationName()) {
            throw new RuntimeException("No 'foreign' parameter defined in the relation '{$this->name}'.");
        }

        return $this->data[RelationParam::FOREIGN];
    }

    /**
     * Whether a foreign key is defined.
     */
    public function hasForeignKey(): bool
    {
        return isset($this->data[RelationParam::FOREIGN_KEY]);
    }

    /**
     * Get a foreign key.
     *
     * @throws RuntimeException
     */
    public function getForeignKey(): string
    {
        if (!$this->hasForeignKey()) {
            throw new RuntimeException("No 'foreignKey' parameter defined in the relation '{$this->name}'.");
        }

        return $this->data[RelationParam::FOREIGN_KEY];
    }

    /**
     * Whether a key is defined.
     */
    public function hasKey(): bool
    {
        return isset($this->data[RelationParam::KEY]);
    }

    /**
     * Get a key.
     * @throws RuntimeException
     */
    public function getKey(): string
    {
        if (!$this->hasKey()) {
            throw new RuntimeException("No 'key' parameter defined in the relation '{$this->name}'.");
        }

        return $this->data[RelationParam::KEY];
    }

    /**
     * Whether a mid-key is defined. For Many-to-Many relationships only.
     */
    public function hasMidKey(): bool
    {
        return !is_null($this->data[RelationParam::MID_KEYS][0] ?? null);
    }

    /**
     * Get a mid-key. For Many-to-Many relationships only.
     *
     * @throws RuntimeException
     */
    public function getMidKey(): string
    {
        if (!$this->hasMidKey()) {
            throw new RuntimeException("No 'midKey' parameter defined in the relation '{$this->name}'.");
        }

        return $this->data[RelationParam::MID_KEYS][0];
    }

    /**
     * Whether a foreign mid-key is defined. For Many-to-Many relationships only.
     *
     * @throws RuntimeException
     */
    public function hasForeignMidKey(): bool
    {
        return !is_null($this->data[RelationParam::MID_KEYS][1] ?? null);
    }

    /**
     * Get a foreign mid-key. For Many-to-Many relationships only.
     *
     * @throws RuntimeException
     */
    public function getForeignMidKey(): string
    {
        if (!$this->hasForeignMidKey()) {
            throw new RuntimeException("No 'foreignMidKey' parameter defined in the relation '{$this->name}'.");
        }

        return $this->data[RelationParam::MID_KEYS][1];
    }

    /**
     * Whether a relationship name is defined.
     */
    public function hasRelationshipName(): bool
    {
        return isset($this->data[RelationParam::RELATION_NAME]);
    }

    /**
     * Get a relationship name.
     *
     * @throws RuntimeException
     */
    public function getRelationshipName(): string
    {
        if (!$this->hasRelationshipName()) {
            throw new RuntimeException("No 'relationName' parameter defined in the relation '{$this->name}'.");
        }

        return $this->data[RelationParam::RELATION_NAME];
    }

    /**
     * Get indexes.
     *
     * @return IndexDefs[]
     * @throws RuntimeException
     */
    public function getIndexList(): array
    {
        if ($this->getType() !== Entity::MANY_MANY) {
            throw new RuntimeException("Can't get indexes.");
        }

        $list = [];

        foreach (($this->data[RelationParam::INDEXES] ?? []) as $name => $item) {
            $list[] = IndexDefs::fromRaw($item, $name);
        }

        return $list;
    }

    /**
     * Get additional middle table conditions.
     *
     * @return array<string, ?scalar>
     */
    public function getConditions(): array
    {
        if ($this->getType() !== Entity::MANY_MANY) {
            throw new RuntimeException("Can't get conditions for non many-many relationship.");
        }

        return $this->getParam(RelationParam::CONDITIONS) ?? [];
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
}
