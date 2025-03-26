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

namespace Espo\Core\Select\Text\Filter;

use Espo\ORM\Query\Part\WhereItem;

/**
 * Immutable.
 */
class Data
{
    private string $filter;
    /** @var string[] */
    private array $attributeList;
    private bool $skipWildcards = false;
    private ?WhereItem $fullTextSearchWhereItem = null;
    private bool $forceFullTextSearch = false;

    /**
     * @param string[] $attributeList
     */
    public function __construct(string $filter, array $attributeList)
    {
        $this->filter = $filter;
        $this->attributeList = $attributeList;
    }

    /**
     * @param string[] $attributeList
     */
    public static function create(string $filter, array $attributeList): self
    {
        return new self($filter, $attributeList);
    }

    public function withFilter(string $filter): self
    {
        $obj = clone $this;
        $obj->filter = $filter;

        return $obj;
    }

    /**
     * @param string[] $attributeList
     */
    public function withAttributeList(array $attributeList): self
    {
        $obj = clone $this;
        $obj->attributeList = $attributeList;

        return $obj;
    }

    public function withSkipWildcards(bool $skipWildcards = true): self
    {
        $obj = clone $this;
        $obj->skipWildcards = $skipWildcards;

        return $obj;
    }

    public function withForceFullTextSearch(bool $forceFullTextSearch = true): self
    {
        $obj = clone $this;
        $obj->forceFullTextSearch = $forceFullTextSearch;

        return $obj;
    }

    public function withFullTextSearchWhereItem(?WhereItem $fullTextSearchWhereItem): self
    {
        $obj = clone $this;
        $obj->fullTextSearchWhereItem = $fullTextSearchWhereItem;

        return $obj;
    }

    public function getFilter(): string
    {
        return $this->filter;
    }

    /**
     * @return string[]
     */
    public function getAttributeList(): array
    {
        return $this->attributeList;
    }

    public function skipWildcards(): bool
    {
        return $this->skipWildcards;
    }

    public function forceFullTextSearch(): bool
    {
        return $this->forceFullTextSearch;
    }

    public function getFullTextSearchWhereItem(): ?WhereItem
    {
        return $this->fullTextSearchWhereItem;
    }
}
