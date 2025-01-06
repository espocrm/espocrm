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

namespace Espo\ORM\Relation;

use Espo\ORM\Entity;
use Espo\ORM\EntityCollection;

/**
 * @internal Not ready for production.
 */
interface Relations
{
    /**
     * Reset a specific relation.
     */
    public function reset(string $relation): void;

    /**
     * Reset all.
     */
    public function resetAll(): void;

    /**
     * @param Entity|null $related
     */
    public function set(string $relation, Entity|null $related): void;

    /**
     * Is a relation set (updated).
     */
    public function isSet(string $relation): bool;

    /**
     * Get set (updated) record or records.
     *
     * @return Entity|null
     */
    public function getSet(string $relation): Entity|null;

    /**
     * Get one related record. For has-one, belongs-to.
     */
    public function getOne(string $relation): ?Entity;

    /**
     * Get a collection of related records. For has-many, many-many, has-children.
     *
     * @return EntityCollection<Entity>
     */
    public function getMany(string $relation): EntityCollection;
}
