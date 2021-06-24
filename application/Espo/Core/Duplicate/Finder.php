<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Core\Duplicate;

use Espo\Core\{
    Select\SelectBuilderFactory,
};

use Espo\ORM\{
    EntityManager,
    Entity,
    Collection,
    Query\Part\WhereItem,
    Query\Part\Condition as Cond,
};

class Finder
{
    protected const LIMIT = 10;

    protected $selectAttributeList = ['id', 'name'];

    private $entityManager;

    private $selectBuilderFactory;

    private $whereBuilderFactory;

    private $whereBuilderMap = [];

    public function __construct(
        EntityManager $entityManager,
        SelectBuilderFactory $selectBuilderFactory,
        WhereBuilderFactory $whereBuilderFactory
    ) {
        $this->entityManager = $entityManager;
        $this->selectBuilderFactory = $selectBuilderFactory;
        $this->whereBuilderFactory = $whereBuilderFactory;
    }

    /**
     * Check whether an entity has a duplicate.
     */
    public function check(Entity $entity): bool
    {
        $where = $this->getWhere($entity);

        if (!$where) {
            return false;
        }

        return $this->checkByWhere($entity, $where);
    }

    /**
     * Find entity duplicates.
     */
    public function find(Entity $entity): ?Collection
    {
        $where = $this->getWhere($entity);

        if (!$where) {
            return null;
        }

        return $this->findByWhere($entity, $where);
    }

    /**
     * The method is public for backward compatibility.
     */
    public function checkByWhere(Entity $entity, WhereItem $where): bool
    {
        $entityType = $entity->getEntityType();

        if ($entity->getId()) {
            $where = Cond::and(
                $where,
                Cond::notEqual(
                    Cond::column('id'),
                    $entity->getId()
                )
            );
        }

        $duplicate = $this->entityManager
            ->getRDBRepository($entityType)
            ->where($where)
            ->select('id')
            ->findOne();

        return (bool) $duplicate;
    }

    /**
     * The method is public for backward compatibility.
     */
    public function findByWhere(Entity $entity, WhereItem $where): ?Collection
    {
        $entityType = $entity->getEntityType();

        if ($entity->getId()) {
            $where = Cond::and(
                $where,
                Cond::notEqual(
                    Cond::column('id'),
                    $entity->getId()
                )
            );
        }

        $query = $this->selectBuilderFactory
            ->create()
            ->from($entityType)
            ->withStrictAccessControl()
            ->buildQueryBuilder()
            ->where($where)
            ->select($this->getSelect($entity))
            ->limit(0, self::LIMIT)
            ->build();

        $builder = $this->entityManager
            ->getRDBRepository($entityType)
            ->clone($query);

        if (!$builder->findOne()) {
            return null;
        }

        return $builder->find();
    }

    private function getSelect(Entity $entity): array
    {
        $select = $this->selectAttributeList;

        foreach ($select as $item) {
            if (!$entity->hasAttribute($item)) {
                unset($select[$item]);
            }
        }

        return array_values($select);
    }

    private function getWhere(Entity $entity): ?WhereItem
    {
        $entityType = $entity->getEntityType();

        if (!array_key_exists($entityType, $this->whereBuilderMap)) {
            $this->whereBuilderMap[$entityType] = $this->loadWhereBuilder($entityType);
        }

        $builder = $this->whereBuilderMap[$entityType];

        if (!$builder) {
            return null;
        }

        return $builder->build($entity);
    }

    private function loadWhereBuilder(string $entityType): ?WhereBuilder
    {
        if (!$this->whereBuilderFactory->has($entityType)) {
            return null;
        }

        return $this->whereBuilderFactory->create($entityType);
    }
}
