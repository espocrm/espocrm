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

namespace Espo\Repositories;


use Espo\Entities\ArrayValue as ArrayValueEntity;

use Espo\ORM\Entity;
use Espo\Core\Repositories\Database;
use Espo\ORM\Repository\RDBRelation;
use RuntimeException;


/**
 * @extends Database<ArrayValueEntity>
 */
class Settings extends Database
{
    public function getById(string $id): ?Entity
    {
        throw new RuntimeException("No supported.");
    }

    public function save(Entity $entity, array $options = []): void
    {
        throw new RuntimeException("No supported.");
    }

    public function remove(Entity $entity, array $options = []): void
    {
        throw new RuntimeException("No supported.");
    }

    public function restoreDeleted(string $id): void
    {
        throw new RuntimeException("No supported.");
    }

    public function getRelation(Entity $entity, string $relationName): RDBRelation
    {
        throw new RuntimeException("No supported.");
    }
}
