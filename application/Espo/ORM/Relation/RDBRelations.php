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

use Espo\ORM\BaseEntity;
use Espo\ORM\Defs\Defs;
use Espo\ORM\Defs\Params\RelationParam;
use Espo\ORM\Entity;
use Espo\ORM\EntityCollection;
use Espo\ORM\EntityManager;
use Espo\ORM\Mapper\RDBMapper;
use Espo\ORM\Name\Attribute;
use Espo\ORM\Query\Part\Order;
use Espo\ORM\Type\RelationType;
use LogicException;
use RuntimeException;

/**
 * @internal
 */
class RDBRelations implements Relations
{
    /** @var array<string, Entity|EntityCollection<Entity>|null> */
    private array $data = [];
    /** @var array<string, Entity|null> */
    private array $setData = [];
    private ?Entity $entity = null;

    /** @var string[] */
    private array $manyTypeList = [
        RelationType::MANY_MANY,
        RelationType::HAS_MANY,
        RelationType::HAS_CHILDREN,
    ];

    private Defs $defs;

    public function __construct(
        private EntityManager $entityManager,
    ) {
        $this->defs = $this->entityManager->getDefs();
    }

    public function setEntity(Entity $entity): void
    {
        if ($this->entity) {
            throw new LogicException("Entity is already set.");
        }

        $this->entity = $entity;
    }

    public function reset(string $relation): void
    {
        unset($this->data[$relation]);
        unset($this->setData[$relation]);
    }

    public function resetAll(): void
    {
        $this->data = [];
        $this->setData = [];
    }

    public function isSet(string $relation): bool
    {
        return array_key_exists($relation, $this->setData);
    }

    /**
     * @return Entity|null
     */
    public function getSet(string $relation): Entity|null
    {
        if (!array_key_exists($relation, $this->setData)) {
            throw new RuntimeException("Relation '$relation' is not set.");
        }

        return $this->setData[$relation];
    }

    /**
     * @param Entity|null $related
     */
    public function set(string $relation, Entity|null $related): void
    {
        if (!$this->entity) {
            throw new LogicException("No entity set.");
        }

        $type = $this->getRelationType($relation);

        if (!$type) {
            throw new LogicException("Relation '$relation' does not exist.");
        }

        if (
            !in_array($type, [
                RelationType::BELONGS_TO,
                RelationType::BELONGS_TO_PARENT,
                RelationType::HAS_ONE,
            ])
        ) {
            throw new LogicException("Relation type '$type' is not supported for setting.");
        }

        if ($related) {
            $nameAttribute = $this->entityManager
                ->getDefs()
                ->getEntity($this->entity->getEntityType())
                ->getRelation($relation)
                ->getParam('nameAttribute') ?? 'name';

            $valueMap = [
                $relation . 'Id' => $related->getId(),
                $relation . 'Name' => $related->get($nameAttribute),
            ];

            if ($type === RelationType::BELONGS_TO_PARENT) {
                $valueMap[$relation . 'Type'] = $related->getEntityType();
            }
        } else {
            $valueMap = [
                $relation . 'Id' => null,
                $relation . 'Name' => null,
            ];

            if ($type === RelationType::BELONGS_TO_PARENT) {
                $valueMap[$relation . 'Type'] = null;
            }
        }

        $this->entity->setMultiple($valueMap);

        $this->setData[$relation] = $related;
    }

    public function getOne(string $relation): ?Entity
    {
        $entity = $this->get($relation);

        if ($entity instanceof EntityCollection) {
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
        if (array_key_exists($relation, $this->setData)) {
            return $this->setData[$relation];
        }

        if (!array_key_exists($relation, $this->data)) {
            if (!$this->entity) {
                throw new LogicException("No entity set.");
            }

            $isMany = in_array($this->getRelationType($relation), $this->manyTypeList);

            $this->data[$relation] = $isMany ?
                $this->findMany($relation) :
                $this->findOne($relation);
        }

        $object = $this->data[$relation];

        if ($object instanceof EntityCollection) {
            /** @var EntityCollection<Entity> $object */
            $object = new EntityCollection(iterator_to_array($object));
        }

        return $object;
    }

    private function findOne(string $relation): ?Entity
    {
        if (!$this->entity) {
            throw new LogicException();
        }

        if (!$this->entity->hasId() && $this->getRelationType($relation) === RelationType::HAS_ONE) {
            return null;
        }

        $foreignEntity = $this->getPartiallyLoadedForeignEntity($relation);

        if ($foreignEntity === false) {
            // Parent type does not exist. Not throwing an error deliberately.
            return null;
        }

        if ($foreignEntity) {
            return $foreignEntity;
        }

        $mapper = $this->entityManager->getMapper();

        /** @noinspection PhpConditionAlreadyCheckedInspection */
        if (!$mapper instanceof RDBMapper) {
            throw new RuntimeException("Non RDB mapper.");
        }

        // We use the Mapper as RDBRelation requires an entity with ID.

        $entity = $mapper->selectRelated($this->entity, $relation);

        if (!$entity) {
            return null;
        }

        if (!$entity instanceof Entity) {
            throw new LogicException("Bad mapper return.");
        }

        return $entity;
    }

    /**
     * @return EntityCollection<Entity>
     */
    private function findMany(string $relation): EntityCollection
    {
        if (!$this->entity) {
            throw new LogicException();
        }

        if (!$this->entity->hasId()) {
            /** @var EntityCollection<Entity> */
            return new EntityCollection();
        }

        $relationDefs = $this->defs
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

    private function getRelationType(string $relation): ?string
    {
        if (!$this->entity) {
            throw new LogicException();
        }

        return $this->defs
            ->getEntity($this->entity->getEntityType())
            ->tryGetRelation($relation)
            ?->getType();
    }

    private function getPartiallyLoadedForeignEntity(string $relation): BaseEntity|false|null
    {
        if (!$this->entity) {
            throw new LogicException();
        }

        $defs = $this->defs
            ->getEntity($this->entity->getEntityType())
            ->getRelation($relation);

        if (!$defs->getParam(RelationParam::DEFERRED_LOAD)) {
            return null;
        }

        $relationType = $defs->getType();

        $id = null;
        $foreignEntityType = null;

        if ($relationType === RelationType::BELONGS_TO) {
            $foreignEntityType = $defs->getForeignEntityType();
            $nameAttribute = $relation . 'Name';
            $id = $this->entity->get($relation . 'Id');
            $name = $this->entity->get($nameAttribute);

            if (
                $id &&
                $name === null &&
                $this->entity->hasAttribute($nameAttribute) &&
                $this->entity->has($nameAttribute)
            ) {
                $hasDeleted = $this->defs
                    ->getEntity($foreignEntityType)
                    ->hasAttribute(Attribute::DELETED);

                if ($hasDeleted) {
                    // Could be either soft-deleted or have name set to null.
                    // We resort to not using a partially loaded entity.
                    return null;
                }
            }
        } else if ($relationType === RelationType::BELONGS_TO_PARENT) {
            $foreignEntityType = $this->entity->get($relation . 'Type');
            $id = $this->entity->get($relation . 'Id');

            if (!$this->entityManager->hasRepository($foreignEntityType)) {
                return false;
            }
        }

        if (!$foreignEntityType || !$id) {
            return null;
        }

        $foreignEntity = $this->entityManager->getNewEntity($foreignEntityType);

        if (!$foreignEntity instanceof BaseEntity) {
            return null;
        }

        $foreignEntity->set(Attribute::ID, $id);
        $foreignEntity->setAsFetched();
        $foreignEntity->setAsPartiallyLoaded();

        return $foreignEntity;
    }
}
