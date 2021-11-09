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

namespace Espo\Core\Utils\Database\Schema;

use Espo\Core\Utils\{
    Util,
    Config,
    Metadata,
    File\Manager as FileManager,
    Log,
    Database\Schema\Schema,
    Database\Schema\Utils as SchemaUtils,
    Module\PathProvider,
};

use Doctrine\DBAL\{
    Schema\Schema as DbalSchema,
    Types\Type as DbalType,
};

class Converter
{
    private $dbalSchema;

    private $databaseSchema;

    private $fileManager;

    private $metadata;

    private $config;

    private $log;

    private $pathProvider;

    private $tablesPath = 'Core/Utils/Database/Schema/tables';

    protected $typeList;

    //pair ORM => doctrine
    protected $allowedDbFieldParams = [
        'len' => 'length',
        'default' => 'default',
        'notNull' => 'notnull',
        'autoincrement' => 'autoincrement',
    ];

    //todo: same array in Converters\Orm
    protected $idParams = [
        'dbType' => 'varchar',
        'len' => 24,
    ];

    //todo: same array in Converters\Orm
    protected $defaultLength = [
        'varchar' => 255,
        'int' => 11,
    ];

    protected $notStorableTypes = [
        'foreign',
    ];

    protected $maxIndexLength;

    public function __construct(
        Metadata $metadata,
        FileManager $fileManager,
        Schema $databaseSchema,
        Config $config,
        Log $log,
        PathProvider $pathProvider
    ) {
        $this->metadata = $metadata;
        $this->fileManager = $fileManager;
        $this->databaseSchema = $databaseSchema;
        $this->config = $config;
        $this->log = $log;
        $this->pathProvider = $pathProvider;

        $this->typeList = array_keys(DbalType::getTypesMap());
    }

    /**
     * Get schema
     *
     * @param  boolean $reload
     *
     * @return \Doctrine\DBAL\Schema\Schema
     */
    protected function getSchema($reload = false)
    {
        if (!isset($this->dbalSchema) || $reload) {
            $this->dbalSchema = new DbalSchema();
        }

        return $this->dbalSchema;
    }

    protected function getDatabaseSchema()
    {
        return $this->databaseSchema;
    }

    protected function getMaxIndexLength()
    {
        if (!isset($this->maxIndexLength)) {
            $this->maxIndexLength = $this->getDatabaseSchema()->getDatabaseHelper()->getMaxIndexLength();
        }

        return $this->maxIndexLength;
    }

    /**
     * Schema conversation process.
     *
     * @param array $ormMeta
     * @param array|string|null $entityList
     *
     * @return \Doctrine\DBAL\Schema\Schema
     */
    public function process(array $ormMeta, $entityList = null)
    {
        $this->log->debug('Schema\Converter - Start: building schema');

        // check if exist files in "Tables" directory and merge with ormMetadata
        $ormMeta = Util::merge($ormMeta, $this->getCustomTables($ormMeta));

        if (isset($ormMeta['unsetIgnore'])) {
            $protectedOrmMeta = array();

            foreach ($ormMeta['unsetIgnore'] as $protectedKey) {
                $protectedOrmMeta = Util::merge(
                    $protectedOrmMeta,
                    Util::fillArrayKeys($protectedKey, Util::getValueByKey($ormMeta, $protectedKey))
                );
            }

            unset($ormMeta['unsetIgnore']);
        }

        // unset some keys in orm
        if (isset($ormMeta['unset'])) {
            $ormMeta = Util::unsetInArray($ormMeta, $ormMeta['unset']);

            unset($ormMeta['unset']);
        }

        if (isset($protectedOrmMeta)) {
            $ormMeta = Util::merge($ormMeta, $protectedOrmMeta);
        }

        if (isset($entityList)) {
            $entityList = is_string($entityList) ? (array) $entityList : $entityList;

            $dependentEntities = $this->getDependentEntities($entityList, $ormMeta);

            $this->log->debug(
                'Rebuild Database for entities: ['.
                implode(', ', $entityList).'] with dependent entities: ['.
                implode(', ', $dependentEntities).']'
            );

            $ormMeta = array_intersect_key($ormMeta, array_flip($dependentEntities));
        }

        $schema = $this->getSchema(true);

        $indexes = SchemaUtils::getIndexes($ormMeta);

        $tables = [];

        foreach ($ormMeta as $entityName => $entityParams) {
            if ($entityParams['skipRebuild'] ?? false) {
                continue;
            }

            $tableName = Util::toUnderScore($entityName);

            if ($schema->hasTable($tableName)) {
                if (!isset($tables[$entityName])) {
                    $tables[$entityName] = $schema->getTable($tableName);
                }

                $this->log->debug('DBAL: Table ['.$tableName.'] exists.');

                continue;
            }

            $tables[$entityName] = $schema->createTable($tableName);

            if (isset($entityParams['params']) && is_array($entityParams['params'])) {
                foreach ($entityParams['params'] as $paramName => $paramValue) {
                    $tables[$entityName]->addOption($paramName, $paramValue);
                }
            }

            $primaryColumns = array();

            foreach ($entityParams['fields'] as $fieldName => $fieldParams) {

                if (
                    (isset($fieldParams['notStorable']) && $fieldParams['notStorable']) ||
                    in_array($fieldParams['type'], $this->notStorableTypes)
                ) {
                    continue;
                }

                switch ($fieldParams['type']) {
                    case 'id':
                        $primaryColumns[] = Util::toUnderScore($fieldName);

                        break;
                }

                $fieldType = isset($fieldParams['dbType']) ? $fieldParams['dbType'] : $fieldParams['type'];

                /** doctrine uses strtolower for all field types */
                $fieldType = strtolower($fieldType);

                if (!in_array($fieldType, $this->typeList)) {
                    $this->log->debug(
                        'Converters\Schema::process(): Field type ['.$fieldType.'] does not exist '.
                        $entityName.':'.$fieldName
                    );

                    continue;
                }

                $columnName = Util::toUnderScore($fieldName);

                if (!$tables[$entityName]->hasColumn($columnName)) {
                    $tables[$entityName]->addColumn($columnName, $fieldType, $this->getDbFieldParams($fieldParams));
                }
            }

            $tables[$entityName]->setPrimaryKey($primaryColumns);

            if (!empty($indexes[$entityName])) {
                $this->addIndexes($tables[$entityName], $indexes[$entityName]);
            }
        }

        // check and create columns/tables for relations
        foreach ($ormMeta as $entityName => $entityParams) {
            if (!isset($entityParams['relations'])) {
                continue;
            }

            foreach ($entityParams['relations'] as $relationName => $relationParams) {

                 switch ($relationParams['type']) {
                    case 'manyMany':
                        $tableName = $relationParams['relationName'];

                        // check for duplicate tables
                        if (!isset($tables[$tableName])) { //no needs to create the table if it already exists
                            $tables[$tableName] = $this->prepareManyMany($entityName, $relationParams, $tables);
                        }

                        break;
                }
            }
        }

        $this->log->debug('Schema\Converter - End: building schema');

        return $schema;
    }

    /**
     * Prepare a relation table for the manyMany relation.
     *
     * @param string $entityName
     * @param array $relationParams
     * @param array $tables
     *
     * @return \Doctrine\DBAL\Schema\Table
     */
    protected function prepareManyMany($entityName, $relationParams, $tables)
    {
        $tableName = Util::toUnderScore($relationParams['relationName']);

        $this->log->debug('DBAL: prepareManyMany invoked for ' . $entityName, [
            'tableName' => $tableName, 'parameters' => $relationParams
        ]);

        if ($this->getSchema()->hasTable($tableName)) {
            $this->log->debug('DBAL: Table ['.$tableName.'] exists.');

            return $this->getSchema()->getTable($tableName);
        }

        $table = $this->getSchema()->createTable($tableName);

        $idColumnOptions = $this->getDbFieldParams([
            'type' => 'id',
            'len' => 20,
            'autoincrement' => true,
        ]);

        $table->addColumn('id', 'bigint', $idColumnOptions);

        // add midKeys to a schema
        $uniqueIndex = [];

        if (empty($relationParams['midKeys'])) {
            $this->log->debug('REBUILD: midKeys are empty!', [
                'scope' => $entityName, 'tableName' => $tableName,
                'parameters' => $relationParams
            ]);
        }
        else {
            foreach($relationParams['midKeys'] as $midKey) {
                $columnName = Util::toUnderScore($midKey);

                $table->addColumn(
                    $columnName,
                    $this->idParams['dbType'],
                    $this->getDbFieldParams([
                        'type' => 'foreignId',
                        'len' => $this->idParams['len'],
                    ])
                );

                $table->addIndex([$columnName], SchemaUtils::generateIndexName($columnName));

                $uniqueIndex[] = $columnName;
            }
        }

        // add additionalColumns
        if (!empty($relationParams['additionalColumns'])) {
            foreach($relationParams['additionalColumns'] as $fieldName => $fieldParams) {
                if (!isset($fieldParams['type'])) {
                    $fieldParams = array_merge($fieldParams, [
                        'type' => 'varchar',
                        'len' => $this->defaultLength['varchar'],
                    ]);
                }

                $table->addColumn(
                    Util::toUnderScore($fieldName),
                    $fieldParams['type'],
                    $this->getDbFieldParams($fieldParams)
                );
            }
        }

        $table->addColumn(
            'deleted',
            'bool',
            $this->getDbFieldParams([
                'type' => 'bool',
                'default' => false,
            ])
        );

        $table->setPrimaryKey(['id']);

        // add defined indexes
        if (!empty($relationParams['indexes'])) {
            $normalizedIndexes = SchemaUtils::getIndexes([
                $entityName => [
                    'fields' => [],
                    'indexes' => $relationParams['indexes'],
                ]
            ]);

            $this->addIndexes($table, $normalizedIndexes[$entityName]);
        }

        // add unique indexes
        if (!empty($relationParams['conditions'])) {
            foreach ($relationParams['conditions'] as $fieldName => $fieldParams) {
                $uniqueIndex[] = Util::toUnderScore($fieldName);
            }
        }

        if (!empty($uniqueIndex)) {
            /** @var string[] $uniqueIndex */
            $uniqueIndexName = implode('_', $uniqueIndex);

            $table->addUniqueIndex(
                $uniqueIndex,
                SchemaUtils::generateIndexName($uniqueIndexName, 'unique')
            );
        }

        return $table;
    }

    protected function addIndexes($table, array $indexes)
    {
        foreach($indexes as $indexName => $indexParams) {
            $indexType = !empty($indexParams['type']) ?
                $indexParams['type'] :
                SchemaUtils::getIndexTypeByIndexDefs($indexParams);

            switch ($indexType) {
                case 'index':
                case 'fulltext':
                    $indexFlagList = isset($indexParams['flags']) ? $indexParams['flags'] : [];

                    $table->addIndex($indexParams['columns'], $indexName, $indexFlagList);

                    break;

                case 'unique':
                    $table->addUniqueIndex($indexParams['columns'], $indexName);

                    break;
            }
        }
    }

    protected function getDbFieldParams($fieldParams)
    {
        $dbFieldParams = [
            'notnull' => false,
        ];

        foreach($this->allowedDbFieldParams as $espoName => $dbalName) {
            if (isset($fieldParams[$espoName])) {
                $dbFieldParams[$dbalName] = $fieldParams[$espoName];
            }
        }

        $databaseParams = $this->config->get('database');

        if (!isset($databaseParams['charset']) || $databaseParams['charset'] == 'utf8mb4') {
            $dbFieldParams['platformOptions'] = [
                'collation' => 'utf8mb4_unicode_ci',
            ];
        }

        switch ($fieldParams['type']) {
            case 'id':
            case 'foreignId':
            case 'foreignType':
                if ($this->getMaxIndexLength() < 3072) {
                    $fieldParams['utf8mb3'] = true;
                }

                break;

            case 'array':
            case 'jsonArray':
            case 'text':
            case 'longtext':
                unset($dbFieldParams['default']); // for db type TEXT can't be defined a default value

                break;

            case 'bool':
                $default = false;

                if (array_key_exists('default', $dbFieldParams)) {
                    $default = $dbFieldParams['default'];
                }

                $dbFieldParams['default'] = intval($default);

                break;
        }

        if (
            $fieldParams['type'] != 'id' &&
            isset($fieldParams['autoincrement']) &&
            $fieldParams['autoincrement']
        ) {
            $dbFieldParams['notnull'] = true;
            $dbFieldParams['unsigned'] = true;
        }

        if (isset($fieldParams['binary']) && $fieldParams['binary']) {
            $dbFieldParams['platformOptions'] = [
                'collation' => 'utf8mb4_bin',
            ];
        }

        if (isset($fieldParams['utf8mb3']) && $fieldParams['utf8mb3']) {
            $dbFieldParams['platformOptions'] = [
                'collation' =>
                    (isset($fieldParams['binary']) && $fieldParams['binary']) ?
                    'utf8_bin' :
                    'utf8_unicode_ci',
            ];
        }

        return $dbFieldParams;
    }

    /**
     * Get custom table definition in
     * `application/Espo/Core/Utils/Database/Schema/tables/` and in metadata 'additionalTables'.
     *
     * @param array $ormMeta
     *
     * @return array
     */
    protected function getCustomTables(array $ormMeta)
    {
        $customTables = $this->loadData($this->pathProvider->getCore() . $this->tablesPath);

        foreach ($this->metadata->getModuleList() as $moduleName) {
            $modulePath = $this->pathProvider->getModule($moduleName) . $this->tablesPath;

            $customTables = Util::merge(
                $customTables,
                $this->loadData($modulePath)
            );
        }

        $customTables = Util::merge(
            $customTables,
            $this->loadData($this->pathProvider->getCustom() . $this->tablesPath)
        );

        // Get custom tables from metdata 'additionalTables'.
        foreach ($ormMeta as $entityParams) {
            if (isset($entityParams['additionalTables']) && is_array($entityParams['additionalTables'])) {
                $customTables = Util::merge($customTables, $entityParams['additionalTables']);
            }
        }

        return $customTables;
    }

    protected function getDependentEntities($entityList, $ormMeta, $dependentEntities = [])
    {
        if (is_string($entityList)) {
            $entityList = (array) $entityList;
        }

        foreach ($entityList as $entityName) {

            if (in_array($entityName, $dependentEntities)) {
                continue;
            }

            $dependentEntities[] = $entityName;

            if (array_key_exists('relations', $ormMeta[$entityName])) {
                foreach ($ormMeta[$entityName]['relations'] as $relationName => $relationParams) {
                    if (isset($relationParams['entity'])) {
                        $relationEntity = $relationParams['entity'];

                        if (!in_array($relationEntity, $dependentEntities)) {
                            $dependentEntities = $this->getDependentEntities(
                                $relationEntity,
                                $ormMeta,
                                $dependentEntities
                            );
                        }
                    }
                }
            }

        }

        return $dependentEntities;
    }

    protected function loadData($path)
    {
        $tables = [];

        if (!file_exists($path)) {
            return $tables;
        }

        $fileList = $this->fileManager->getFileList($path, false, '\.php$', true);

        foreach($fileList as $fileName) {
            $fileData = $this->fileManager->getPhpContents($path . '/' . $fileName);

            if (is_array($fileData)) {
                $tables = Util::merge($tables, $fileData);
            }
        }

        return $tables;
    }
}
