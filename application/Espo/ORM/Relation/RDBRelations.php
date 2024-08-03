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
use Espo\ORM\EntityManager;
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
     * @return Collection<Entity>
     */
    public function getMany(string $relation): Collection
    {
        $collection = $this->get($relation);

        if (!$collection instanceof Collection) {
            throw new LogicException("Not a collection. Use `getOne` instead.");
        }

        /** @var Collection<Entity> */
        return $collection;
    }

    /**
     * @param string $relation
     * @return Entity|Collection<Entity>|null
     */
    private function get(string $relation): Entity|Collection|null
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
                $relationRepository->find() :
                $relationRepository->findOne();
        }

        return $this->data[$relation];
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
