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

use RuntimeException;

class DeleteBuilder implements Builder
{
    use SelectingBuilderTrait;

    /**
     * Create an instance.
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * Build a DELETE query.
     */
    public function build(): Delete
    {
        return Delete::fromRaw($this->params);
    }

    /**
     * Clone an existing query for a subsequent modifying and building.
     */
    public function clone(Delete $query): self
    {
        $this->cloneInternal($query);

        return $this;
    }

    /**
     * Set FROM parameter. For what entity type to build a query.
     */
    public function from(string $entityType, ?string $alias = null): self
    {
        if (isset($this->params['from'])) {
            throw new RuntimeException("Method 'from' can be called only once.");
        }

        $this->params['from'] = $entityType;
        $this->params['fromAlias'] = $alias;

        return $this;
    }

    /**
     * Apply LIMIT.
     */
    public function limit(?int $limit = null): self
    {
        $this->params['limit'] = $limit;

        return $this;
    }
}
