<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

namespace Espo\Core\Field;

use Espo\Core\Name\Field;
use Espo\ORM\Entity;
use InvalidArgumentException;

/**
 * A link-multiple item. Immutable.
 */
class LinkMultipleItem
{
    private string $id;
    private ?string $name = null;
    /** @var array<string, mixed> */
    private array $columnData = [];

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(string $id)
    {
        if ($id === '') {
            throw new InvalidArgumentException("Empty ID.");
        }

        $this->id = $id;
    }

    /**
     * Get an ID.
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Get a name.
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Get a column value.
     *
     * @return mixed
     */
    public function getColumnValue(string $column)
    {
        return $this->columnData[$column] ?? null;
    }

    /**
     * Whether a column value is set.
     */
    public function hasColumnValue(string $column): bool
    {
        return array_key_exists($column, $this->columnData);
    }

    /**
     * Get a list of set columns.
     *
     * @return array<int, string>
     */
    public function getColumnList(): array
    {
        return array_keys($this->columnData);
    }

    /**
     * Clone with a name.
     *
     * @param ?string $name Is nullable since 9.4.0.
     */
    public function withName(?string $name): self
    {
        $obj = $this->clone();
        $obj->name = $name;

        return $obj;
    }

    /**
     * Clone with a column value.
     *
     * @param mixed $value
     */
    public function withColumnValue(string $column, $value): self
    {
        $obj = $this->clone();
        $obj->columnData[$column] = $value;

        return $obj;
    }

    /**
     * Create.
     *
     * @throws InvalidArgumentException
     */
    public static function create(string $id, ?string $name = null): self
    {
        $obj = new self($id);
        $obj->name = $name;

        return $obj;
    }

    /**
     * Create from a link.
     *
     * @throws InvalidArgumentException
     *
     * @since 9.4.0
     */
    public static function fromLink(Link $link): self
    {
        return self::create($link->getId(), $link->getName());
    }

    /**
     * Create from an entity.
     *
     * @throws InvalidArgumentException
     *
     * @since 9.4.0
     */
    public static function fromEntity(Entity $entity): self
    {
        return self::create($entity->getId());
    }

    private function clone(): self
    {
        $obj = new self($this->id);

        $obj->name = $this->name;
        $obj->columnData = $this->columnData;

        return $obj;
    }
}
