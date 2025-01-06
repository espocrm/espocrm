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

namespace Espo\Core\Duplicate;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Select\SelectBuilderFactory;

use Espo\ORM\Collection;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\ORM\Name\Attribute;
use Espo\ORM\Query\Part\Condition as Cond;
use Espo\ORM\Query\Part\WhereItem;
use RuntimeException;

class Finder
{
    private const LIMIT = 5;

    /** @var array<string, ?WhereBuilder<Entity>> */
    private array $whereBuilderMap = [];

    public function __construct(
        private EntityManager $entityManager,
        private SelectBuilderFactory $selectBuilderFactory,
        private WhereBuilderFactory $whereBuilderFactory
    ) {}

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
     *
     * @return ?Collection<Entity>
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

        if ($entity->hasId()) {
            $where = Cond::and(
                $where,
                Cond::notEqual(
                    Cond::column(Attribute::ID),
                    $entity->getId()
                )
            );
        }

        $duplicate = $this->entityManager
            ->getRDBRepository($entityType)
            ->where($where)
            ->select(Attribute::ID)
            ->findOne();

        return (bool) $duplicate;
    }

    /**
     * The method is public for backward compatibility.
     *
     * @return ?Collection<Entity>
     */
    public function findByWhere(Entity $entity, WhereItem $where): ?Collection
    {
        $entityType = $entity->getEntityType();

        if ($entity->hasId()) {
            $where = Cond::and(
                $where,
                Cond::notEqual(
                    Cond::column(Attribute::ID),
                    $entity->getId()
                )
            );
        }

        try {
            $baseQueryBuilder = $this->selectBuilderFactory
                ->create()
                ->from($entityType)
                ->withStrictAccessControl()
                ->buildQueryBuilder()
                ->select([Attribute::ID])
                ->limit(0, self::LIMIT);
        } catch (Forbidden|BadRequest $e) {
            throw new RuntimeException($e->getMessage(), 0, $e);
        }

        $repository = $this->entityManager->getRDBRepository($entityType);

        $query = $baseQueryBuilder
            ->where($where)
            ->build();

        $rdbBuilder = $repository->clone($query);

        if (!$rdbBuilder->findOne()) {
            return null;
        }

        $ids = array_map(
            fn(Entity $e) => $e->getId(),
            iterator_to_array($rdbBuilder->find())
        );

        return $repository
            ->clone($baseQueryBuilder->build())
            ->select(['*'])
            ->where([Attribute::ID => $ids])
            ->find();
    }

    private function getWhere(Entity $entity): ?WhereItem
    {
        $entityType = $entity->getEntityType();

        if (!array_key_exists($entityType, $this->whereBuilderMap)) {
            $this->whereBuilderMap[$entityType] = $this->loadWhereBuilder($entityType);
        }

        $builder = $this->whereBuilderMap[$entityType];

        return $builder?->build($entity);
    }

    /**
     * @return ?WhereBuilder<Entity>
     */
    private function loadWhereBuilder(string $entityType): ?WhereBuilder
    {
        if (!$this->whereBuilderFactory->has($entityType)) {
            return null;
        }

        return $this->whereBuilderFactory->create($entityType);
    }
}
