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

namespace Espo\ORM\Mapper;

use Espo\ORM\{
    Entity,
    Collection,
    SthCollection,
    EntityFactory,
    CollectionFactory,
    Metadata,
    SqlExecutor,
    QueryComposer\QueryComposer,
    QueryParams\Select,
    QueryParams\Update,
    QueryParams\Delete,
    QueryParams\Insert,
};

use PDO;
use StdClass;
use LogicException;
use RuntimeException;

/**
 * Abstraction for DB. Mapping of Entity to DB. Supposed to be used only internally. Use repositories instead.
 */
class BaseMapper implements Mapper
{
    const ATTRIBUTE_DELETED = 'deleted';

    protected $fieldsMapCache = [];

    protected $aliasesCache = [];

    protected $pdo;
    protected $entityFactroy;
    protected $collectionFactory;
    protected $queryComposer;
    protected $metadata;
    protected $sqlExecutor;

    public function __construct(
        PDO $pdo,
        EntityFactory $entityFactory,
        CollectionFactory $collectionFactory,
        QueryComposer $queryComposer,
        Metadata $metadata,
        SqlExecutor $sqlExecutor
    ) {
        $this->pdo = $pdo;
        $this->queryComposer = $queryComposer;
        $this->entityFactory = $entityFactory;
        $this->collectionFactory = $collectionFactory;
        $this->metadata = $metadata;
        $this->sqlExecutor = $sqlExecutor;

        $this->helper = new Helper($metadata);
    }

    /**
     * {@inheritdoc}
     */
    public function selectOne(Select $select) : ?Entity
    {
        $entityType = $select->getFrom();

        $entity = $this->entityFactory->create($entityType);

        $sql = $this->queryComposer->compose($select);

        $sth = $this->executeSql($sql);

        while ($row = $sth->fetch()) {
            $this->populateEntityFromRow($entity, $row);

            $entity->setAsFetched();

            return $entity;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function count(Select $select) : int
    {
        return (int) $this->aggregate($select, 'COUNT', 'id');
    }

    public function max(Select $select, string $attribute)
    {
         $value =  $this->aggregate($select, 'MAX', $attribute);

         return $this->castToNumber($value);
    }

    public function min(Select $select, string $attribute)
    {
        $value = $this->aggregate($select, 'MIN', $attribute);

        return $this->castToNumber($value);
    }

    public function sum(Select $select, string $attribute)
    {
        $value = $this->aggregate($select, 'SUM', $attribute);

        return $this->castToNumber($value);
    }

    protected function castToNumber($value)
    {
        if (is_int($value) || is_float($value)) {
            return $value;
        }

        if (!is_string($value)) {
            return 0;
        }

        if (strpos($value, '.') !== false) {
            return (float) $value;
        }

        return (int) $value;
    }

    /**
     * {@inheritdoc}
     * @todo Change return type to SthCollection once PHP 7.4 is a min version.
     */
    public function select(Select $select) : Collection
    {
        $entityType = $select->getFrom();

        $sql = $this->queryComposer->compose($select);

        return $this->selectBySqlInternal($entityType, $sql);
    }

    /**
     * Select entities from DB by a SQL query.
     */
    public function selectBySql(string $entityType, string $sql) : SthCollection
    {
        return $this->selectBySqlInternal($entityType, $sql);
    }

    protected function selectBySqlInternal(string $entityType, string $sql) : SthCollection
    {
        return $this->collectionFactory->createFromSql($entityType, $sql);
    }

    public function aggregate(Select $select, string $aggregation, string $aggregationBy)
    {
        $entityType = $select->getFrom();

        $entity = $this->entityFactory->create($entityType);

        if (empty($aggregation) || !$entity->hasAttribute($aggregationBy)) {
            throw new RuntimeException();
        }

        $params = $select->getRaw();

        $params['aggregation'] = $aggregation;
        $params['aggregationBy'] = $aggregationBy;

        $select = Select::fromRaw($params);

        $sql = $this->queryComposer->compose($select);

        $sth = $this->executeSql($sql);

        while ($row = $sth->fetch()) {
            return $row['value'];
        }

        return null;
    }

    /**
     * Select related entities from DB.
     *
     * @return ?SthCollection|Entity
     */
    public function selectRelated(Entity $entity, string $relationName, ?Select $select = null)
    {
        return $this->selectRelatedInternal($entity, $relationName, $select);
    }

    protected function selectRelatedInternal(
        Entity $entity, string $relationName, ?Select $select = null, bool $returnTotalCount = false
    ) {
        $params = [];

        if ($select) {
            $params = $select->getRaw();
        }

        $entityType = $entity->getEntityType();

        $relDefs = $entity->getRelations()[$relationName];

        if (!$entity->getRelationType($relationName)) {
            throw new LogicException(
                "Missing 'type' in definition for relationship {$relationName} in {entityType} entity."
            );
        }

        if ($relDefs['type'] !== Entity::BELONGS_TO_PARENT) {
            if (!isset($relDefs['entity'])) {
                throw new LogicException(
                    "Missing 'entity' in definition for relationship {$relationName} in {entityType} entity."
                );
            }

            $relEntityType = $entity->getRelationParam($relationName, 'entity');

            $relEntity = $this->entityFactory->create($relEntityType);
        }

        if ($returnTotalCount) {
            $params['aggregation'] = 'COUNT';
            $params['aggregationBy'] = 'id';
        }

        if (empty($params['whereClause'])) {
            $params['whereClause'] = [];
        }

        $relType = $relDefs['type'];

        $keySet = $this->helper->getRelationKeys($entity, $relationName);

        $key = $keySet['key'];
        $foreignKey = $keySet['foreignKey'];

        switch ($relType) {
            case Entity::BELONGS_TO:
                $params['whereClause'][$foreignKey] = $entity->get($key);
                $params['offset'] = 0;
                $params['limit'] = 1;
                $params['from'] = $relEntity->getEntityType();

                $sql = $this->queryComposer->compose(Select::fromRaw($params));

                $sth = $this->executeSql($sql);

                if ($returnTotalCount) {
                    while ($row = $sth->fetch()) {
                        return (int) $row['value'];
                    }

                    return 0;
                }

                while ($row = $sth->fetch()) {
                    $this->populateEntityFromRow($relEntity, $row);

                    $relEntity->setAsFetched();

                    return $relEntity;
                }

                return null;

            case Entity::HAS_MANY:
            case Entity::HAS_CHILDREN:
            case Entity::HAS_ONE:
                $params['whereClause'][$foreignKey] = $entity->get($key);

                if ($relType == Entity::HAS_CHILDREN) {
                    $foreignType = $keySet['foreignType'];
                    $params['whereClause'][$foreignType] = $entity->getEntityType();
                }

                if ($relType == Entity::HAS_ONE) {
                    $params['offset'] = 0;
                    $params['limit'] = 1;
                }

                if (!empty($relDefs['conditions']) && is_array($relDefs['conditions'])) {
                    $params['whereClause'][] = $relDefs['conditions'];
                }

                $resultDataList = [];

                $params['from'] = $relEntity->getEntityType();

                $sql = $this->queryComposer->compose(Select::fromRaw($params));

                if ($returnTotalCount) {
                    $sth = $this->executeSql($sql);

                    while ($row = $sth->fetch()) {
                        return (int) $row['value'];
                    }

                    return 0;
                }

                if ($relType == Entity::HAS_ONE) {
                    $resultDataList = $this->executeSql($sql)->fetchAll();

                    if (!count($resultDataList)) {
                        return null;
                    }

                    $this->populateEntityFromRow($relEntity, $resultDataList[0]);

                    $relEntity->setAsFetched();

                    return $relEntity;
                }

                return $this->collectionFactory->createFromSql($relEntity->getEntityType(), $sql);

            case Entity::MANY_MANY:

                $params['joins'] = $params['joins'] ?? [];

                $params['joins'][] = $this->getManyManyJoin($entity, $relationName);

                $params['select'] = $this->getModifiedSelectForManyToMany($entity, $relationName, $params['select'] ?? []);

                $params['from'] = $relEntity->getEntityType();

                $sql = $this->queryComposer->compose(Select::fromRaw($params));

                if ($returnTotalCount) {
                    $sth = $this->executeSql($sql);

                    while ($row = $sth->fetch()) {
                        return (int) $row['value'];
                    }

                    return 0;
                }

                return $this->collectionFactory->createFromSql($relEntity->getEntityType(), $sql);

            case Entity::BELONGS_TO_PARENT:
                $foreignEntityType = $entity->get($keySet['typeKey']);
                $foreignEntityId = $entity->get($key);

                if (!$foreignEntityType || !$foreignEntityId) {
                    return null;
                }

                $params['whereClause'][$foreignKey] = $foreignEntityId;
                $params['offset'] = 0;
                $params['limit'] = 1;

                $relEntity = $this->entityFactory->create($foreignEntityType);

                $params['from'] = $foreignEntityType;

                $sql = $this->queryComposer->compose(Select::fromRaw($params));

                $sth = $this->executeSql($sql);

                if ($returnTotalCount) {
                    while ($row = $sth->fetch()) {
                        return (int) $row['value'];
                    }

                    return 0;
                }

                while ($row = $sth->fetch()) {
                    $this->populateEntityFromRow($relEntity, $row);

                    $relEntity->setAsFetched();

                    return $relEntity;
                }

                return null;
        }

        throw new LogicException(
            "Bad 'type' {$relType} in definition for relationship {$relationName} in {$entityType} entity."
        );
    }

    /**
     * Get a number of related entities in DB.
     */
    public function countRelated(Entity $entity, string $relationName, ?Select $select = null) : int
    {
        return (int) $this->selectRelatedInternal($entity, $relationName, $select, true);
    }

    /**
     * Relate an entity with another entity.
     */
    public function relate(Entity $entity, string $relationName, Entity $foreignEntity, ?array $columnData = null) : bool
    {
        return $this->addRelation($entity, $relationName, null, $foreignEntity, $columnData);
    }

    /**
     * Unrelate an entity from another entity.
     */
    public function unrelate(Entity $entity, string $relationName, Entity $foreignEntity) : bool
    {
        return $this->removeRelation($entity, $relationName, null, false, $foreignEntity);
    }

    /**
     * Unrelate an entity from another entity by a given ID.
     */
    public function relateById(Entity $entity, string $relationName, string $id, ?array $columnData = null) : bool
    {
        return $this->addRelation($entity, $relationName, $id, null, $columnData);
    }

    /**
     * Unrelate an entity from another entity by a given ID.
     */
    public function unrelateById(Entity $entity, string $relationName, string $id) : bool
    {
        return $this->removeRelation($entity, $relationName, $id);
    }

    /**
     * Unrelate all related entities.
     */
    public function unrelateAll(Entity $entity, string $relationName) : bool
    {
        return $this->removeRelation($entity, $relationName, null, true);
    }

    /**
     * Update relationship columns.
     */
    public function updateRelationColumns(Entity $entity, string $relationName, ?string $id = null, array $columnData) : bool
    {
        if (empty($id) || empty($relationName)) {
            throw new RuntimeException("Can't update relation, empty ID or relation name.");
        }

        if (empty($columnData)) {
            return false;
        }

        $relDefs = $entity->getRelations()[$relationName];
        $keySet = $this->helper->getRelationKeys($entity, $relationName);

        $relType = $relDefs['type'];

        switch ($relType) {
            case Entity::MANY_MANY:
                $middleName = ucfirst($entity->getRelationParam($relationName, 'relationName'));

                $nearKey = $keySet['nearKey'];
                $distantKey = $keySet['distantKey'];

                $update = [];

                foreach ($columnData as $column => $value) {
                    $update[$column] = $value;
                }

                if (empty($update)) {
                    return true;
                }

                $where = [
                    $nearKey => $entity->id,
                    $distantKey => $id,
                    static::ATTRIBUTE_DELETED => false,
                ];

                $conditions = $entity->getRelationParam($relationName, 'conditions') ?? [];

                foreach ($conditions as $k => $value) {
                    $where[$k] = $value;
                }

                $sql = $this->queryComposer->compose(
                    Update::fromRaw([
                        'from' => $middleName,
                        'whereClause' => $where,
                        'set' => $update,
                    ])
                );

                $this->executeSql($sql, true);

                return true;
        }

        throw new LogicException("Relation type '{$relType}' is not supported.");
    }

    /**
     * Get a relationship column value.
     *
     * @return string|int|float|bool|null A relationship column value.
     */
    public function getRelationColumn(Entity $entity, string $relationName, string $id, string $column)
    {
        $type = $entity->getRelationType($relationName);

        if (!$type === Entity::MANY_MANY) {
            throw new RuntimeException("'getRelationColumn' works only on many-to-many relations.");
        }

        if (!$id) {
            throw new RuntimeException("Empty ID passed to 'getRelationColumn'.");
        }

        $middleName = ucfirst($entity->getRelationParam($relationName, 'relationName'));

        $keySet = $this->helper->getRelationKeys($entity, $relationName);

        $nearKey = $keySet['nearKey'];
        $distantKey = $keySet['distantKey'];

        $additionalColumns = $entity->getRelationParam($relationName, 'additionalColumns') ?? [];

        if (!isset($additionalColumns[$column])) {
            return null;
        }

        $columnType = $additionalColumns[$column]['type'] ?? Entity::VARCHAR;

        $where = [
            $nearKey => $entity->id,
            $distantKey => $id,
            static::ATTRIBUTE_DELETED => false,
        ];

        $conditions = $entity->getRelationParam($relationName, 'conditions') ?? [];

        foreach ($conditions as $k => $value) {
            $where[$k] = $value;
        }

        $sql = $this->queryComposer->compose(
            Select::fromRaw([
                'from' => $middleName,
                'select' => [[$column, 'value']],
                'whereClause' => $where,
            ])
        );

        $sth = $this->executeSql($sql);

        while ($row = $sth->fetch()) {
            $value = $row['value'];

            if ($columnType == Entity::BOOL) {
                return (bool) $value;
            }

            if ($columnType == Entity::INT) {
                return (int) $value;
            }

            if ($columnType == Entity::FLOAT) {
                return (int) $value;
            }

            return $value;
        }

        return null;
    }

    /**
     * Mass relate.
     */
    public function massRelate(Entity $entity, string $relationName, Select $select)
    {
        $params = $select->getRaw();

        $id = $entity->id;

        if (empty($id) || empty($relationName)) {
            throw new RuntimeException("Cant't mass relate on empty ID or relation name.");
        }

        $relDefs = $entity->getRelations()[$relationName];

        if (!isset($relDefs['entity']) || !isset($relDefs['type'])) {
            throw new LogicException(
                "Not appropriate definition for relationship {$relationName} in " . $entity->getEntityType() . " entity."
            );
        }

        $relType = $relDefs['type'];

        $foreignEntityType = $relDefs['entity'];

        $relEntity = $this->entityFactory->create($foreignEntityType);

        $keySet = $this->helper->getRelationKeys($entity, $relationName);

        switch ($relType) {
            case Entity::MANY_MANY:
                $nearKey = $keySet['nearKey'];
                $distantKey = $keySet['distantKey'];

                $middleName = ucfirst($relDefs['relationName']);

                $columns = [];
                $columns[] = $nearKey;

                $valueList = [];
                $valueList[] = $entity->id;

                $conditions = $relDefs['conditions'] ?? [];

                foreach ($conditions as $left => $value) {
                    $columns[] = $left;
                    $valueList[] = $value;
                }

                $columns[] = $distantKey;

                $params['select'] = [];

                foreach ($valueList as $i => $value) {
                   $params['select'][] = ['VALUE:' . $value, 'v' . strval($i)];
                }

                $params['select'][] = 'id';

                unset($params['orderBy']);
                unset($params['order']);

                $params['from'] = $foreignEntityType;

                $sql = $this->queryComposer->compose(
                    Insert::fromRaw([
                        'into' => $middleName,
                        'columns' => $columns,
                        'valuesQuery' => Select::fromRaw($params),
                        'updateSet' => [
                            static::ATTRIBUTE_DELETED => false,
                        ],
                    ])
                );

                $this->executeSql($sql, true);

                return;
        }

        throw new LogicException("Relation type '{$relType}' is not supported for mass relate.");
    }

    protected function executeSql(string $sql, bool $rerunIfDeadlock = false)/* : PDOStatement*/
    {
        return $this->sqlExecutor->execute($sql, $rerunIfDeadlock);
    }

    protected function addRelation(
        Entity $entity, string $relationName, ?string $id = null, ?Entity $relEntity = null, ?array $data = null
    ) : bool {

        $entityType = $entity->getEntityType();

        if ($relEntity) {
            $id = $relEntity->id;
        }

        if (empty($id) || empty($relationName) || !$entity->get('id')) {
            throw new RuntimeException("Can't relate an empty entity or relation name.");
        }

        if (!$entity->hasRelation($relationName)) {
            throw new RuntimeException("Relation '{$relationName}' does not exist in '{$entityType}'.");
        }

        $relDefs = $entity->getRelations()[$relationName];

        $relType = $entity->getRelationType($relationName);

        if ($relType == Entity::BELONGS_TO_PARENT && !$relEntity) {
            throw new RuntimeException("Bad foreign passed.");
        }

        $foreignEntityType = $entity->getRelationParam($relationName, 'entity');

        if (!$relType || !$foreignEntityType && $relType !== Entity::BELONGS_TO_PARENT) {
            throw new LogicException(
                "Not appropriate definition for relationship {$relationName} in '{$entityType}' entity."
            );
        }

        if (is_null($relEntity)) {
            $relEntity = $this->entityFactory->create($foreignEntityType);

            $relEntity->id = $id;
        }

        $keySet = $this->helper->getRelationKeys($entity, $relationName);

        switch ($relType) {
            case Entity::BELONGS_TO:
                $key = $relationName . 'Id';

                $foreignRelationName = $entity->getRelationParam($relationName, 'foreign');

                if (
                    $foreignRelationName &&
                    $relEntity->getRelationParam($foreignRelationName, 'type') === Entity::HAS_ONE
                ) {
                    $sql = $this->queryComposer->compose(
                        Update::fromRaw([
                            'from' => $entityType,
                            'whereClause' => [
                                'id!=' => $entity->id,
                                $key => $id,
                                static::ATTRIBUTE_DELETED => false,
                            ],
                            'set' => [
                                $key => null,
                            ],
                        ])
                    );

                    $this->executeSql($sql, true);
                }

                $entity->set($key, $relEntity->id);
                $entity->setFetched($key, $relEntity->id);

                $sql = $this->queryComposer->compose(
                    Update::fromRaw([
                        'from' => $entityType,
                        'whereClause' => [
                            'id' => $entity->id,
                            static::ATTRIBUTE_DELETED => false,
                        ],
                        'set' => [
                            $key => $relEntity->id,
                        ],
                    ])
                );

                $this->executeSql($sql, true);

                return true;

            case Entity::BELONGS_TO_PARENT:
                $key = $relationName . 'Id';
                $typeKey = $relationName . 'Type';

                $entity->set($key, $relEntity->id);
                $entity->set($typeKey, $relEntity->getEntityType());
                $entity->setFetched($key, $relEntity->id);
                $entity->setFetched($typeKey, $relEntity->getEntityType());

                $sql = $this->queryComposer->compose(
                    Update::fromRaw([
                        'from' => $entityType,
                        'whereClause' => [
                            'id' => $entity->id,
                            static::ATTRIBUTE_DELETED => false,
                        ],
                        'set' => [
                            $key => $relEntity->id,
                            $typeKey => $relEntity->getEntityType(),
                        ],
                    ])
                );

                $this->executeSql($sql, true);

                return true;

            case Entity::HAS_ONE:
                $foreignKey = $keySet['foreignKey'];

                $selectForCount = Select::fromRaw([
                    'from' => $relEntity->getEntityType(),
                    'whereClause' => ['id' => $id],
                ]);

                if ($this->count($selectForCount) === 0) {
                    return false;
                }

                $sql = $this->queryComposer->compose(
                    Update::fromRaw([
                        'from' => $relEntity->getEntityType(),
                        'whereClause' => [
                            $foreignKey => $entity->id,
                            static::ATTRIBUTE_DELETED => false,
                        ],
                        'set' => [
                            $foreignKey => NULL,
                        ],
                    ])
                );

                $this->executeSql($sql, true);

                $sql = $this->queryComposer->compose(
                    Update::fromRaw([
                        'from' => $relEntity->getEntityType(),
                        'whereClause' => [
                            'id' => $id,
                            static::ATTRIBUTE_DELETED => false,
                        ],
                        'set' => [
                            $foreignKey => $entity->id,
                        ],
                    ])
                );

                $this->executeSql($sql, true);

                return true;

            case Entity::HAS_CHILDREN:
            case Entity::HAS_MANY:
                $key = $keySet['key'];
                $foreignKey = $keySet['foreignKey'];

                $selectForCount = Select::fromRaw([
                    'from' => $relEntity->getEntityType(),
                    'whereClause' => ['id' => $id],
                ]);

                if ($this->count($selectForCount) === 0) {
                    return false;
                }

                $set = [
                    $foreignKey => $entity->get('id'),
                ];

                if ($relType == Entity::HAS_CHILDREN) {
                    $foreignType = $keySet['foreignType'];
                    $set[$foreignType] = $entity->getEntityType();
                }

                $sql = $this->queryComposer->compose(
                    Update::fromRaw([
                        'from' => $relEntity->getEntityType(),
                        'whereClause' => [
                            'id' => $id,
                            static::ATTRIBUTE_DELETED => false,
                        ],
                        'set' => $set,
                    ])
                );

                $this->executeSql($sql, true);

                return true;

            case Entity::MANY_MANY:
                $nearKey = $keySet['nearKey'];
                $distantKey = $keySet['distantKey'];

                $selectForCount = Select::fromRaw([
                    'from' => $relEntity->getEntityType(),
                    'whereClause' => ['id' => $id],
                ]);

                if ($this->count($selectForCount) === 0) {
                    return false;
                }

                if (!isset($relDefs['relationName'])) {
                    throw new LogicException("Bad relation '{$relationName}' in '{$entityType}'.");
                }

                $middleName = ucfirst($relDefs['relationName']);

                $conditions = $relDefs['conditions'] ?? [];
                $data = $data ?? [];

                $where = [
                    $nearKey => $entity->id,
                    $distantKey => $relEntity->id,
                ];

                foreach ($conditions as $f => $v) {
                    $where[$f] = $v;
                }

                $sql = $this->queryComposer->compose(
                    Select::fromRaw([
                        'from' => $middleName,
                        'select' => ['id'],
                        'whereClause' => $where,
                        'withDeleted' => true,
                    ])
                );

                $sth = $this->executeSql($sql);

                // @todo Leave one INSERT for better performance.

                if ($sth->rowCount() == 0) {
                    $values = $where;
                    $columns = array_keys($values);

                    $update = [
                        static::ATTRIBUTE_DELETED => false,
                    ];

                    foreach ($data as $column => $value) {
                        $columns[] = $column;
                        $values[$column] = $value;
                        $update[$column] = $value;
                    }

                    $sql = $this->queryComposer->compose(
                        Insert::fromRaw([
                            'into' => $middleName,
                            'columns' => $columns,
                            'values' => $values,
                            'updateSet' => $update,
                        ])
                    );

                    $this->executeSql($sql, true);

                    return true;
                }

                $update = [
                    static::ATTRIBUTE_DELETED => false,
                ];

                foreach ($data as $column => $value) {
                    $update[$column] = $value;
                }

                $sql = $this->queryComposer->compose(
                    Update::fromRaw([
                        'from' => $middleName,
                        'whereClause' => $where,
                        'set' => $update,
                    ])
                );

                $this->executeSql($sql, true);

                return true;
        }

        throw new LogicException("Relation type '{$relType}' is not supported.");
    }

    protected function removeRelation(
        Entity $entity, string $relationName, ?string $id = null, bool $all = false, ?Entity $relEntity = null
    ) : bool {
        if ($relEntity) {
            $id = $relEntity->id;
        }

        $entityType = $entity->getEntityType();

        if (empty($id) && empty($all) || empty($relationName)) {
            throw new RuntimeException("Can't unrelate an empty entity or relation name.");
        }

        if (!$entity->hasRelation($relationName)) {
            throw new RuntimeException("Relation '{$relationName}' does not exist in '{$entityType}'.");
        }

        $relDefs = $entity->getRelations()[$relationName];

        $relType = $entity->getRelationType($relationName);

        if ($relType === Entity::BELONGS_TO_PARENT && !$relEntity && !$all) {
            throw new RuntimeException("Bad foreign passed.");
        }

        $foreignEntityType = $entity->getRelationParam($relationName, 'entity');

        if ($relType === Entity::BELONGS_TO_PARENT && $relEntity) {
            $foreignEntityType = $relEntity->getEntityType();
        }

        if (!$relType || !$foreignEntityType && $relType !== Entity::BELONGS_TO_PARENT) {
            throw new LogicException(
                "Not appropriate definition for relationship {$relationName} in " .
                $entity->getEntityType() . " entity."
            );
        }

        if (is_null($relEntity) && $relType !== Entity::BELONGS_TO_PARENT) {
            $relEntity = $this->entityFactory->create($foreignEntityType);

            $relEntity->id = $id;
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
                    'id' => $entity->id
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

                $where[static::ATTRIBUTE_DELETED] = false;

                $sql = $this->queryComposer->compose(
                    Update::fromRaw([
                        'from' => $entityType,
                        'whereClause' => $where,
                        'set' => $update,
                    ])
                );

                $this->executeSql($sql, true);

                return true;

            case Entity::HAS_ONE:
            case Entity::HAS_MANY:
            case Entity::HAS_CHILDREN:
                $foreignKey = $keySet['foreignKey'];

                $update = [
                    $foreignKey => null,
                ];

                $where = [];

                if (!$all && $relType !== Entity::HAS_ONE) {
                    $where['id'] = $id;
                }

                $where[$foreignKey] = $entity->id;

                if ($relType === Entity::HAS_CHILDREN) {
                    $foreignType = $keySet['foreignType'];
                    $where[$foreignType] = $entity->getEntityType();
                    $update[$foreignType] = null;
                }

                $where[static::ATTRIBUTE_DELETED] = false;

                $sql = $this->queryComposer->compose(
                    Update::fromRaw([
                        'from' => $relEntity->getEntityType(),
                        'whereClause' => $where,
                        'set' => $update,
                    ])
                );

                $this->executeSql($sql, true);

                return true;

            case Entity::MANY_MANY:
                $nearKey = $keySet['nearKey'];
                $distantKey = $keySet['distantKey'];

                if (!isset($relDefs['relationName'])) {
                    throw new LogicException("Bad relation '{$relationName}' in '{$entityType}'.");
                }

                $middleName = ucfirst($relDefs['relationName']);

                $conditions = $relDefs['conditions'] ?? [];

                $where = [
                    $nearKey => $entity->id,
                ];

                if (!$all) {
                    $where[$distantKey] = $id;
                }

                foreach ($conditions as $f => $v) {
                    $where[$f] = $v;
                }

                $sql = $this->queryComposer->compose(
                    Update::fromRaw([
                        'from' => $middleName,
                        'whereClause' => $where,
                        'set' => [
                            static::ATTRIBUTE_DELETED => true,
                        ],
                    ])
                );

                $this->executeSql($sql, true);

                return true;
        }

        throw new LogicException("Relation type '{$relType}' is not supported for unrelating.");
    }

    /**
     * Insert an entity into DB.
     *
     * @todo Set 'id' if auto-increment (as fetched).
     */
    public function insert(Entity $entity)
    {
        $this->insertInternal($entity);
    }

    /**
     * Insert an entity into DB, on duplicate key update specified attributes.
     */
    public function insertOnDuplicateUpdate(Entity $entity, array $onDuplicateUpdateAttributeList)
    {
        $this->insertInternal($entity, $onDuplicateUpdateAttributeList);
    }

    protected function insertInternal(Entity $entity, ?array $onDuplicateUpdateAttributeList = null)
    {
        $update = null;

        if ($onDuplicateUpdateAttributeList && count($onDuplicateUpdateAttributeList)) {
            $update = $onDuplicateSetMap = $this->getInsertOnDuplicateSetMap($entity, $onDuplicateUpdateAttributeList);
        }

        $sql = $this->queryComposer->compose(
            Insert::fromRaw([
                'into' => $entity->getEntityType(),
                'columns' => $this->getInsertColumnList($entity),
                'values' => $this->getInsertValueMap($entity),
                'updateSet' => $update,
            ])
        );

        $this->executeSql($sql, true);

        if ($entity->getAttributeParam('id', 'autoincrement')) {
            $this->setLastInsertIdWithinConnection($entity);
        }
    }

    protected function setLastInsertIdWithinConnection(Entity $entity)
    {
        $id = $this->pdo->lastInsertId();

        if ($id === '' || $id === null) {
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
    public function massInsert(Collection $collection)
    {
        if (!count($collection)) {
            return;
        }

        $values = [];

        foreach ($collection as $entity) {
            $values[] = $this->getInsertValueMap($entity);
        }

        $sql = $this->queryComposer->compose(
            Insert::fromRaw([
                'into' => $entity->getEntityType(),
                'columns' => $this->getInsertColumnList($collection[0]),
                'values' => $values,
            ])
        );

        $this->executeSql($sql, true);
    }

    protected function getInsertColumnList(Entity $entity) : array
    {
        $columnList = [];

        $dataList = $this->toValueMap($entity);

        foreach ($dataList as $attribute => $value) {
            $columnList[] = $attribute;
        }

        return $columnList;
    }

    protected function getInsertValueMap(Entity $entity) : array
    {
        $map = [];

        foreach ($this->toValueMap($entity) as $attribute => $value) {
            $type = $entity->getAttributeType($attribute);
            $map[$attribute] = $this->prepareValueForInsert($type, $value);
        }

        return $map;
    }

    protected function getInsertOnDuplicateSetMap(Entity $entity, array $attributeList)
    {
        $list = [];

        foreach ($attributeList as $a) {
            $list[$a] = $this->prepareValueForInsert($entity, $entity->get($a));
        }

        return $list;
    }

    protected function getValueMapForUpdate(Entity $entity) : array
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
    public function update(Entity $entity)
    {
        $valueMap = $this->getValueMapForUpdate($entity);

        if (count($valueMap) == 0) {
            return;
        }

        $sql = $this->queryComposer->compose(
            Update::fromRaw([
                'from' => $entity->getEntityType(),
                'whereClause' => [
                    'id' => $entity->id,
                    static::ATTRIBUTE_DELETED => false,
                ],
                'set' => $valueMap,
            ])
        );

        $this->executeSql($sql);
    }

    protected function prepareValueForInsert($type, $value)
    {
        if ($type == Entity::JSON_ARRAY && is_array($value)) {
            $value = json_encode($value, \JSON_UNESCAPED_UNICODE);
        }
        else if ($type == Entity::JSON_OBJECT && (is_array($value) || $value instanceof StdClass)) {
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
    public function deleteFromDb(string $entityType, string $id, bool $onlyDeleted = false)
    {
        if (empty($entityType) || empty($id)) {
            throw new RuntimeException("Can't delete an empty entity type or ID from DB.");
        }

        $whereClause = [
            'id' => $id,
        ];

        if ($onlyDeleted) {
            $whereClause[static::ATTRIBUTE_DELETED] = true;
        }

        $sql = $this->queryComposer->compose(Delete::fromRaw([
            'from' => $entityType,
            'whereClause' => $whereClause,
        ]));

        $this->executeSql($sql);
    }

    /**
     * Unmark an entity as deleted in DB.
     */
    public function restoreDeleted(string $entityType, string $id)
    {
        if (empty($entityType) || empty($id)) {
            throw new RuntimeException("Can't restore an empty entity type or ID.");
        }

        $whereClause = [
            'id' => $id,
        ];

        $sql = $this->queryComposer->compose(
            Update::fromRaw([
                'from' => $entityType,
                'whereClause' => $whereClause,
                'set' => [static::ATTRIBUTE_DELETED => false],
            ])
        );

        $this->executeSql($sql);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(Entity $entity) : bool
    {
        $entity->set(static::ATTRIBUTE_DELETED, true);

        return (booL) $this->update($entity);
    }

    protected function toValueMap(Entity $entity, bool $onlyStorable = true) : array
    {
        $data = [];

        foreach ($entity->getAttributes() as $attribute => $defs) {
            if ($entity->has($attribute)) {
                if ($onlyStorable) {
                    if (
                        !empty($defs['notStorable'])
                        ||
                        !empty($defs['autoincrement'])
                        ||
                        isset($defs['source']) && $defs['source'] != 'db'
                    ) {
                        continue;
                    }

                    if ($defs['type'] == Entity::FOREIGN) {
                        continue;
                    }
                }

                $data[$attribute] = $entity->get($attribute);
            }
        }

        return $data;
    }

    protected function populateEntityFromRow(Entity $entity, $data)
    {
        $entity->set($data);
    }

    protected function getModifiedSelectForManyToMany(Entity $entity, string $relationName, array $select) : array
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

        foreach ($additionalSelect as $i => $item) {
            $index = array_search($item[1], $select);
            if ($index !== false) {
                $select[$index] = $item;
            }
        }

        return $select;
    }

    protected function getManyManyJoin(Entity $entity, string $relationName, ?array $conditions = null) : array
    {
        $defs = $entity->getRelations()[$relationName];

        $middleName = $defs['relationName'] ?? null;

        $keySet = $this->helper->getRelationKeys($entity, $relationName);

        $key = $keySet['key'];
        $foreignKey = $keySet['foreignKey'];
        $nearKey = $keySet['nearKey'];
        $distantKey = $keySet['distantKey'];

        if (!$middleName) {
            throw new RuntimeException("No 'relationName' parameter for '{$relationName}' relationship.");
        }

        $alias = lcfirst($middleName);

        $join = [
            ucfirst($middleName),
            $alias,
            [
                "{$distantKey}:" => $foreignKey,
                "{$nearKey}" => $entity->get($key),
                static::ATTRIBUTE_DELETED => false,
            ],
        ];

        $conditions = $conditions ?? [];

        if (!empty($defs['conditions']) && is_array($defs['conditions'])) {
            $conditions = array_merge($conditions, $defs['conditions']);
        }

        $join[2] = array_merge($join[2], $conditions);

        return $join;
    }

    protected function getManyManyAdditionalSelect(Entity $entity, string $relationName) : array
    {
        $foreign = $entity->getRelationParam($relationName, 'foreign');
        $foregnEntityType = $entity->getRelationParam($relationName, 'entity');

        $middleName = lcfirst($entity->getRelationParam($relationName, 'relationName'));

        if (!$foreign || !$foregnEntityType) {
            return [];
        }

        $foreignEntity = $this->entityFactory->create($foregnEntityType);

        $map = $foreignEntity->getRelationParam($foreign, 'columnAttributeMap') ?? [];

        $select = [];

        foreach ($map as $column => $attribute) {
            $select[] = [
                $middleName . '.' . $column,
                $attribute
            ];
        }

        return $select;
    }
}
