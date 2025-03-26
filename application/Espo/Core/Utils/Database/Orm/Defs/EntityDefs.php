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

namespace Espo\Core\Utils\Database\Orm\Defs;

use Espo\ORM\Defs\Params\EntityParam;

/**
 * Immutable.
 */
class EntityDefs
{
    /** @var array<string, AttributeDefs> */
    private array $attributes = [];
    /** @var array<string, RelationDefs> */
    private array $relations = [];
    /** @var array<string, IndexDefs> */
    private array $indexes = [];

    private function __construct() {}

    public static function create(): self
    {
        return new self();
    }

    public function withAttribute(AttributeDefs $attributeDefs): self
    {
        $obj = clone $this;
        $obj->attributes[$attributeDefs->getName()] = $attributeDefs;

        return $obj;
    }

    public function withRelation(RelationDefs $relationDefs): self
    {
        $obj = clone $this;
        $obj->relations[$relationDefs->getName()] = $relationDefs;

        return $obj;
    }

    public function withIndex(IndexDefs $index): self
    {
        $obj = clone $this;
        $obj->indexes[$index->getName()] = $index;

        return $obj;
    }

    public function withoutAttribute(string $name): self
    {
        $obj = clone $this;
        unset($obj->attributes[$name]);

        return $obj;
    }

    public function withoutRelation(string $name): self
    {
        $obj = clone $this;
        unset($obj->relations[$name]);

        return $obj;
    }

    public function withoutIndex(string $name): self
    {
        $obj = clone $this;
        unset($obj->indexes[$name]);

        return $obj;
    }

    public function getAttribute(string $name): ?AttributeDefs
    {
        return $this->attributes[$name] ?? null;
    }

    public function getRelation(string $name): ?RelationDefs
    {
        return $this->relations[$name] ?? null;
    }

    public function getIndex(string $name): ?IndexDefs
    {
        return $this->indexes[$name] ?? null;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function toAssoc(): array
    {
        $data = [];

        if (count($this->attributes)) {
            $attributesData = [];

            foreach ($this->attributes as $name => $attributeDefs) {
                $attributesData[$name] = $attributeDefs->toAssoc();
            }

            $data[EntityParam::ATTRIBUTES] = $attributesData;
        }

        if (count($this->relations)) {
            $relationsData = [];

            foreach ($this->relations as $name => $relationDefs) {
                $relationsData[$name] = $relationDefs->toAssoc();
            }

            $data[EntityParam::RELATIONS] = $relationsData;
        }

        if (count($this->indexes)) {
            $indexesData = [];

            foreach ($this->indexes as $name => $indexDefs) {
                $indexesData[$name] = $indexDefs->toAssoc();
            }

            $data[EntityParam::INDEXES] = $indexesData;
        }

        return $data;
    }
}
