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
 * A link value object. Immutable.
 */
class Link
{
    private string $id;
    private ?string $name = null;

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(string $id)
    {
        if (!$id) {
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
     * Clone with a name.
     */
    public function withName(?string $name): self
    {
        $obj = new self($this->id);

        $obj->name = $name;

        return $obj;
    }

    /**
     * Create from an ID.
     *
     * @throws InvalidArgumentException
     */
    public static function create(string $id, ?string $name = null): self
    {
        return (new self($id))->withName($name);
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
}
