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

namespace Espo\Modules\Crm\Tools\Calendar\Items;

use Espo\Core\Field\DateTime;
use Espo\Modules\Crm\Tools\Calendar\Item;

use RuntimeException;
use stdClass;

class Event implements Item
{
    private ?DateTime $start;
    private ?DateTime $end;
    private string $entityType;
    /** @var array<string, mixed> */
    private array $attributes;
    /** @var string[] */
    private array $userIdList = [];
    /** @var array<string, string> */
    private array $userNameMap = [];

    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(?DateTime $start, ?DateTime $end, string $entityType, array $attributes)
    {
        $this->start = $start;
        $this->end = $end;
        $this->entityType = $entityType;
        $this->attributes = $attributes;
    }

    public function getRaw(): stdClass
    {
        $obj = (object) [
            'scope' => $this->entityType,
            'dateStart' => $this->start?->toString(),
            'dateEnd' => $this->end?->toString(),
        ];

        if ($this->userIdList !== []) {
            $obj->userIdList = $this->userIdList;
            $obj->userNameMap = (object) $this->userNameMap;
        }

        foreach ($this->attributes as $key => $value) {
            $obj->$key = $obj->$key ?? $value;
        }

        return $obj;
    }

    /**
     * @param mixed $value
     */
    public function withAttribute(string $name, $value): self
    {
        $obj = clone $this;
        $obj->attributes[$name] = $value;

        return $obj;
    }

    public function withId(string $id): self
    {
        $obj = clone $this;
        $obj->attributes['id'] = $id;

        return $obj;
    }

    public function withUserIdAdded(string $userId): self
    {
        $obj = clone $this;
        $obj->userIdList[] = $userId;

        return $obj;
    }

    /**
     * @param array<string, string> $userNameMap
     */
    public function withUserNameMap(array $userNameMap): self
    {
        $obj = clone $this;
        $obj->userNameMap = $userNameMap;

        return $obj;
    }

    public function getId(): string
    {
        $id = $this->attributes['id'] ?? null;

        if (!$id) {
            throw new RuntimeException();
        }

        return $id;
    }

    public function getStart(): ?DateTime
    {
        return $this->start;
    }

    public function getEnd(): ?DateTime
    {
        return $this->end;
    }

    public function getEntityType(): string
    {
        return $this->entityType;
    }

    /**
     * @return array<string, mixed>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @return string[]
     */
    public function getUserIdList(): array
    {
        return $this->userIdList;
    }

    /**
     * @return mixed
     */
    public function getAttribute(string $name)
    {
        return $this->attributes[$name] ?? null;
    }
}
