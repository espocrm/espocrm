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

namespace Espo\ORM\Repository\Deprecation;

use Espo\ORM\Collection;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\ORM\Mapper\BaseMapper;
use Espo\ORM\Query\Part\Expression;
use Espo\ORM\Query\Select;
use Espo\ORM\Repository\RDBSelectBuilder;
use Espo\ORM\SthCollection;

/**
 * @internal
 * @template TEntity of Entity
 */
trait RDBRepositoryDeprecationTrait
{
    /**
     * @deprecated Use `group` method.
     * @todo Remove in v9.0.
     * @param Expression|Expression[]|string|string[] $groupBy
     * @return RDBSelectBuilder<TEntity>
     */
    public function groupBy($groupBy): RDBSelectBuilder
    {
        return $this->group($groupBy);
    }

    /**
     * @deprecated As of v7.0. Use the Query Builder instead. Otherwise, code will be not portable.
     * @todo Remove in v9.0.
     */
    protected function getPDO(): \PDO
    {
        return $this->entityManager->getPDO();
    }

    /**
     * @deprecated Use `$this->entityManager`.
     * @todo Remove in v9.0.
     */
    protected function getEntityManager(): EntityManager
    {
        return $this->entityManager;
    }

    /**
     * @deprecated Use QueryBuilder instead.
     * @todo Rewrite usages.
     */
    public function deleteFromDb(string $id, bool $onlyDeleted = false): void
    {
        $mapper = $this->getMapper();

        if (!$mapper instanceof BaseMapper) {
            throw new \RuntimeException("Not supported 'deleteFromDb'.");
        }

        $mapper->deleteFromDb($this->entityType, $id, $onlyDeleted);
    }

    /**
     * Get an entity. If ID is NULL, a new entity is returned.
     *
     * @deprecated Use `getById` and `getNew`.
     * @todo Remove in v9.0.
     */
    public function get(?string $id = null): ?Entity
    {
        if (is_null($id)) {
            return $this->getNew();
        }

        return $this->getById($id);
    }

    /**
     * @deprecated As of v6.0. Use `getRelation(...)->find()`.
     * @todo Remove in v9.0.
     * @param ?array<string, mixed> $params
     * @return Collection<TEntity>|TEntity|null
     */
    public function findRelated(Entity $entity, string $relationName, ?array $params = null)
    {
        $params = $params ?? [];

        if ($entity->getEntityType() !== $this->entityType) {
            throw new \RuntimeException("Not supported entity type.");
        }

        if (!$entity->hasId()) {
            return null;
        }

        $type = $entity->getRelationType($relationName);
        /** @phpstan-ignore-next-line */
        $entityType = $entity->getRelationParam($relationName, 'entity');

        $additionalColumns = $params['additionalColumns'] ?? [];
        unset($params['additionalColumns']);

        $additionalColumnsConditions = $params['additionalColumnsConditions'] ?? [];
        unset($params['additionalColumnsConditions']);

        $select = null;

        if ($entityType) {
            $params['from'] = $entityType;
            $select = Select::fromRaw($params);
        }

        if ($type === Entity::MANY_MANY && count($additionalColumns)) {
            if ($select === null) {
                throw new \RuntimeException();
            }

            $select = $this->applyRelationAdditionalColumns($entity, $relationName, $additionalColumns, $select);
        }

        // @todo Get rid of 'additionalColumnsConditions' usage. Use 'whereClause' instead.
        if ($type === Entity::MANY_MANY && count($additionalColumnsConditions)) {
            if ($select === null) {
                throw new \RuntimeException();
            }

            $select = $this->applyRelationAdditionalColumnsConditions(
                $entity,
                $relationName,
                $additionalColumnsConditions,
                $select
            );
        }

        /** @var Collection<TEntity>|TEntity|null $result */
        $result = $this->getMapper()->selectRelated($entity, $relationName, $select);

        if ($result instanceof SthCollection) {
            /** @var SthCollection<TEntity> */
            return $this->entityManager->getCollectionFactory()->createFromSthCollection($result);
        }

        return $result;
    }

    /**
     * @deprecated As of v6.0. Use `getRelation(...)->count()`.
     * @todo Remove in v9.0.
     * @param ?array<string, mixed> $params
     */
    public function countRelated(Entity $entity, string $relationName, ?array $params = null): int
    {
        $params = $params ?? [];

        if ($entity->getEntityType() !== $this->entityType) {
            throw new \RuntimeException("Not supported entity type.");
        }

        if (!$entity->hasId()) {
            return 0;
        }

        $type = $entity->getRelationType($relationName);
        /** @phpstan-ignore-next-line */
        $entityType = $entity->getRelationParam($relationName, 'entity');

        $additionalColumnsConditions = $params['additionalColumnsConditions'] ?? [];
        unset($params['additionalColumnsConditions']);

        $select = null;

        if ($entityType) {
            $params['from'] = $entityType;

            $select = Select::fromRaw($params);
        }

        if ($type === Entity::MANY_MANY && count($additionalColumnsConditions)) {
            if ($select === null) {
                throw new \RuntimeException();
            }

            $select = $this->applyRelationAdditionalColumnsConditions(
                $entity,
                $relationName,
                $additionalColumnsConditions,
                $select
            );
        }

        return (int) $this->getMapper()->countRelated($entity, $relationName, $select);
    }

    /**
     * @param string[] $columns
     */
    private function applyRelationAdditionalColumns(
        Entity $entity,
        string $relationName,
        array $columns,
        Select $select
    ): Select {

        if (empty($columns)) {
            return $select;
        }

        /** @phpstan-ignore-next-line */
        $middleName = lcfirst($entity->getRelationParam($relationName, 'relationName'));

        $selectItemList = $select->getSelect();

        if ($selectItemList === []) {
            $selectItemList[] = '*';
        }

        foreach ($columns as $column => $alias) {
            $selectItemList[] = [
                $middleName . '.' . $column,
                $alias
            ];
        }

        return $this->entityManager
            ->getQueryBuilder()
            ->select()
            ->clone($select)
            ->select($selectItemList)
            ->build();
    }

    /**
     * @param array<string, mixed> $conditions
     */
    private function applyRelationAdditionalColumnsConditions(
        Entity $entity,
        string $relationName,
        array $conditions,
        Select $select
    ): Select {

        if (empty($conditions)) {
            return $select;
        }

        /** @phpstan-ignore-next-line */
        $middleName = lcfirst($entity->getRelationParam($relationName, 'relationName'));

        $builder = $this->entityManager
            ->getQueryBuilder()
            ->select()
            ->clone($select);

        foreach ($conditions as $column => $value) {
            $builder->where(
                $middleName . '.' . $column,
                $value
            );
        }

        return $builder->build();
    }
    /**
     * @deprecated As of v6.0. Use `getRelation(...)->isRelated(...)`.
     * @todo Remove in v9.0.
     * @param TEntity|string $foreign
     */
    public function isRelated(Entity $entity, string $relationName, $foreign): bool
    {
        if (!$entity->hasId()) {
            return false;
        }

        if ($entity->getEntityType() !== $this->entityType) {
            throw new \RuntimeException("Not supported entity type.");
        }

        /** @var mixed $foreign */

        if ($foreign instanceof Entity) {
            if (!$foreign->hasId()) {
                return false;
            }

            $id = $foreign->getId();
        }
        else if (is_string($foreign)) {
            $id = $foreign;
        }
        else {
            throw new \RuntimeException("Bad 'foreign' value.");
        }

        if (!$id) {
            return false;
        }

        if (in_array($entity->getRelationType($relationName), [Entity::BELONGS_TO, Entity::BELONGS_TO_PARENT])) {
            if (!$entity->has($relationName . 'Id')) {
                $entity = $this->getById($entity->getId());
            }
        }

        /** @phpstan-var TEntity $entity */

        $relation = $this->getRelation($entity, $relationName);

        if ($foreign instanceof Entity) {
            return $relation->isRelated($foreign);
        }

        return (bool) $this->countRelated($entity, $relationName, [
            'whereClause' => [
                'id' => $id,
            ],
        ]);
    }
    /**
     * @deprecated As of v6.0. Use `getRelation(...)->relate(...)`.
     * @todo Remove in v9.0.
     * @phpstan-ignore-next-line
     */
    public function relate(Entity $entity, string $relationName, $foreign, $columnData = null, array $options = [])
    {
        if (!$entity->hasId()) {
            throw new \RuntimeException("Can't relate an entity w/o ID.");
        }

        if (!$foreign instanceof Entity && !is_string($foreign)) {
            throw new \RuntimeException("Bad 'foreign' value.");
        }

        if ($entity->getEntityType() !== $this->entityType) {
            throw new \RuntimeException("Not supported entity type.");
        }

        $this->beforeRelate($entity, $relationName, $foreign, $columnData, $options);

        $beforeMethodName = 'beforeRelate' . ucfirst($relationName);

        if (method_exists($this, $beforeMethodName)) {
            $this->$beforeMethodName($entity, $foreign, $columnData, $options);
        }

        $result = false;

        $methodName = 'relate' . ucfirst($relationName);

        if (method_exists($this, $methodName)) {
            $result = $this->$methodName($entity, $foreign, $columnData, $options);
        }
        else {
            $data = $columnData;

            if ($columnData instanceof \stdClass) {
                $data = get_object_vars($columnData);
            }

            if ($foreign instanceof Entity) {
                $result = $this->getMapper()->relate($entity, $relationName, $foreign, $data);
            }
            else {
                $id = $foreign;

                $result = $this->getMapper()->relateById($entity, $relationName, $id, $data);
            }
        }

        if ($result) {
            $this->afterRelate($entity, $relationName, $foreign, $columnData, $options);

            $afterMethodName = 'afterRelate' . ucfirst($relationName);

            if (method_exists($this, $afterMethodName)) {
                $this->$afterMethodName($entity, $foreign, $columnData, $options);
            }
        }

        return $result;
    }

    /**
     * @deprecated As of v6.0. Use `getRelation(...)->unrelate(...)`.
     * @todo Remove in v9.0.
     * @phpstan-ignore-next-line
     */
    public function unrelate(Entity $entity, string $relationName, $foreign, array $options = [])
    {
        if (!$entity->hasId()) {
            throw new \RuntimeException("Can't unrelate an entity w/o ID.");
        }

        if (!$foreign instanceof Entity && !is_string($foreign)) {
            throw new \RuntimeException("Bad foreign value.");
        }

        if ($entity->getEntityType() !== $this->entityType) {
            throw new \RuntimeException("Not supported entity type.");
        }

        $this->beforeUnrelate($entity, $relationName, $foreign, $options);

        $beforeMethodName = 'beforeUnrelate' . ucfirst($relationName);

        if (method_exists($this, $beforeMethodName)) {
            $this->$beforeMethodName($entity, $foreign, $options);
        }

        $result = false;

        $methodName = 'unrelate' . ucfirst($relationName);

        if (method_exists($this, $methodName)) {
            $this->$methodName($entity, $foreign);
        }
        else {
            if ($foreign instanceof Entity) {
                $this->getMapper()->unrelate($entity, $relationName, $foreign);
            }
            else {
                $id = $foreign;

                $this->getMapper()->unrelateById($entity, $relationName, $id);
            }
        }

        $this->afterUnrelate($entity, $relationName, $foreign, $options);

        $afterMethodName = 'afterUnrelate' . ucfirst($relationName);

        if (method_exists($this, $afterMethodName)) {
            $this->$afterMethodName($entity, $foreign, $options);
        }

        return $result;
    }

    /**
     * @deprecated As of v6.0. Use `getRelation(...)->getColumn(...)`.
     * @todo Remove in v9.0.
     * @phpstan-ignore-next-line
     */
    public function getRelationColumn(Entity $entity, string $relationName, string $foreignId, string $column)
    {
        return $this->getMapper()->getRelationColumn($entity, $relationName, $foreignId, $column);
    }

    /**
     * @deprecated As of v6.0. Use `getRelation(...)->updateColumns(...)`.
     * @todo Remove in v9.0.
     * @phpstan-ignore-next-line
     */
    public function updateRelation(Entity $entity, string $relationName, $foreign, $columnData)
    {
        if (!$entity->hasId()) {
            throw new \RuntimeException("Can't update a relation for an entity w/o ID.");
        }

        if (!$foreign instanceof Entity && !is_string($foreign)) {
            throw new \RuntimeException("Bad foreign value.");
        }

        if ($columnData instanceof \stdClass) {
            $columnData = get_object_vars($columnData);
        }

        if ($foreign instanceof Entity) {
            $id = $foreign->getId();
        } else {
            $id = $foreign;
        }

        if (!is_string($id)) {
            throw new \RuntimeException("Bad foreign value.");
        }

        $this->getMapper()->updateRelationColumns($entity, $relationName, $id, $columnData);

        return true;
    }

    /**
     * @deprecated As of v6.0. Use `getRelation(...)->massRelate(...)`.
     * @todo Remove in v9.0.
     * @phpstan-ignore-next-line
     */
    public function massRelate(Entity $entity, string $relationName, array $params = [], array $options = [])
    {
        if (!$entity->hasId()) {
            throw new \RuntimeException("Can't related an entity w/o ID.");
        }

        $this->beforeMassRelate($entity, $relationName, $params, $options);

        $select = Select::fromRaw($params);

        $this->getMapper()->massRelate($entity, $relationName, $select);

        $this->afterMassRelate($entity, $relationName, $params, $options);
    }
}
