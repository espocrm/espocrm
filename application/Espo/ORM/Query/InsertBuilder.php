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

namespace Espo\ORM\Query;

class InsertBuilder implements Builder
{
    use BaseBuilderTrait;

    /**
     * @var array<string, mixed>
     */
    protected $params = [];

    /**
     * Create an instance.
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * Build a INSERT query.
     */
    public function build(): Insert
    {
        return Insert::fromRaw($this->params);
    }

    /**
     * Clone an existing query for a subsequent modifying and building.
     */
    public function clone(Insert $query): self
    {
        $this->cloneInternal($query);

        return $this;
    }

    /**
     * Into what entity type to insert.
     */
    public function into(string $entityType): self
    {
        $this->params['into'] = $entityType;

        return $this;
    }

    /**
     * What columns to set with values. A list of columns.
     *
     * @param string[] $columns
     */
    public function columns(array $columns): self
    {
        $this->params['columns'] = $columns;

        return $this;
    }

    /**
     * What values to insert. A key-value map or a list of key-value maps.
     *
     * @param array<string, ?scalar>|array<string, ?scalar>[] $values
     */
    public function values(array $values): self
    {
        $this->params['values'] = $values;

        return $this;
    }

    /**
     * Values to set on duplicate key. A key-value map.
     *
     * @param array<string, ?scalar> $updateSet
     */
    public function updateSet(array $updateSet): self
    {
        $this->params['updateSet'] = $updateSet;

        return $this;
    }

    /**
     * For a mass insert by a select sub-query.
     */
    public function valuesQuery(SelectingQuery $query): self
    {
        $this->params['valuesQuery'] = $query;

        return $this;
    }
}
