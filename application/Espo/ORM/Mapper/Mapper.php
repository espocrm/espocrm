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

namespace Espo\ORM\Mapper;

use Espo\ORM\Entity;
use Espo\ORM\Collection;
use Espo\ORM\Query\Select;

interface Mapper
{
    /**
     * Get a first entity from DB.
     */
    public function selectOne(Select $select): ?Entity;

    /**
     * Select entities from DB.
     *
     * @return Collection<Entity>
     */
    public function select(Select $select): Collection;

    /**
     * Get a number of records in DB.
     */
    public function count(Select $select): int;

    /**
     * Insert an entity into DB.
     */
    public function insert(Entity $entity): void;

    /**
     * Insert a collection into DB.
     *
     * @param Collection<Entity> $collection
     */
    public function massInsert(Collection $collection): void;

    /**
     * Update an entity in DB.
     */
    public function update(Entity $entity): void;

    /**
     * Delete an entity from DB or mark as deleted.
     */
    public function delete(Entity $entity): void;

    /**
     * Insert an entity into DB, on duplicate key update specified attributes.
     *
     * @param string[] $onDuplicateUpdateAttributeList
     */
    public function insertOnDuplicateUpdate(Entity $entity, array $onDuplicateUpdateAttributeList): void;
}
