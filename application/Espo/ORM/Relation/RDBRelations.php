<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

use Espo\ORM\Collection;
use Espo\ORM\Entity;
use Espo\ORM\EntityCollection;
use Espo\ORM\EntityManager;
use Espo\ORM\Query\Part\Order;
use Espo\ORM\Type\RelationType;
use LogicException;

/**
 * @internal
 */
class RDBRelations implements Relations
{
    /** @var array<string, Entity|Collection<Entity>|null> */
    private array $data = [];
    private ?Entity $entity = null;

    public function __construct(
        private EntityManager $entityManager,
    ) {}

    public function setEntity(Entity $entity): void
    {
        if ($this->entity) {
            throw new LogicException("Entity is already set.");
        }

        $this->entity = $entity;
    }

    public function reset(): void
    {
        $this->data = [];
    }

    public function getOne(string $relation): ?Entity
    {
        $entity = $this->get($relation);

        if ($entity instanceof Collection) {
            throw new LogicException("Not an entity. Use `getMany` instead.");
        }

        return $entity;
    }

    /**
     * @return EntityCollection<Entity>
     */
    public function getMany(string $relation): EntityCollection
    {
        $collection = $this->get($relation);

        if (!$collection instanceof EntityCollection) {
            throw new LogicException("Not a collection. Use `getOne` instead.");
        }

        /** @var EntityCollection<Entity> */
        return $collection;
    }

    /**
     * @param string $relation
     * @return Entity|EntityCollection<Entity>|null
     */
    private function get(string $relation): Entity|EntityCollection|null
    {
        if (!array_key_exists($relation, $this->data)) {
            if (!$this->entity) {
                throw new LogicException("No entity set.");
            }

            $relationRepository = $this->entityManager->getRelation($this->entity, $relation);

            $isMany = in_array($this->getRelationType($relation), [
                RelationType::MANY_MANY,
                RelationType::HAS_MANY,
                RelationType::HAS_CHILDREN,
            ]);

            $this->data[$relation] = $isMany ?
                $this->findMany($relation) :
                $relationRepository->findOne();
        }

        $object = $this->data[$relation];

        if ($object instanceof Collection) {
            /** @var EntityCollection<Entity> $object */
            $object = new EntityCollection(iterator_to_array($object));
        }

        return $object;
    }

    /**
     * @return EntityCollection<Entity>
     */
    private function findMany(string $relation): EntityCollection
    {
        if (!$this->entity) {
            throw new LogicException();
        }

        $relationDefs = $this->entityManager
            ->getDefs()
            ->getEntity($this->entity->getEntityType())
            ->getRelation($relation);

        $orderBy = null;
        $order = null;

        if ($relationDefs->getParam('orderBy')) {
            $orderBy = $relationDefs->getParam('orderBy');

            if ($relationDefs->getParam('order')) {
                $order = strtoupper($relationDefs->getParam('order')) === Order::DESC ? Order::DESC : Order::ASC;
            }
        }

        $builder = $this->entityManager->getRelation($this->entity, $relation);

        if ($orderBy) {
            $builder->order($orderBy, $order);
        }

        $collection = $builder->find();

        if (!$collection instanceof EntityCollection) {
            $collection = new EntityCollection(iterator_to_array($collection));
        }

        /** @var EntityCollection<Entity> */
        return $collection;
    }

    private function getRelationType(string $relation): string
    {
        if (!$this->entity) {
            throw new LogicException();
        }

        return $this->entityManager
            ->getDefs()
            ->getEntity($this->entity->getEntityType())
            ->getRelation($relation)
            ->getType();
    }
}
