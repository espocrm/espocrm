<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\ORM;

use Espo\ORM\Query\Select;

/**
 * Creates collections.
 */
class CollectionFactory
{
    protected EntityManager $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param array<Entity|array<string,mixed>> $dataList
     * @return EntityCollection<Entity>
     */
    public function create(?string $entityType = null, array $dataList = []): EntityCollection
    {
        return new EntityCollection($dataList, $entityType, $this->entityManager->getEntityFactory());
    }

    /**
     * @return SthCollection<Entity>
     */
    public function createFromSql(string $entityType, string $sql): SthCollection
    {
        return SthCollection::fromSql($entityType, $sql, $this->entityManager);
    }

    /**
     * @return SthCollection<Entity>
     */
    public function createFromQuery(Select $query): SthCollection
    {
        return SthCollection::fromQuery($query, $this->entityManager);
    }

    /**
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
