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

namespace Espo\Core\Utils\Database\Schema;

use Espo\Core\InjectableFactory;
use Espo\Core\Utils\Database\ConfigDataProvider;
use Espo\Core\Utils\Database\MetadataProvider as MetadataProvider;
use Espo\Core\Utils\Log;
use Espo\Core\Utils\Util;

use Espo\ORM\Defs\AttributeDefs;
use Espo\ORM\Defs\EntityDefs;
use Espo\ORM\Defs\IndexDefs;
use Espo\ORM\Defs\Params\AttributeParam;
use Espo\ORM\Defs\Params\EntityParam;
use Espo\ORM\Defs\Params\RelationParam;
use Espo\ORM\Defs\RelationDefs;
use Espo\ORM\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\DBAL\Schema\SchemaException;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\Schema as DbalSchema;
use Doctrine\DBAL\Types\Type as DbalType;
use Espo\ORM\Type\AttributeType;

/**
 * Schema representation builder.
 */
class Builder
{
    private const ATTR_ID = 'id';
    private const ATTR_DELETED = 'deleted';

    private int $idLength;
    private string $idDbType;

    /** @var string[] */
    private $typeList;
    private ColumnPreparator $columnPreparator;

    public function __construct(
        private Log $log,
        private InjectableFactory $injectableFactory,
        ConfigDataProvider $configDataProvider,
        ColumnPreparatorFactory $columnPreparatorFactory,
        MetadataProvider $metadataProvider
    ) {
        $this->typeList = array_keys(DbalType::getTypesMap());

        $platform = $configDataProvider->getPlatform();

        $this->columnPreparator = $columnPreparatorFactory->create($platform);

        $this->idLength = $metadataProvider->getIdLength();
        $this->idDbType = $metadataProvider->getIdDbType();
    }

    /**
     * Build a schema representation for an ORM metadata.
     *
     * @param array<string, mixed> $ormMeta Raw ORM metadata.
     * @param ?string[] $entityTypeList Specific entity types.
     * @throws SchemaException
     */
    public function build(array $ormMeta, ?array $entityTypeList = null): DbalSchema
    {
        $this->log->debug('Schema\Builder - Start');

        $ormMeta = $this->amendMetadata($ormMeta, $entityTypeList);
        $tables = [];

        $schema = new DbalSchema();

        foreach ($ormMeta as $entityType => $entityParams) {
            $entityDefs = EntityDefs::fromRaw($entityParams, $entityType);

            $this->buildEntity($entityDefs, $schema, $tables);
        }

        foreach ($ormMeta as $entityType => $entityParams) {
            foreach (($entityParams[EntityParam::RELATIONS] ?? []) as $relationName => $relationParams) {
                $relationDefs = RelationDefs::fromRaw($relationParams, $relationName);

                if ($relationDefs->getType() !== Entity::MANY_MANY) {
                    continue;
                }

                $this->buildManyMany($entityType, $relationDefs, $schema, $tables);
            }
        }

        $this->log->debug('Schema\Builder - End');

        return $schema;
    }

    /**
     * @param array<string, Table> $tables
     * @throws SchemaException
     */
    private function buildEntity(EntityDefs $entityDefs, DbalSchema $schema, array &$tables): void
    {
        if ($entityDefs->getParam('skipRebuild')) {
            return;
        }

        $entityType = $entityDefs->getName();

        $modifier = $this->getEntityDefsModifier($entityDefs);

        if ($modifier) {
            $modifiedEntityDefs = $modifier->modify($entityDefs);

            $entityDefs = EntityDefs::fromRaw($modifiedEntityDefs->toAssoc(), $entityType);
        }

        $this->log->debug("Schema\Builder: Entity $entityType");

        $tableName = Util::toUnderScore($entityType);

        if ($schema->hasTable($tableName)) {
            $tables[$entityType] ??= $schema->getTable($tableName);

            $this->log->debug('Schema\Builder: Table [' . $tableName . '] exists.');

            return;
        }

        $table = $schema->createTable($tableName);

        $tables[$entityType] = $table;

        /** @var array<string, mixed> $tableParams */
        $tableParams = $entityDefs->getParam('params') ?? [];

        foreach ($tableParams as $paramName => $paramValue) {
            $table->addOption($paramName, $paramValue);
        }

        $primaryColumns = [];

        foreach ($entityDefs->getAttributeList() as $attributeDefs) {
            if (
                $attributeDefs->isNotStorable() ||
                $attributeDefs->getType() === Entity::FOREIGN
            ) {
                continue;
            }

            $column = $this->columnPreparator->prepare($attributeDefs);

            if ($attributeDefs->getType() === Entity::ID) {
                $primaryColumns[] = $column->getName();
            }

            if (!in_array($column->getType(), $this->typeList)) {
                $this->log->warning(
                    'Schema\Builder: Column type [' . $column->getType() . '] not supported, ' .
                    $entityType . ':' . $attributeDefs->getName()
                );

                continue;
            }

            if ($table->hasColumn($column->getName())) {
                continue;
            }

            $this->addColumn($table, $column);
        }

        $table->setPrimaryKey($primaryColumns);

        $this->addIndexes($table, $entityDefs->getIndexList());
    }

    private function getEntityDefsModifier(EntityDefs $entityDefs): ?EntityDefsModifier
    {
        /** @var ?class-string<EntityDefsModifier> $modifierClassName */
        $modifierClassName = $entityDefs->getParam('modifierClassName');

        if (!$modifierClassName) {
            return null;
        }

        return $this->injectableFactory->create($modifierClassName);
    }

    /**
     * @param array<string, mixed> $ormMeta
     * @param ?string[] $entityTypeList
     * @return array<string, mixed>
     */
    private function amendMetadata(array $ormMeta, ?array $entityTypeList): array
    {
        if (isset($ormMeta['unsetIgnore'])) {
            $protectedOrmMeta = [];

            foreach ($ormMeta['unsetIgnore'] as $protectedKey) {
                $protectedOrmMeta = Util::merge(
                    $protectedOrmMeta,
                    Util::fillArrayKeys($protectedKey, Util::getValueByKey($ormMeta, $protectedKey))
                );
            }

            unset($ormMeta['unsetIgnore']);
        }

        // Unset some keys.
        if (isset($ormMeta['unset'])) {
            /** @var array<string, mixed> $ormMeta */
            $ormMeta = Util::unsetInArray($ormMeta, $ormMeta['unset']);

            unset($ormMeta['unset']);
        }

        if (isset($protectedOrmMeta)) {
            /** @var array<string, mixed> $ormMeta */
            $ormMeta = Util::merge($ormMeta, $protectedOrmMeta);
        }

        if (isset($entityTypeList)) {
            $dependentEntityTypeList = $this->getDependentEntityTypeList($entityTypeList, $ormMeta);

            $this->log->debug(
                'Schema\Builder: Rebuild for entity types: [' .
                implode(', ', $entityTypeList) . '] with dependent entity types: [' .
                implode(', ', $dependentEntityTypeList) . ']'
            );

            $ormMeta = array_intersect_key($ormMeta, array_flip($dependentEntityTypeList));
        }

        return $ormMeta;
    }

    /**
     * @throws SchemaException
     */
    private function addColumn(Table $table, Column $column): void
    {
        $table->addColumn(
            $column->getName(),
            $column->getType(),
            self::convertColumn($column)
        );
    }

    /**
     * Prepare a relation table for the manyMany relation.
     *
     * @param string $entityType
     * @param array<string, Table> $tables
     * @throws SchemaException
     */
    private function buildManyMany(
        string $entityType,
        RelationDefs $relationDefs,
        DbalSchema $schema,
        array &$tables
    ): void {

        $relationshipName = $relationDefs->getRelationshipName();

        if (isset($tables[$relationshipName])) {
            return;
        }

        $tableName = Util::toUnderScore($relationshipName);

        $this->log->debug("Schema\Builder: ManyMany for $entityType.{$relationDefs->getName()}");

        if ($schema->hasTable($tableName)) {
            $this->log->debug('Schema\Builder: Table [' . $tableName . '] exists.');

            $tables[$relationshipName] ??= $schema->getTable($tableName);

            return;
        }

        $table = $schema->createTable($tableName);

        $idColumn = $this->columnPreparator->prepare(
            AttributeDefs::fromRaw([
                AttributeParam::DB_TYPE => Types::BIGINT,
                'type' => Entity::ID,
                AttributeParam::LEN => 20,
                'autoincrement' => true,
            ], self::ATTR_ID)
        );

        $this->addColumn($table, $idColumn);

        if (!$relationDefs->hasMidKey() || !$relationDefs->getForeignMidKey()) {
            $this->log->error('Schema\Builder: Relationship midKeys are empty.', [
                'entityType' => $entityType,
                'relationName' => $relationDefs->getName(),
            ]);

            return;
        }

        $midKeys = [
            $relationDefs->getMidKey(),
            $relationDefs->getForeignMidKey(),
        ];

        foreach ($midKeys as $midKey) {
            $column = $this->columnPreparator->prepare(
                AttributeDefs::fromRaw([
                    'type' => Entity::FOREIGN_ID,
                    AttributeParam::DB_TYPE => $this->idDbType,
                    AttributeParam::LEN => $this->idLength,
                ], $midKey)
            );

            $this->addColumn($table, $column);
        }

        /** @var array<string, array<string, mixed>> $additionalColumns */
        $additionalColumns = $relationDefs->getParam(RelationParam::ADDITIONAL_COLUMNS) ?? [];

        foreach ($additionalColumns as $fieldName => $fieldParams) {
            if ($fieldParams['type'] === AttributeType::FOREIGN_ID) {
                $fieldParams = array_merge([
                    AttributeParam::DB_TYPE => $this->idDbType,
                    AttributeParam::LEN => $this->idLength,
                ], $fieldParams);
            }

            $column = $this->columnPreparator->prepare(AttributeDefs::fromRaw($fieldParams, $fieldName));

            $this->addColumn($table, $column);
        }

        $deletedColumn = $this->columnPreparator->prepare(
            AttributeDefs::fromRaw([
                'type' => Entity::BOOL,
                'default' => false,
            ], self::ATTR_DELETED)
        );

        $this->addColumn($table, $deletedColumn);

        $table->setPrimaryKey([self::ATTR_ID]);

        $this->addIndexes($table, $relationDefs->getIndexList());

        $tables[$relationshipName] = $table;
    }

    /**
     * @param IndexDefs[] $indexDefsList
     * @throws SchemaException
     */
    private function addIndexes(Table $table, array $indexDefsList): void
    {
        foreach ($indexDefsList as $indexDefs) {
            $columns = array_map(
                fn($item) => Util::toUnderScore($item),
                $indexDefs->getColumnList()
            );

            if ($indexDefs->isUnique()) {
                $table->addUniqueIndex($columns, $indexDefs->getKey());

                continue;
            }

            $table->addIndex($columns, $indexDefs->getKey(), $indexDefs->getFlagList());
        }
    }

    /**
     * @todo Move to a class. Add unit test.
     * @return array<string, mixed>
     */
    private static function convertColumn(Column $column): array
    {
        $result = [
            'notnull' => $column->isNotNull(),
        ];

        if ($column->getLength() !== null) {
            $result['length'] = $column->getLength();
        }

        if ($column->getDefault() !== null) {
            $result['default'] = $column->getDefault();
        }

        if ($column->getAutoincrement() !== null) {
            $result['autoincrement'] = $column->getAutoincrement();
        }

        if ($column->getPrecision() !== null) {
            $result['precision'] = $column->getPrecision();
        }

        if ($column->getScale() !== null) {
            $result['scale'] = $column->getScale();
        }

        if ($column->getUnsigned() !== null) {
            $result['unsigned'] = $column->getUnsigned();
        }

        if ($column->getFixed() !== null) {
            $result['fixed'] = $column->getFixed();
        }

        // Can't use customSchemaOptions as it causes unwanted ALTER TABLE.
        $result['platformOptions'] = [];

        if ($column->getCollation()) {
            $result['platformOptions']['collation'] = $column->getCollation();
        }

        if ($column->getCharset()) {
            $result['platformOptions']['charset'] = $column->getCharset();
        }

        return $result;
    }

    /**
     * @param string[] $entityTypeList
     * @param array<string, mixed> $ormMeta
     * @param string[] $depList
     * @return string[]
     */
    private function getDependentEntityTypeList(array $entityTypeList, array $ormMeta, array $depList = []): array
    {
        foreach ($entityTypeList as $entityType) {
            if (in_array($entityType, $depList)) {
                continue;
            }

            $depList[] = $entityType;

            $entityDefs = EntityDefs::fromRaw($ormMeta[$entityType] ?? [], $entityType);

            foreach ($entityDefs->getRelationList() as $relationDefs) {
                if (!$relationDefs->hasForeignEntityType()) {
                    continue;
                }

                $itemEntityType = $relationDefs->getForeignEntityType();

                if (in_array($itemEntityType, $depList)) {
                    continue;
                }

                $depList = $this->getDependentEntityTypeList([$itemEntityType], $ormMeta, $depList);
            }
        }

        return $depList;
    }
}
