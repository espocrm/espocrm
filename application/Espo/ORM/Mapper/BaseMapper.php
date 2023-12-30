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

namespace Espo\ORM\Mapper;

use Espo\ORM\Entity;
use Espo\ORM\BaseEntity;
use Espo\ORM\Collection;
use Espo\ORM\Query\DeleteBuilder;
use Espo\ORM\Query\InsertBuilder;
use Espo\ORM\Query\SelectBuilder;
use Espo\ORM\Executor\QueryExecutor;
use Espo\ORM\Query\UpdateBuilder;
use Espo\ORM\SthCollection;
use Espo\ORM\EntityFactory;
use Espo\ORM\CollectionFactory;
use Espo\ORM\Metadata;
use Espo\ORM\Query\Select;

use PDO;
use stdClass;
use LogicException;
use RuntimeException;

/**
 * Abstraction for DB. Mapping of Entity to DB. Supposed to be used only internally. Use repositories instead.
 *
 * @todo Use entityDefs. Don't use methods of BaseEntity.
 */
class BaseMapper implements RDBMapper
{
    private const ATTR_ID = 'id';
    private const ATTR_DELETED = 'deleted';
    private const FUNC_COUNT = 'COUNT';

    private Helper $helper;

    public function __construct(
        private PDO $pdo,
        private EntityFactory $entityFactory,
        private CollectionFactory $collectionFactory,
        private Metadata $metadata,
        private QueryExecutor $queryExecutor
    ) {
        $this->helper = new Helper($metadata);
    }

    /**
     * {@inheritdoc}
     */
    public function selectOne(Select $select): ?Entity
    {
        $entityType = $select->getFrom();

        if ($entityType === null) {
            throw new RuntimeException("No entity type.");
        }

        $select = $this->addFromAliasToSelectQuery($select);
        $entity = $this->entityFactory->create($entityType);

        $sth = $this->queryExecutor->execute($select);

        $row = $sth->fetch();

        if (!$row) {
            return null;
        }

        $this->populateEntityFromRow($entity, $row);
        $entity->setAsFetched();

        return $entity;
    }

    /**
     * {@inheritdoc}
     * @return SthCollection<Entity>
     */
    public function select(Select $select): SthCollection
    {
        $select = $this->addFromAliasToSelectQuery($select);

        return $this->collectionFactory->createFromQuery($select);
    }

    /**
     * {@inheritdoc}
     */
    public function count(Select $select): int
    {
        return (int) $this->aggregate($select, self::FUNC_COUNT, 'id');
    }

    public function max(Select $select, string $attribute): int|float
    {
         $value =  $this->aggregate($select, 'MAX', $attribute);

         return $this->castToNumber($value);
    }

    public function min(Select $select, string $attribute): int|float
    {
        $value = $this->aggregate($select, 'MIN', $attribute);

        return $this->castToNumber($value);
    }

    public function sum(Select $select, string $attribute): int|float
    {
        $value = $this->aggregate($select, 'SUM', $attribute);

        return $this->castToNumber($value);
    }

    private function castToNumber(mixed $value): int|float
    {
        if (is_int($value) || is_float($value)) {
            return $value;
        }

        if (!is_string($value)) {
            return 0;
        }

        if (str_contains($value, '.')) {
            return (float) $value;
        }

        return (int) $value;
    }

    private function addFromAliasToSelectQuery(Select $select): Select
    {
        if ($select->getFromAlias() || !$select->getFrom()) {
            return $select;
        }

        return SelectBuilder::create()
            ->clone($select)
            ->from($select->getFrom(), lcfirst($select->getFrom()))
            ->build();
    }

    /**
     * Select entities from DB by aт SQL query.
     *
     * @return SthCollection<Entity>
     */
    public function selectBySql(string $entityType, string $sql): SthCollection
    {
        return $this->collectionFactory->createFromSql($entityType, $sql);
    }

    private function aggregate(Select $select, string $aggregation, string $aggregationBy): mixed
    {
        $entityType = $select->getFrom();

        if ($entityType === null) {
            throw new RuntimeException("No entity type.");
        }

        $entity = $this->entityFactory->create($entityType);

        if (!$aggregation || !$entity->hasAttribute($aggregationBy)) {
            throw new RuntimeException();
        }

        $select = $this->addFromAliasToSelectQuery($select);
        $selectAggregation = $this->convertSelectQueryToAggregation($select, $aggregation, $aggregationBy);

        $sth = $this->queryExecutor->execute($selectAggregation);
        $row = $sth->fetch();

        if (!$row) {
            return null;
        }

        return $row['value'] ?? null;
    }

    private function convertSelectQueryToAggregation(
        Select $select,
        string $aggregation,
        string $aggregationBy = 'id'
    ): Select {

        $expression = "{$aggregation}:({$aggregationBy})";

        $raw = $select->getRaw();

        unset($raw['select']);
        unset($raw['orderBy']);
        unset($raw['order']);
        unset($raw['offset']);
        unset($raw['limit']);
        unset($raw['distinct']);
        unset($raw['forShare']);
        unset($raw['forUpdate']);

        $selectAggregation = SelectBuilder::create()
            ->clone(Select::fromRaw($raw))
            ->select($expression, 'value')
            ->build();

        $wrap = $aggregation === self::FUNC_COUNT && (
            $select->isDistinct() || $select->getGroup()
        );

        if (!$wrap) {
            return $selectAggregation;
        }

        $expression = "{$aggregation}:(asq.{$aggregationBy})";

        $subQueryBuilder = SelectBuilder::create()
            ->clone($selectAggregation)
            ->select([])
            ->select('id');

        if ($select->isDistinct()) {
            $subQueryBuilder->distinct();
        }

        return SelectBuilder::create()
            ->select($expression, 'value')
            ->fromQuery($subQueryBuilder->build(), 'asq')
            ->build();
    }

    /**
     * {@inheritDoc}
     *
     * @return Collection<Entity>|Entity|null
     */
    public function selectRelated(Entity $entity, string $relationName, ?Select $select = null): Collection|Entity|null
    {
        $result = $this->selectRelatedInternal($entity, $relationName, $select);

        if (is_int($result)) {
            throw new LogicException();
        }

        return $result;
    }

    /**
     * @return Collection<Entity>|Entity|int|null
     */
    private function selectRelatedInternal(
        Entity $entity,
        string $relationName,
        ?Select $select = null,
        bool $returnTotalCount = false
    ): Collection|Entity|int|null {

        $params = [];

        if ($select) {
            $params = $select->getRaw();
        }

        $entityType = $entity->getEntityType();
        $relType = $entity->getRelationType($relationName);

        $relEntityType = $this->getRelationParam($entity, $relationName, 'entity');

        $relEntity = null;

        if (!$relType) {
            throw new LogicException(
                "Missing 'type' in definition for relationship '{$relationName}' in {entityType} entity.");
        }

        if ($relType !== Entity::BELONGS_TO_PARENT) {
            if (!$relEntityType) {
                throw new LogicException(
                    "Missing 'entity' in definition for relationship '{$relationName}' in {entityType} entity.");
            }

            $relEntity = $this->entityFactory->create($relEntityType);
        }

        $params['whereClause'] ??= [];

        $keySet = $this->helper->getRelationKeys($entity, $relationName);

        $key = $keySet['key'];
        $foreignKey = $keySet['foreignKey'];

        switch ($relType) {
            case Entity::BELONGS_TO:
                /** @var Entity $relEntity */

                $params['whereClause'][] = [$foreignKey =>$entity->get($key)];
                $params['offset'] = 0;
                $params['limit'] = 1;
                $params['from'] = $relEntity->getEntityType();
                $params['fromAlias'] ??= lcfirst($relEntity->getEntityType());

                $select = Select::fromRaw($params);

                if ($returnTotalCount) {
                    $select = $this->convertSelectQueryToAggregation($select, self::FUNC_COUNT);

                    $sth = $this->queryExecutor->execute($select);
                    $row = $sth->fetch();

                    if (!$row) {
                        return 0;
                    }

                    return (int) $row['value'];
                }

                $sth = $this->queryExecutor->execute($select);
                $row = $sth->fetch();

                if (!$row) {
                    return null;
                }

                $this->populateEntityFromRow($relEntity, $row);
                $relEntity->setAsFetched();

                return $relEntity;

            case Entity::HAS_MANY:
            case Entity::HAS_CHILDREN:
            case Entity::HAS_ONE:
                /** @var Entity $relEntity */

                $params['from'] = $relEntity->getEntityType();
                $params['fromAlias'] ??= lcfirst($relEntity->getEntityType());
                $params['whereClause'][] = [$foreignKey => $entity->get($key)];

                if ($relType == Entity::HAS_CHILDREN) {
                    $foreignType = $keySet['foreignType'] ?? null;

                    if ($foreignType === null) {
                        throw new RuntimeException("Bad relation key.");
                    }

                    $params['whereClause'][] = [$foreignType => $entity->getEntityType()];
                }

                $relConditions = $this->getRelationParam($entity, $relationName, 'conditions');

                if ($relConditions) {
                    $params['whereClause'][] = $relConditions;
                }

                if ($relType == Entity::HAS_ONE) {
                    $params['offset'] = 0;
                    $params['limit'] = 1;
                }

                $select = Select::fromRaw($params);

                if ($returnTotalCount) {
                    $select = $this->convertSelectQueryToAggregation($select, self::FUNC_COUNT);

                    $sth = $this->queryExecutor->execute($select);
                    $row = $sth->fetch();

                    if (!$row) {
                        return 0;
                    }

                    return (int) $row['value'];
                }

                if ($relType == Entity::HAS_ONE) {
                    $sth = $this->queryExecutor->execute($select);
                    $row = $sth->fetch();

                    if (!$row) {
                        return null;
                    }

                    $this->populateEntityFromRow($relEntity, $row);
                    $relEntity->setAsFetched();

                    return $relEntity;
                }

                return $this->collectionFactory->createFromQuery($select);

            case Entity::MANY_MANY:
                /** @var Entity $relEntity */

                $params['from'] = $relEntity->getEntityType();
                $params['fromAlias'] ??= lcfirst($relEntity->getEntityType());
                $params['joins'] ??= [];
                $params['joins'][] = $this->getManyManyJoin($entity, $relationName);
                $params['select'] = $this->getModifiedSelectForManyToMany(
                    $entity,
                    $relationName,
                    $params['select'] ?? []
                );

                $select = Select::fromRaw($params);

                if ($returnTotalCount) {
                    $select = $this->convertSelectQueryToAggregation($select, self::FUNC_COUNT);

                    $sth = $this->queryExecutor->execute($select);
                    $row = $sth->fetch();

                    if (!$row) {
                        return 0;
                    }

                    return (int) $row['value'];
                }

                return $this->collectionFactory->createFromQuery($select);

            case Entity::BELONGS_TO_PARENT:
                $typeKey = $keySet['typeKey'] ?? null;

                if ($typeKey === null) {
                    throw new RuntimeException("Bad relation key.");
                }

                $foreignEntityType = $entity->get($typeKey);
                $foreignEntityId = $entity->get($key);

                if (!$foreignEntityType || !$foreignEntityId) {
                    return null;
                }

                $params['whereClause'][] = [$foreignKey => $foreignEntityId];
                $params['offset'] = 0;
                $params['limit'] = 1;

                $relEntity = $this->entityFactory->create($foreignEntityType);

                $params['from'] = $foreignEntityType;
                $params['fromAlias'] ??= lcfirst($foreignEntityType);

                $select = Select::fromRaw($params);

                if ($returnTotalCount) {
                    $select = $this->convertSelectQueryToAggregation($select, self::FUNC_COUNT);

                    $sth = $this->queryExecutor->execute($select);
                    $row = $sth->fetch();

                    if (!$row) {
                        return 0;
                    }

                    return (int) $row['value'];
                }

                $sth = $this->queryExecutor->execute($select);
                $row = $sth->fetch();

                if (!$row) {
                    return null;
                }

                $this->populateEntityFromRow($relEntity, $row);
                $relEntity->setAsFetched();

                return $relEntity;
        }

        throw new LogicException(
            "Bad type '{$relType}' in definition for relationship '{$relationName}' in '{$entityType}' entity.");
    }

    /**
     * {@inheritDoc}
     */
    public function countRelated(Entity $entity, string $relationName, ?Select $select = null): int
    {
        /** @var int|null $result */
        $result = $this->selectRelatedInternal($entity, $relationName, $select, true);

        return (int) $result;
    }

    /**
     * {@inheritDoc}
     */
    public function relate(
        Entity $entity,
        string $relationName,
        Entity $foreignEntity,
        ?array $columnData = null
    ): bool {

        return $this->addRelation($entity, $relationName, null, $foreignEntity, $columnData);
    }

    /**
     * {@inheritDoc}
     */
    public function unrelate(Entity $entity, string $relationName, Entity $foreignEntity): void
    {
        $this->removeRelation($entity, $relationName, null, false, $foreignEntity);
    }

    /**
     * {@inheritDoc}
     */
    public function relateById(Entity $entity, string $relationName, string $id, ?array $columnData = null): bool
    {
        return $this->addRelation($entity, $relationName, $id, null, $columnData);
    }

    /**
     * {@inheritDoc}
     */
    public function unrelateById(Entity $entity, string $relationName, string $id): void
    {
        $this->removeRelation($entity, $relationName, $id);
    }

    /**
     * Unrelate all related entities.
     */
    public function unrelateAll(Entity $entity, string $relationName): void
    {
        $this->removeRelation($entity, $relationName, null, true);
    }

    /**
     * {@inheritDoc}
     */
    public function updateRelationColumns(
        Entity $entity,
        string $relationName,
        string $id,
        array $columnData
    ): void {

        if (empty($id) || empty($relationName)) {
            throw new RuntimeException("Can't update relation, empty ID or relation name.");
        }

        if (empty($columnData)) {
            return;
        }

        $keySet = $this->helper->getRelationKeys($entity, $relationName);

        $relType =  $entity->getRelationType($relationName);

        switch ($relType) {
            case Entity::MANY_MANY:

                $middleName = ucfirst($this->getRelationParam($entity, $relationName, 'relationName'));

                $nearKey = $keySet['nearKey'] ?? null;
                $distantKey = $keySet['distantKey'] ?? null;

                if ($nearKey === null || $distantKey === null) {
                    throw new RuntimeException("Bad relation key.");
                }

                $update = [];

                foreach ($columnData as $column => $value) {
                    $update[$column] = $value;
                }

                /** @phpstan-ignore-next-line */
                if (empty($update)) {
                    return;
                }

                $where = [
                    $nearKey => $entity->getId(),
                    $distantKey => $id,
                    self::ATTR_DELETED => false,
                ];

                $conditions = $this->getRelationParam($entity, $relationName, 'conditions') ?? [];

                foreach ($conditions as $k => $value) {
                    $where[$k] = $value;
                }

                $query = UpdateBuilder::create()
                    ->in($middleName)
                    ->where($where)
                    ->set($update)
                    ->build();

                $this->queryExecutor->execute($query);

                return;
        }

        throw new LogicException("Relation type '{$relType}' is not supported.");
    }

    /**
     * {@inheritDoc}
     */
    public function getRelationColumn(
        Entity $entity,
        string $relationName,
        string $id,
        string $column
    ): string|int|float|bool|null {

        $type = $entity->getRelationType($relationName);

        if ($type !== Entity::MANY_MANY) {
            throw new RuntimeException("'getRelationColumn' works only on many-to-many relations.");
        }

        if (!$id) {
            throw new RuntimeException("Empty ID passed to 'getRelationColumn'.");
        }

        $middleName = ucfirst($this->getRelationParam($entity, $relationName, 'relationName'));

        $keySet = $this->helper->getRelationKeys($entity, $relationName);

        $nearKey = $keySet['nearKey'] ?? null;
        $distantKey = $keySet['distantKey'] ?? null;

        if ($nearKey === null || $distantKey === null) {
            throw new RuntimeException("Bad relation key.");
        }

        $additionalColumns = $this->getRelationParam($entity, $relationName, 'additionalColumns') ?? [];

        if (!isset($additionalColumns[$column])) {
            return null;
        }

        $columnType = $additionalColumns[$column]['type'] ?? Entity::VARCHAR;

        $where = [
            $nearKey => $entity->getId(),
            $distantKey => $id,
            self::ATTR_DELETED => false,
        ];

        $conditions = $this->getRelationParam($entity, $relationName, 'conditions') ?? [];

        foreach ($conditions as $k => $value) {
            $where[$k] = $value;
        }

        $query = SelectBuilder::create()
            ->from($middleName)
            ->select($column, 'value')
            ->where($where)
            ->build();

        $sth = $this->queryExecutor->execute($query);
        $row = $sth->fetch();

        if (!$row) {
            return null;
        }

        $value = $row['value'];

        if ($columnType == Entity::BOOL) {
            return (bool) $value;
        }

        if ($columnType == Entity::INT) {
            return (int) $value;
        }

        if ($columnType == Entity::FLOAT) {
            return (float) $value;
        }

        return $value;
    }

    /**
     * Mass relate.
     */
    public function massRelate(Entity $entity, string $relationName, Select $select): void
    {
        if (!$entity->hasId()) {
            throw new RuntimeException("Entity w/o ID.");
        }

        if (empty($relationName)) {
            throw new RuntimeException("Empty relation name.");
        }

        $relType = $entity->getRelationType($relationName);

        $foreignEntityType = $this->getRelationParam($entity, $relationName, 'entity');

        if (!$foreignEntityType || !$relType) {
            throw new LogicException(
                "Not appropriate definition for relationship '{$relationName}' in '" .
                $entity->getEntityType() . "' entity.");
        }

        $keySet = $this->helper->getRelationKeys($entity, $relationName);

        switch ($relType) {
            case Entity::MANY_MANY:
                $nearKey = $keySet['nearKey'] ?? null;
                $distantKey = $keySet['distantKey'] ?? null;

                if ($nearKey === null || $distantKey === null) {
                    throw new RuntimeException("Bad relation key.");
                }

                $middleName = ucfirst($this->getRelationParam($entity, $relationName, 'relationName'));

                $valueList = [];
                $valueList[] = $entity->getId();

                $conditions = $this->getRelationParam($entity, $relationName, 'conditions') ?? [];

                $columns = [$nearKey];

                foreach ($conditions as $left => $value) {
                    $columns[] = $left;
                    $valueList[] = $value;
                }

                $columns[] = $distantKey;

                $selectColumns = [];

                foreach ($valueList as $i => $value) {
                   $selectColumns[] = ['VALUE:' . $value, 'v' . strval($i)];
                }

                $selectColumns[] = 'id';

                $subQuery = SelectBuilder::create()
                    ->clone($select)
                    ->select($selectColumns)
                    ->order([])
                    ->build();

                $query = InsertBuilder::create()
                    ->into($middleName)
                    ->columns($columns)
                    ->valuesQuery($subQuery)
                    ->updateSet([self::ATTR_DELETED => false])
                    ->build();

                $this->queryExecutor->execute($query);

                return;
        }

        throw new LogicException("Relation type '{$relType}' is not supported for mass relate.");
    }

    /**
     * @param ?array<string, mixed> $data
     */
    private function addRelation(
        Entity $entity,
        string $relationName,
        ?string $id = null,
        ?Entity $relEntity = null,
        ?array $data = null
    ): bool {

        $entityType = $entity->getEntityType();

        if ($relEntity) {
            $id = $relEntity->getId();
        }

        if (empty($id) || empty($relationName) || !$entity->get('id')) {
            throw new RuntimeException("Can't relate an empty entity or relation name.");
        }

        if (!$entity->hasRelation($relationName)) {
            throw new RuntimeException("Relation '{$relationName}' does not exist in '{$entityType}'.");
        }

        $relType = $entity->getRelationType($relationName);

        if ($relType == Entity::BELONGS_TO_PARENT && !$relEntity) {
            throw new RuntimeException("Bad foreign passed.");
        }

        $foreignEntityType = $this->getRelationParam($entity, $relationName, 'entity');

        if (!$relType || !$foreignEntityType && $relType !== Entity::BELONGS_TO_PARENT) {
            throw new LogicException(
                "Not appropriate definition for relationship {$relationName} in '{$entityType}' entity.");
        }

        if (is_null($relEntity)) {
            $relEntity = $this->entityFactory->create($foreignEntityType);

            $relEntity->set('id', $id);
        }

        $keySet = $this->helper->getRelationKeys($entity, $relationName);

        switch ($relType) {
            case Entity::BELONGS_TO:
                $key = $relationName . 'Id';
                $foreignRelationName = $this->getRelationParam($entity, $relationName, 'foreign');

                if (
                    $foreignRelationName &&
                    $this->getRelationParam($relEntity, $foreignRelationName, 'type') === Entity::HAS_ONE
                ) {
                    $query0 = UpdateBuilder::create()
                        ->in($entityType)
                        ->where([
                            self::ATTR_ID . '!=' => $entity->getId(),
                            $key => $id,
                            self::ATTR_DELETED => false,
                        ])
                        ->set([$key => null])
                        ->build();

                    $this->queryExecutor->execute($query0);
                }

                $entity->set($key, $relEntity->getId());
                $entity->setFetched($key, $relEntity->getId());

                $query = UpdateBuilder::create()
                    ->in($entityType)
                    ->where([
                        self::ATTR_ID => $entity->getId(),
                        self::ATTR_DELETED => false,
                    ])
                    ->set([$key => $relEntity->getId()])
                    ->build();

                $this->queryExecutor->execute($query);

                return true;

            case Entity::BELONGS_TO_PARENT:
                $key = $relationName . 'Id';
                $typeKey = $relationName . 'Type';

                $entity->set($key, $relEntity->getId());
                $entity->set($typeKey, $relEntity->getEntityType());
                $entity->setFetched($key, $relEntity->getId());
                $entity->setFetched($typeKey, $relEntity->getEntityType());

                $query = UpdateBuilder::create()
                    ->in($entityType)
                    ->where([
                        self::ATTR_ID => $entity->getId(),
                        self::ATTR_DELETED => false,
                    ])
                    ->set([
                        $key => $relEntity->getId(),
                        $typeKey => $relEntity->getEntityType(),
                    ])
                    ->build();

                $this->queryExecutor->execute($query);

                return true;

            case Entity::HAS_ONE:
                $foreignKey = $keySet['foreignKey'];

                $selectForCount = SelectBuilder::create()
                    ->from($relEntity->getEntityType())
                    ->where([self::ATTR_ID => $id])
                    ->build();

                if ($this->count($selectForCount) === 0) {
                    return false;
                }

                $query1 = UpdateBuilder::create()
                    ->in($relEntity->getEntityType())
                    ->where([
                        $foreignKey => $entity->getId(),
                        self::ATTR_DELETED => false,
                    ])
                    ->set([$foreignKey => null])
                    ->build();

                $query2 = UpdateBuilder::create()
                    ->in($relEntity->getEntityType())
                    ->where([
                        self::ATTR_ID => $id,
                        self::ATTR_DELETED => false,
                    ])
                    ->set([$foreignKey => $entity->getId()])
                    ->build();

                $this->queryExecutor->execute($query1);
                $this->queryExecutor->execute($query2);

                return true;

            case Entity::HAS_CHILDREN:
            case Entity::HAS_MANY:
                $foreignKey = $keySet['foreignKey'];

                $selectForCount = SelectBuilder::create()
                    ->from($relEntity->getEntityType())
                    ->where([self::ATTR_ID => $id])
                    ->build();

                if ($this->count($selectForCount) === 0) {
                    return false;
                }

                $set = [$foreignKey => $entity->getId()];

                if ($relType == Entity::HAS_CHILDREN) {
                    $foreignType = $keySet['foreignType'] ?? null;

                    if ($foreignType === null) {
                        throw new RuntimeException("Bad relation key.");
                    }

                    $set[$foreignType] = $entity->getEntityType();
                }

                $query = UpdateBuilder::create()
                    ->in($relEntity->getEntityType())
                    ->where([
                        self::ATTR_ID => $id,
                        self::ATTR_DELETED => false,
                    ])
                    ->set($set)
                    ->build();

                $this->queryExecutor->execute($query);

                return true;

            case Entity::MANY_MANY:
                $nearKey = $keySet['nearKey'] ?? null;
                $distantKey = $keySet['distantKey'] ?? null;

                if ($nearKey === null || $distantKey === null) {
                    throw new RuntimeException("Bad relation key.");
                }

                $selectForCount = SelectBuilder::create()
                    ->from($relEntity->getEntityType())
                    ->where([self::ATTR_ID => $id])
                    ->build();

                if ($this->count($selectForCount) === 0) {
                    return false;
                }

                if (!$this->getRelationParam($entity, $relationName, 'relationName')) {
                    throw new LogicException("Bad relation '{$relationName}' in '{$entityType}'.");
                }

                $middleName = ucfirst($this->getRelationParam($entity, $relationName, 'relationName'));
                /** @var array<string, ?scalar> $conditions */
                $conditions = $this->getRelationParam($entity, $relationName, 'conditions') ?? [];

                $data = $data ?? [];

                $where = [
                    $nearKey => $entity->getId(),
                    $distantKey => $relEntity->getId(),
                ];

                foreach ($conditions as $f => $v) {
                    $where[$f] = $v;
                }

                $selectQuery = SelectBuilder::create()
                    ->from($middleName)
                    ->select(['id'])
                    ->where($where)
                    ->withDeleted()
                    ->build();

                $sth = $this->queryExecutor->execute($selectQuery);

                // @todo Leave one INSERT for better performance.

                if ($sth->rowCount() == 0) {
                    $values = $where;
                    $columns = array_keys($values);

                    $update = [self::ATTR_DELETED => false];

                    foreach ($data as $column => $value) {
                        $columns[] = $column;
                        $values[$column] = $value;
                        $update[$column] = $value;
                    }

                    $insertQuery = InsertBuilder::create()
                        ->into($middleName)
                        ->columns($columns)
                        ->values($values)
                        ->updateSet($update)
                        ->build();

                    $this->queryExecutor->execute($insertQuery);

                    return true;
                }

                $update = [self::ATTR_DELETED => false];

                foreach ($data as $column => $value) {
                    $update[$column] = $value;
                }

                $updateQuery = UpdateBuilder::create()
                    ->in($middleName)
                    ->where($where)
                    ->set($update)
                    ->build();

                $this->queryExecutor->execute($updateQuery);

                return true;
        }

        throw new LogicException("Relation type '{$relType}' is not supported.");
    }

    private function removeRelation(
        Entity $entity,
        string $relationName,
        ?string $id = null,
        bool $all = false,
        ?Entity $relEntity = null
    ): void {

        if ($relEntity) {
            $id = $relEntity->getId();
        }

        $entityType = $entity->getEntityType();

        if (empty($id) && empty($all) || empty($relationName)) {
            throw new RuntimeException("Can't unrelate an empty entity or relation name.");
        }

        if (!$entity->hasRelation($relationName)) {
            throw new RuntimeException("Relation '{$relationName}' does not exist in '{$entityType}'.");
        }

        $relType = $entity->getRelationType($relationName);

        if ($relType === Entity::BELONGS_TO_PARENT && !$relEntity && !$all) {
            throw new RuntimeException("Bad foreign passed.");
        }

        $foreignEntityType = $this->getRelationParam($entity, $relationName, 'entity');

        if ($relType === Entity::BELONGS_TO_PARENT && $relEntity) {
            $foreignEntityType = $relEntity->getEntityType();
        }

        if (!$relType || !$foreignEntityType && $relType !== Entity::BELONGS_TO_PARENT) {
            throw new LogicException(
                "Not appropriate definition for relationship {$relationName} in " .
                $entity->getEntityType() . " entity.");
        }

        if (is_null($relEntity) && $relType !== Entity::BELONGS_TO_PARENT) {
            $relEntity = $this->entityFactory->create($foreignEntityType);

            $relEntity->set('id', $id);
        }

        $keySet = $this->helper->getRelationKeys($entity, $relationName);

        switch ($relType) {
            case Entity::BELONGS_TO:
            case Entity::BELONGS_TO_PARENT:
                $key = $relationName . 'Id';

                $update = [
                    $key => null,
                ];

                $where = [
                    'id' => $entity->getId(),
                ];

                if (!$all) {
                    $where[$key] = $id;
                }

                $entity->set($key, null);
                $entity->setFetched($key, null);

                if ($relType === Entity::BELONGS_TO_PARENT) {
                    $typeKey = $relationName . 'Type';
                    $update[$typeKey] = null;

                    if (!$all) {
                        $where[$typeKey] = $foreignEntityType;
                    }

                    $entity->set($typeKey, null);
                    $entity->setFetched($typeKey, null);
                }

                $where[self::ATTR_DELETED] = false;

                $query = UpdateBuilder::create()
                    ->in($entityType)
                    ->where($where)
                    ->set($update)
                    ->build();

                $this->queryExecutor->execute($query);

                return;

            case Entity::HAS_ONE:
            case Entity::HAS_MANY:
            case Entity::HAS_CHILDREN:
                $foreignKey = $keySet['foreignKey'];

                $update = [
                    $foreignKey => null,
                ];

                $where = [];

                if (!$all && $relType !== Entity::HAS_ONE) {
                    $where[self::ATTR_ID] = $id;
                }

                $where[$foreignKey] = $entity->getId();

                if ($relType === Entity::HAS_CHILDREN) {
                    $foreignType = $keySet['foreignType'] ?? null;

                    if ($foreignType === null) {
                        throw new RuntimeException("Bad relation key.");
                    }

                    $where[$foreignType] = $entity->getEntityType();
                    $update[$foreignType] = null;
                }

                $where[self::ATTR_DELETED] = false;

                /** @var Entity $relEntity */

                $query = UpdateBuilder::create()
                    ->in($relEntity->getEntityType())
                    ->where($where)
                    ->set($update)
                    ->build();

                $this->queryExecutor->execute($query);

                return;

            case Entity::MANY_MANY:
                $nearKey = $keySet['nearKey'] ?? null;
                $distantKey = $keySet['distantKey'] ?? null;

                if ($nearKey === null || $distantKey === null) {
                    throw new RuntimeException("Bad relation key.");
                }

                if (!$this->getRelationParam($entity, $relationName, 'relationName')) {
                    throw new LogicException("Bad relation '{$relationName}' in '{$entityType}'.");
                }

                $middleName = ucfirst($this->getRelationParam($entity, $relationName, 'relationName'));
                $conditions = $this->getRelationParam($entity, $relationName, 'conditions') ?? [];

                $where = [$nearKey => $entity->getId()];

                if (!$all) {
                    $where[$distantKey] = $id;
                }

                foreach ($conditions as $f => $v) {
                    $where[$f] = $v;
                }

                $query = UpdateBuilder::create()
                    ->in($middleName)
                    ->where($where)
                    ->set([self::ATTR_DELETED => true])
                    ->build();

                $this->queryExecutor->execute($query);

                return;
        }

        throw new LogicException("Relation type '{$relType}' is not supported for un-relate.");
    }

    /**
     * Insert an entity into DB.
     *
     * @todo Set 'id' if auto-increment (as fetched).
     */
    public function insert(Entity $entity): void
    {
        $this->insertInternal($entity);
    }

    /**
     * Insert an entity into DB, on duplicate key update specified attributes.
     */
    public function insertOnDuplicateUpdate(Entity $entity, array $onDuplicateUpdateAttributeList): void
    {
        $this->insertInternal($entity, $onDuplicateUpdateAttributeList);
    }

    /**
     * @param string[]|null $onDuplicateUpdateAttributeList
     */
    private function insertInternal(Entity $entity, ?array $onDuplicateUpdateAttributeList = null): void
    {
        $update = null;

        if ($onDuplicateUpdateAttributeList !== null && count($onDuplicateUpdateAttributeList)) {
            $update = $this->getInsertOnDuplicateSetMap($entity, $onDuplicateUpdateAttributeList);
        }

        $query = InsertBuilder::create()
            ->into($entity->getEntityType())
            ->columns($this->getInsertColumnList($entity))
            ->values($this->getInsertValueMap($entity))
            ->updateSet($update ?? [])
            ->build();

        $this->queryExecutor->execute($query);

        if ($this->getAttributeParam($entity, 'id', 'autoincrement')) {
            $this->setLastInsertIdWithinConnection($entity);
        }
    }

    private function setLastInsertIdWithinConnection(Entity $entity): void
    {
        $id = $this->pdo->lastInsertId();

        if ($id === '' || $id === null) { /** @phpstan-ignore-line */
            return;
        }

        if ($entity->getAttributeType('id') === Entity::INT) {
            $id = (int) $id;
        }

        $entity->set('id', $id);
        $entity->setFetched('id', $id);
    }

    /**
     * {@inheritdoc}
     */
    public function massInsert(Collection $collection): void
    {
        $count = is_countable($collection) ?
            count($collection) :
            iterator_count($collection);

        if ($count === 0) {
            return;
        }

        $values = [];

        $entityType = null;
        $firstEntity = null;

        foreach ($collection as $entity) {
            if ($firstEntity === null) {
                $firstEntity = $entity;
                $entityType = $entity->getEntityType();
            }

            $values[] = $this->getInsertValueMap($entity);
        }

        if (!$entityType) {
            throw new LogicException();
        }

        /** @var Entity $firstEntity */

        $query = InsertBuilder::create()
            ->into($entityType)
            ->columns($this->getInsertColumnList($firstEntity))
            ->values($values)
            ->build();

        $this->queryExecutor->execute($query);
    }

    /**
     * @return string[]
     */
    private function getInsertColumnList(Entity $entity): array
    {
        $columnList = [];

        $dataList = $this->toValueMap($entity);

        foreach ($dataList as $attribute => $value) {
            $columnList[] = $attribute;
        }

        return $columnList;
    }

    /**
     * @return array<string, ?scalar>
     */
    private function getInsertValueMap(Entity $entity): array
    {
        $map = [];

        foreach ($this->toValueMap($entity) as $attribute => $value) {
            $type = $entity->getAttributeType($attribute);

            $map[$attribute] = $this->prepareValueForInsert($type, $value);
        }

        return $map;
    }

    /**
     * @param string[] $attributeList
     * @return string[]
     */
    private function getInsertOnDuplicateSetMap(Entity $entity, array $attributeList)
    {
        $list = [];

        foreach ($attributeList as $attribute) {
            $type = $entity->getAttributeType($attribute);

            $list[$attribute] = $this->prepareValueForInsert($type, $entity->get($attribute));
        }

        return $list;
    }

    /**
     * @return array<string, mixed>
     */
    private function getValueMapForUpdate(Entity $entity): array
    {
        $valueMap = [];

        foreach ($this->toValueMap($entity) as $attribute => $value) {
            if ($attribute == 'id') {
                continue;
            }

            $type = $entity->getAttributeType($attribute);

            if ($type == Entity::FOREIGN) {
                continue;
            }

            if (!$entity->isAttributeChanged($attribute)) {
                continue;
            }

            $valueMap[$attribute] = $this->prepareValueForInsert($type, $value);
        }

        return $valueMap;
    }

    /**
     * {@inheritdoc}
     */
    public function update(Entity $entity): void
    {
        $valueMap = $this->getValueMapForUpdate($entity);

        if (count($valueMap) == 0) {
            return;
        }

        $query = UpdateBuilder::create()
            ->in($entity->getEntityType())
            ->set($valueMap)
            ->where([
                self::ATTR_ID => $entity->getId(),
                self::ATTR_DELETED => false,
            ])
            ->build();

        $this->queryExecutor->execute($query);
    }

    private function prepareValueForInsert(?string $type, mixed $value): mixed
    {
        if ($type == Entity::JSON_ARRAY && is_array($value)) {
            $value = json_encode($value, \JSON_UNESCAPED_UNICODE);
        }
        else if ($type == Entity::JSON_OBJECT && (is_array($value) || $value instanceof stdClass)) {
            $value = json_encode($value, \JSON_UNESCAPED_UNICODE);
        }
        else {
            if (is_array($value) || is_object($value)) {
                return null;
            }
        }

        return $value;
    }

    /**
     * Delete an entity from DB.
     */
    public function deleteFromDb(string $entityType, string $id, bool $onlyDeleted = false): void
    {
        if (empty($entityType) || empty($id)) {
            throw new RuntimeException("Can't delete an empty entity type or ID from DB.");
        }

        $whereClause = [self::ATTR_ID => $id];

        if ($onlyDeleted) {
            $whereClause[self::ATTR_DELETED] = true;
        }

        $query = DeleteBuilder::create()
            ->from($entityType)
            ->where($whereClause)
            ->build();

        $this->queryExecutor->execute($query);
    }

    /**
     * Unmark an entity as deleted in DB.
     */
    public function restoreDeleted(string $entityType, string $id): void
    {
        if (empty($entityType) || empty($id)) {
            throw new RuntimeException("Can't restore an empty entity type or ID.");
        }

        $query = UpdateBuilder::create()
            ->in($entityType)
            ->where([self::ATTR_ID => $id])
            ->set([self::ATTR_DELETED => false])
            ->build();

        $this->queryExecutor->execute($query);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(Entity $entity): void
    {
        $entity->set(self::ATTR_DELETED, true);

        $this->update($entity);
    }

    /**
     * @return array<string, mixed>
     */
    private function toValueMap(Entity $entity, bool $onlyStorable = true): array
    {
        $data = [];

        foreach ($entity->getAttributeList() as $attribute) {
            if (!$entity->has($attribute)) {
                continue;
            }

            if (
                $onlyStorable &&
                (
                    $this->getAttributeParam($entity, $attribute, 'notStorable') ||
                    $this->getAttributeParam($entity, $attribute, 'autoincrement') ||
                    (
                        $this->getAttributeParam($entity, $attribute, 'source') &&
                        $this->getAttributeParam($entity, $attribute, 'source') !== 'db'
                    )
                )
            ) {
                continue;
            }

            if ($onlyStorable && $entity->getAttributeType($attribute) === Entity::FOREIGN) {
                continue;
            }

            $data[$attribute] = $entity->get($attribute);
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function populateEntityFromRow(Entity $entity, $data): void
    {
        $entity->set($data);
    }

    /**
     * @param array<mixed> $select
     * @return array<mixed>
     */
    private function getModifiedSelectForManyToMany(Entity $entity, string $relationName, array $select): array
    {
        $additionalSelect = $this->getManyManyAdditionalSelect($entity, $relationName);

        if (!count($additionalSelect)) {
            return $select;
        }

        if (empty($select)) {
            $select = ['*'];
        }

        if ($select[0] === '*') {
            return array_merge($select, $additionalSelect);
        }

        foreach ($additionalSelect as $item) {
            $index = array_search($item[1], $select);

            if ($index !== false) {
                $select[$index] = $item;
            }
        }

        return $select;
    }

    /**
     * @param array<string, mixed>|null $conditions
     * @return array{string, string, array<string|int, mixed>}
     */
    private function getManyManyJoin(Entity $entity, string $relationName, ?array $conditions = null): array
    {
        $middleName = $this->getRelationParam($entity, $relationName, 'relationName');

        $keySet = $this->helper->getRelationKeys($entity, $relationName);

        $key = $keySet['key'];
        $foreignKey = $keySet['foreignKey'];
        $nearKey = $keySet['nearKey'] ?? null;
        $distantKey = $keySet['distantKey'] ?? null;

        if (!$middleName) {
            throw new RuntimeException("No 'relationName' parameter for '{$relationName}' relationship.");
        }

        if ($nearKey === null || $distantKey === null) {
            throw new RuntimeException("Bad relation key.");
        }

        $alias = lcfirst($middleName);

        $join = [
            ucfirst($middleName),
            $alias,
            [
                "{$distantKey}:" => $foreignKey,
                "{$nearKey}" => $entity->get($key),
                self::ATTR_DELETED => false,
            ],
        ];

        $conditions = $conditions ?? [];

        $relationConditions = $this->getRelationParam($entity, $relationName, 'conditions');

        if ($relationConditions) {
            $conditions = array_merge($conditions, $relationConditions);
        }

        $join[2] = array_merge($join[2], $conditions);

        return $join;
    }

    /**
     * @return array<array{string, string}>
     */
    private function getManyManyAdditionalSelect(Entity $entity, string $relationName): array
    {
        $foreign = $this->getRelationParam($entity, $relationName, 'foreign');
        $foreignEntityType = $this->getRelationParam($entity, $relationName, 'entity');

        $middleName = lcfirst($this->getRelationParam($entity, $relationName, 'relationName'));

        if (!$foreign || !$foreignEntityType) {
            return [];
        }

        $foreignEntity = $this->entityFactory->create($foreignEntityType);

        $map = $this->getRelationParam($foreignEntity, $foreign, 'columnAttributeMap') ?? [];

        $select = [];

        foreach ($map as $column => $attribute) {
            $select[] = [
                $middleName . '.' . $column,
                $attribute
            ];
        }

        return $select;
    }

    /**
     * @return mixed
     */
    private function getAttributeParam(Entity $entity, string $attribute, string $param)
    {
        if ($entity instanceof BaseEntity) {
            return $entity->getAttributeParam($attribute, $param);
        }

        $entityDefs = $this->metadata
            ->getDefs()
            ->getEntity($entity->getEntityType());

        if (!$entityDefs->hasAttribute($attribute)) {
            return null;
        }

        return $entityDefs->getAttribute($attribute)->getParam($param);
    }

    private function getRelationParam(Entity $entity, string $relation, string $param): mixed
    {
        if ($entity instanceof BaseEntity) {
            return $entity->getRelationParam($relation, $param);
        }

        $entityDefs = $this->metadata
            ->getDefs()
            ->getEntity($entity->getEntityType());

        if (!$entityDefs->hasRelation($relation)) {
            return null;
        }

        return $entityDefs->getRelation($relation)->getParam($param);
    }
}
