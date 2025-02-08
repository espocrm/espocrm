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

namespace Espo\ORM\Mapper;

use Espo\ORM\Defs\Params\AttributeParam;
use Espo\ORM\Defs\Params\RelationParam;
use Espo\ORM\Entity;
use Espo\ORM\BaseEntity;
use Espo\ORM\Collection;
use Espo\ORM\Name\Attribute;
use Espo\ORM\Query\DeleteBuilder;
use Espo\ORM\Query\InsertBuilder;
use Espo\ORM\Query\Part\Selection;
use Espo\ORM\Query\SelectBuilder;
use Espo\ORM\Executor\QueryExecutor;
use Espo\ORM\Query\UpdateBuilder;
use Espo\ORM\SthCollection;
use Espo\ORM\EntityFactory;
use Espo\ORM\CollectionFactory;
use Espo\ORM\Metadata;
use Espo\ORM\Query\Select;

use Espo\ORM\Type\AttributeType;
use PDO;
use stdClass;
use LogicException;
use RuntimeException;

use const JSON_UNESCAPED_UNICODE;

/**
 * Abstraction for DB. Mapping of Entity to DB. Supposed to be used only internally. Use repositories instead.
 *
 * @todo Use entityDefs. Don't use methods of BaseEntity.
 */
class BaseMapper implements RDBMapper
{
    private const ATTR_ID = Attribute::ID;
    private const ATTR_DELETED = Attribute::DELETED;
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
        return (int) $this->aggregate($select, self::FUNC_COUNT, Attribute::ID);
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
        string $aggregationBy = Attribute::ID
    ): Select {

        $expression = "$aggregation:($aggregationBy)";

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

        $expression = "$aggregation:(asq.$aggregationBy)";

        $subQueryBuilder = SelectBuilder::create()
            ->clone($selectAggregation)
            ->select([])
            ->select(Attribute::ID);

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

        $builder = new SelectBuilder();

        if ($select) {
            $params = $select->getRaw();

            $builder->clone($select);
        }

        $entityType = $entity->getEntityType();
        $relType = $entity->getRelationType($relationName);

        $relEntityType = $this->getRelationParam($entity, $relationName, RelationParam::ENTITY);

        $relEntity = null;

        if (!$relType) {
            throw new LogicException(
                "Missing 'type' in definition for relationship '$relationName' in {entityType} entity.");
        }

        if ($relType !== Entity::BELONGS_TO_PARENT) {
            if (!$relEntityType) {
                throw new LogicException(
                    "Missing 'entity' in definition for relationship '$relationName' in {entityType} entity.");
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

                $alias = $select?->getFromAlias() ?? lcfirst($relEntityType);

                $builder
                    ->from($relEntityType, $alias)
                    ->limit(0, 1)
                    ->where([$foreignKey => $entity->get($key)]);

                $select = $builder->build();

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

                $alias = $select?->getFromAlias() ?? lcfirst($relEntityType);

                $builder
                    ->from($relEntityType, $alias)
                    ->where([$foreignKey => $entity->get($key)]);

                if ($relType == Entity::HAS_CHILDREN) {
                    $foreignType = $keySet['foreignType'] ?? null;

                    if ($foreignType === null) {
                        throw new RuntimeException("Bad relation key.");
                    }

                    $builder->where([$foreignType => $entity->getEntityType()]);
                }

                $relConditions = $this->getRelationParam($entity, $relationName, RelationParam::CONDITIONS);

                if ($relConditions) {
                    $builder->where($relConditions);
                }

                if ($relType == Entity::HAS_ONE) {
                    $builder->limit(0, 1);
                }

                $select = $builder->build();

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

                $alias = $select?->getFromAlias() ?? lcfirst($relEntityType);

                $join = $this->getManyManyJoin($entity, $relationName);

                $selections = $this->getModifiedSelectForManyToMany(
                    $entity,
                    $relationName,
                    $select ? $select->getSelect() : []
                );

                $builder
                    ->from($relEntityType, $alias)
                    ->join($join[0], $join[1], $join[2])
                    ->select($selections);

                $select = $builder->build();

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

                $alias = $select?->getFromAlias() ?? lcfirst($foreignEntityType);

                $builder
                    ->from($foreignEntityType, $alias)
                    ->limit(0, 1)
                    ->where([$foreignKey => $foreignEntityId]);

                $relEntity = $this->entityFactory->create($foreignEntityType);

                $select = $builder->build();

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
            "Bad type '$relType' in definition for relationship '$relationName' in '$entityType' entity.");
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

                $middleName = ucfirst($this->getRelationParam($entity, $relationName, RelationParam::RELATION_NAME));

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

                $conditions = $this->getRelationParam($entity, $relationName, RelationParam::CONDITIONS) ?? [];

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

        throw new LogicException("Relation type '$relType' is not supported.");
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

        $middleName = ucfirst($this->getRelationParam($entity, $relationName, RelationParam::RELATION_NAME));

        $keySet = $this->helper->getRelationKeys($entity, $relationName);

        $nearKey = $keySet['nearKey'] ?? null;
        $distantKey = $keySet['distantKey'] ?? null;

        if ($nearKey === null || $distantKey === null) {
            throw new RuntimeException("Bad relation key.");
        }

        $additionalColumns = $this->getRelationParam($entity, $relationName, RelationParam::ADDITIONAL_COLUMNS) ?? [];

        if (!isset($additionalColumns[$column])) {
            return null;
        }

        $columnType = $additionalColumns[$column][AttributeParam::TYPE] ?? Entity::VARCHAR;

        $where = [
            $nearKey => $entity->getId(),
            $distantKey => $id,
            self::ATTR_DELETED => false,
        ];

        $conditions = $this->getRelationParam($entity, $relationName, RelationParam::CONDITIONS) ?? [];

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

        $foreignEntityType = $this->getRelationParam($entity, $relationName, RelationParam::ENTITY);

        if (!$foreignEntityType || !$relType) {
            throw new LogicException(
                "Not appropriate definition for relationship '$relationName' in '" .
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

                $middleName = ucfirst($this->getRelationParam($entity, $relationName, RelationParam::RELATION_NAME));

                $valueList = [];
                $valueList[] = $entity->getId();

                $conditions = $this->getRelationParam($entity, $relationName, RelationParam::CONDITIONS) ?? [];

                $columns = [$nearKey];

                foreach ($conditions as $left => $value) {
                    $columns[] = $left;
                    $valueList[] = $value;
                }

                $columns[] = $distantKey;

                $selectColumns = [];

                foreach ($valueList as $i => $value) {
                   $selectColumns[] = ["VALUE:$value", "v$i"];
                }

                $selectColumns[] = Attribute::ID;

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

        throw new LogicException("Relation type '$relType' is not supported for mass relate.");
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

        if (empty($id) || empty($relationName) || !$entity->get(Attribute::ID)) {
            throw new RuntimeException("Can't relate an empty entity or relation name.");
        }

        if (!$entity->hasRelation($relationName)) {
            throw new RuntimeException("Relation '$relationName' does not exist in '$entityType'.");
        }

        $relType = $entity->getRelationType($relationName);

        if ($relType == Entity::BELONGS_TO_PARENT && !$relEntity) {
            throw new RuntimeException("Bad foreign passed.");
        }

        $foreignEntityType = $this->getRelationParam($entity, $relationName, RelationParam::ENTITY);

        if (!$relType || !$foreignEntityType && $relType !== Entity::BELONGS_TO_PARENT) {
            throw new LogicException(
                "Not appropriate definition for relationship $relationName in '$entityType' entity.");
        }

        if (is_null($relEntity)) {
            $relEntity = $this->entityFactory->create($foreignEntityType);

            $relEntity->set(Attribute::ID, $id);
        }

        $keySet = $this->helper->getRelationKeys($entity, $relationName);

        switch ($relType) {
            case Entity::BELONGS_TO:
                $key = $relationName . 'Id';
                $foreignRelationName = $this->getRelationParam($entity, $relationName, RelationParam::FOREIGN);

                if (
                    $foreignRelationName &&
                    $this->getRelationParam($relEntity, $foreignRelationName, RelationParam::TYPE) === Entity::HAS_ONE
                ) {
                    $where = [
                        self::ATTR_ID . '!=' => $entity->getId(),
                        $key => $id,
                    ];

                    if (self::hasDeletedAttribute($entity)) {
                        $where[self::ATTR_DELETED] = false;
                    }

                    $query0 = UpdateBuilder::create()
                        ->in($entityType)
                        ->where($where)
                        ->set([$key => null])
                        ->build();

                    $this->queryExecutor->execute($query0);
                }

                $entity->set($key, $relEntity->getId());
                $entity->setFetched($key, $relEntity->getId());

                $where = [self::ATTR_ID => $entity->getId()];

                if (self::hasDeletedAttribute($entity)) {
                    $where[self::ATTR_DELETED] = false;
                }

                $query = UpdateBuilder::create()
                    ->in($entityType)
                    ->where($where)
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

                $where = [self::ATTR_ID => $entity->getId()];

                if (self::hasDeletedAttribute($entity)) {
                    $where[self::ATTR_DELETED] = false;
                }

                $query = UpdateBuilder::create()
                    ->in($entityType)
                    ->where($where)
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

                $where1 = [$foreignKey => $entity->getId()];
                $where2 = [self::ATTR_ID => $id];

                if (self::hasDeletedAttribute($relEntity)) {
                    $where1[self::ATTR_DELETED] = false;
                    $where2[self::ATTR_DELETED] = false;
                }

                $query1 = UpdateBuilder::create()
                    ->in($relEntity->getEntityType())
                    ->where($where1)
                    ->set([$foreignKey => null])
                    ->build();

                $query2 = UpdateBuilder::create()
                    ->in($relEntity->getEntityType())
                    ->where($where2)
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

                $where = [self::ATTR_ID => $id];

                if (self::hasDeletedAttribute($relEntity)) {
                    $where[self::ATTR_DELETED] = false;
                }

                $query = UpdateBuilder::create()
                    ->in($relEntity->getEntityType())
                    ->where($where)
                    ->set($set)
                    ->build();

                $sth = $this->queryExecutor->execute($query);

                if ($sth->rowCount() === 0) {
                    return false;
                }

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

                if (!$this->getRelationParam($entity, $relationName, RelationParam::RELATION_NAME)) {
                    throw new LogicException("Bad relation '$relationName' in '$entityType'.");
                }

                $middleName = ucfirst($this->getRelationParam($entity, $relationName, RelationParam::RELATION_NAME));
                /** @var array<string, ?scalar> $conditions */
                $conditions = $this->getRelationParam($entity, $relationName, RelationParam::CONDITIONS) ?? [];

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
                    ->select([Attribute::ID])
                    ->where($where)
                    ->withDeleted()
                    ->build();

                $sth = $this->queryExecutor->execute($selectQuery);

                // @todo Leave one INSERT for better performance.

                if ($sth->rowCount() === 0) {
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

                    $sth = $this->queryExecutor->execute($insertQuery);

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

                $sth = $this->queryExecutor->execute($updateQuery);

                if ($sth->rowCount() === 0) {
                    return false;
                }

                return true;
        }

        throw new LogicException("Relation type '$relType' is not supported.");
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
            throw new RuntimeException("Relation '$relationName' does not exist in '$entityType'.");
        }

        $relType = $entity->getRelationType($relationName);

        if ($relType === Entity::BELONGS_TO_PARENT && !$relEntity && !$all) {
            throw new RuntimeException("Bad foreign passed.");
        }

        $foreignEntityType = $this->getRelationParam($entity, $relationName, RelationParam::ENTITY);

        if ($relType === Entity::BELONGS_TO_PARENT && $relEntity) {
            $foreignEntityType = $relEntity->getEntityType();
        }

        if (!$relType || !$foreignEntityType && $relType !== Entity::BELONGS_TO_PARENT) {
            throw new LogicException(
                "Not appropriate definition for relationship $relationName in " .
                $entity->getEntityType() . " entity.");
        }

        if (is_null($relEntity) && $relType !== Entity::BELONGS_TO_PARENT) {
            $relEntity = $this->entityFactory->create($foreignEntityType);

            $relEntity->set(Attribute::ID, $id);
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
                    Attribute::ID => $entity->getId(),
                ];

                if (!$all) {
                    $where[$key] = $id;
                }

                /** @noinspection PhpRedundantOptionalArgumentInspection */
                $entity->set($key, null);
                $entity->setFetched($key, null);

                if ($relType === Entity::BELONGS_TO_PARENT) {
                    $typeKey = $relationName . 'Type';
                    $update[$typeKey] = null;

                    if (!$all) {
                        $where[$typeKey] = $foreignEntityType;
                    }

                    /** @noinspection PhpRedundantOptionalArgumentInspection */
                    $entity->set($typeKey, null);
                    $entity->setFetched($typeKey, null);
                }

                if (self::hasDeletedAttribute($entity)) {
                    $where[self::ATTR_DELETED] = false;
                }

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

                /** @var Entity $relEntity */

                if (self::hasDeletedAttribute($relEntity)) {
                    $where[self::ATTR_DELETED] = false;
                }

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

                if (!$this->getRelationParam($entity, $relationName, RelationParam::RELATION_NAME)) {
                    throw new LogicException("Bad relation '$relationName' in '$entityType'.");
                }

                $middleName = ucfirst($this->getRelationParam($entity, $relationName, RelationParam::RELATION_NAME));
                $conditions = $this->getRelationParam($entity, $relationName, RelationParam::CONDITIONS) ?? [];

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

        throw new LogicException("Relation type '$relType' is not supported for un-relate.");
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

        if ($this->getAttributeParam($entity, Attribute::ID, AttributeParam::AUTOINCREMENT)) {
            $this->setLastInsertIdWithinConnection($entity);
        }
    }

    private function setLastInsertIdWithinConnection(Entity $entity): void
    {
        $id = $this->pdo->lastInsertId();

        /** @noinspection PhpConditionAlreadyCheckedInspection */
        if ($id === '' || $id === null) { /** @phpstan-ignore-line */
            return;
        }

        if ($entity->getAttributeType(Attribute::ID) === Entity::INT) {
            $id = (int) $id;
        }

        $entity->set(Attribute::ID, $id);
        $entity->setFetched(Attribute::ID, $id);
    }

    /**
     * {@inheritdoc}
     */
    public function massInsert(Collection $collection): void
    {
        /** @noinspection PhpParamsInspection */
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
            if ($attribute == Attribute::ID) {
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

        $where = [self::ATTR_ID => $entity->getId()];

        if (self::hasDeletedAttribute($entity)) {
            $where[self::ATTR_DELETED] = false;
        }

        $query = UpdateBuilder::create()
            ->in($entity->getEntityType())
            ->set($valueMap)
            ->where($where)
            ->build();

        $this->queryExecutor->execute($query);
    }

    private function prepareValueForInsert(?string $type, mixed $value): mixed
    {
        if ($type == Entity::JSON_ARRAY && is_array($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        } else if ($type == Entity::JSON_OBJECT && (is_array($value) || $value instanceof stdClass)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        } else {
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
        if (!self::hasDeletedAttribute($entity)) {
            $this->deleteFromDb($entity->getEntityType(), $entity->getId());

            return;
        }

        $entity->set(self::ATTR_DELETED, true);
        $this->update($entity);
    }

    /**
     * @return array<string, mixed>
     * @noinspection PhpSameParameterValueInspection
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
                    $this->getAttributeParam($entity, $attribute, AttributeParam::NOT_STORABLE) ||
                    $this->getAttributeParam($entity, $attribute, AttributeParam::AUTOINCREMENT) ||
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
     * @param Selection[] $select
     * @return array<int, Selection|array{string, string}>
     */
    private function getModifiedSelectForManyToMany(Entity $entity, string $relationName, array $select): array
    {
        $additionalSelect = $this->getManyManyAdditionalSelect($entity, $relationName);

        if ($additionalSelect === []) {
            return $select;
        }

        if ($select === []) {
            $select[] = Selection::fromString('*');
        }

        if ($select[0]->getExpression()->getValue() === '*') {
            return array_merge($select, $additionalSelect);
        }

        foreach ($additionalSelect as $item) {
            $index = false;

            foreach ($select as $i => $it) {
                if (
                    $it instanceof Selection &&
                    $it->getExpression()->getValue() === $item[1]
                ) {
                    $index = $i;

                    break;
                }
            }

            if ($index !== false) {
                $select[$index] = $item;
            }
        }

        return $select;
    }

    /**
     * @param array<string, mixed>|null $conditions
     * @return array{string, string, array<string|int, mixed>}
     * @noinspection PhpSameParameterValueInspection
     */
    private function getManyManyJoin(Entity $entity, string $relationName, ?array $conditions = null): array
    {
        $middleName = $this->getRelationParam($entity, $relationName, RelationParam::RELATION_NAME);

        $keySet = $this->helper->getRelationKeys($entity, $relationName);

        $key = $keySet['key'];
        $foreignKey = $keySet['foreignKey'];
        $nearKey = $keySet['nearKey'] ?? null;
        $distantKey = $keySet['distantKey'] ?? null;

        if (!$middleName) {
            throw new RuntimeException("No 'relationName' parameter for '$relationName' relationship.");
        }

        if ($nearKey === null || $distantKey === null) {
            throw new RuntimeException("Bad relation key.");
        }

        $alias = lcfirst($middleName);

        $where = [
            "$distantKey:" => $foreignKey,
            $nearKey => $entity->get($key),
            self::ATTR_DELETED => false, // @todo Check 'deleted' exists.
        ];

        $conditions = $conditions ?? [];

        $relationConditions = $this->getRelationParam($entity, $relationName, RelationParam::CONDITIONS);

        if ($relationConditions) {
            $conditions = array_merge($conditions, $relationConditions);
        }

        $where = array_merge($where, $conditions);

        return [ucfirst($middleName), $alias, $where];
    }

    /**
     * @return array<array{string, string}>
     */
    private function getManyManyAdditionalSelect(Entity $entity, string $relationName): array
    {
        $foreign = $this->getRelationParam($entity, $relationName, RelationParam::FOREIGN);
        $foreignEntityType = $this->getRelationParam($entity, $relationName, RelationParam::ENTITY);

        $middleName = lcfirst($this->getRelationParam($entity, $relationName, RelationParam::RELATION_NAME));

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

    private static function hasDeletedAttribute(Entity $entity): bool
    {
        return $entity->hasAttribute(self::ATTR_DELETED) &&
            $entity->getAttributeType(self::ATTR_DELETED) === AttributeType::BOOL;
    }
}
