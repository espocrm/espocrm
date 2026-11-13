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

namespace Espo\ORM;

use Espo\ORM\Query\Select;
use Espo\ORM\Relation\RelationsMap;

/**
 * Creates collections.
 */
class CollectionFactory
{
    public function __construct(
        protected EntityManager $entityManager,
        private RelationsMap $relationsMap,
    ) {}

    /**
     * Create.
     *
     * @param array<Entity|array<string, mixed>> $dataList
     * @return EntityCollection<Entity>
     */
    public function create(?string $entityType = null, array $dataList = []): EntityCollection
    {
        return new EntityCollection($dataList, $entityType, $this->entityManager->getEntityFactory());
    }

    /**
     * Create from an SQL.
     *
     * @return SthCollection<Entity>
     */
    public function createFromSql(string $entityType, string $sql): SthCollection
    {
        return SthCollection::fromSql(
            entityType: $entityType,
            sql: $sql,
            entityManager: $this->entityManager,
            relationsMap: $this->relationsMap,
        );
    }

    /**
     * Create from a query.
     *
     * @return SthCollection<Entity>
     */
    public function createFromQuery(Select $query): SthCollection
    {
        return SthCollection::fromQuery(
            query: $query,
            entityManager: $this->entityManager,
            relationsMap: $this->relationsMap,
        );
    }

    /**
     * Create EntityCollection from SthCollection.
     *
     * @template TEntity of Entity
     * @param SthCollection<TEntity> $sthCollection
     * @return EntityCollection<TEntity>
     */
    public function createFromSthCollection(SthCollection $sthCollection): EntityCollection
    {
        /** @var EntityCollection<TEntity> */
        return EntityCollection::fromSthCollection($sthCollection);
    }
}
