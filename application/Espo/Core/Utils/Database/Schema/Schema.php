<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

use Doctrine\DBAL\{
    Types\Type,
    Schema\SchemaDiff as DBALSchemaDiff,
    Schema\Schema as DBALSchema,
    Connection,
    Platforms\AbstractPlatform,
};

use Espo\Core\{
    Utils\Config,
    Utils\Metadata,
    Utils\File\Manager as FileManager,
    ORM\EntityManager,
    Utils\File\ClassMap,
    Utils\Metadata\OrmMetadataData,
    Utils\Util,
    Utils\Database\Helper,
    Utils\Database\DBAL\Schema\Comparator,
    Utils\Database\Converter as DatabaseConverter,
    Utils\Log,
    Utils\Module\PathProvider,
};

use Throwable;

class Schema
{
    private Config $config;

    private Metadata $metadata;

    private FileManager $fileManager;

    private EntityManager $entityManager;

    private ClassMap $classMap;

    private Comparator $comparator;

    private DatabaseConverter $converter;

    private Helper $databaseHelper;

    protected OrmMetadataData $ormMetadataData;

    private Log $log;

    private string $fieldTypePath = 'application/Espo/Core/Utils/Database/DBAL/FieldTypes';

    private string $rebuildActionsPath = 'Core/Utils/Database/Schema/rebuildActions';

    private Converter $schemaConverter;

    /**
     * @var ?array{
     *   beforeRebuild: \Espo\Core\Utils\Database\Schema\BaseRebuildActions[],
     *   afterRebuild: \Espo\Core\Utils\Database\Schema\BaseRebuildActions[],
     * }
     */
    protected $rebuildActions = null;

    public function __construct(
        Config $config,
        Metadata $metadata,
        FileManager $fileManager,
        EntityManager $entityManager,
        ClassMap $classMap,
        OrmMetadataData $ormMetadataData,
        Log $log,
        PathProvider $pathProvider
    ) {
        $this->config = $config;
        $this->metadata = $metadata;
        $this->fileManager = $fileManager;
        $this->entityManager = $entityManager;
        $this->classMap = $classMap;
        $this->log = $log;

        $this->databaseHelper = new Helper($this->config);
        $this->comparator = new Comparator();

        $this->initFieldTypes();

        $this->converter = new DatabaseConverter($this->metadata, $this->fileManager, $this->config);

        $this->schemaConverter = new Converter(
            $this->metadata,
            $this->fileManager,
            $this,
            $this->config,
            $this->log,
            $pathProvider
        );

        $this->ormMetadataData = $ormMetadataData;
    }

    public function getDatabaseHelper(): Helper
    {
        return $this->databaseHelper;
    }

    public function getPlatform(): AbstractPlatform
    {
        return $this->getConnection()->getDatabasePlatform();
    }

    public function getConnection(): Connection
    {
        return $this->getDatabaseHelper()->getDbalConnection();
    }

    protected function initFieldTypes(): void
    {
        /** @var string[] $typeList */
        $typeList = $this->fileManager->getFileList($this->fieldTypePath, false, '\.php$');

        foreach ($typeList as $name) {
            /** @var string $typeName */
            $typeName = preg_replace('/Type\.php$/i', '', $name);
            $dbalTypeName = strtolower($typeName);

            $filePath = Util::concatPath($this->fieldTypePath, $typeName . 'Type');

            /** @var class-string<\Doctrine\DBAL\Types\Type> $class */
            $class = Util::getClassName($filePath);

            if (!Type::hasType($dbalTypeName)) {
                Type::addType($dbalTypeName, $class);
            }
            else {
                Type::overrideType($dbalTypeName, $class);
            }

            if (method_exists($class, 'getDbTypeName')) {
                /** @var callable $getDbTypeNameCallable */
                $getDbTypeNameCallable = [$class, 'getDbTypeName'];

                $dbTypeName = call_user_func($getDbTypeNameCallable);
            }
            else {
                $dbTypeName = $dbalTypeName;
            }

            $this->getConnection()
                ->getDatabasePlatform()
                ->registerDoctrineTypeMapping($dbTypeName, $dbalTypeName);
        }
    }

    /**
     * Rebuild database schema.
     *
     * @param ?string[] $entityList
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function rebuild(?array $entityList = null): bool
    {
        if (!$this->converter->process()) {
            return false;
        }

        $currentSchema = $this->getCurrentSchema();

        $metadataSchema = $this->schemaConverter->process($this->ormMetadataData->getData(), $entityList);

        $this->initRebuildActions($currentSchema, $metadataSchema);

        try {
            $this->executeRebuildActions('beforeRebuild');
        }
        catch (Throwable $e) {
            $this->log->alert('Rebuild database fault: '. $e);

            return false;
        }

        $queries = $this->getDiffSql($currentSchema, $metadataSchema);

        $result = true;
        $connection = $this->getConnection();

        foreach ($queries as $sql) {
            $this->log->info('SCHEMA, Execute Query: '.$sql);

            try {
                $result &= (bool) $connection->executeQuery($sql);
            }
            catch (Throwable $e) {
                $this->log->alert('Rebuild database fault: '. $e);

                $result = false;
            }
        }

        try {
            $this->executeRebuildActions('afterRebuild');
        }
        catch (Throwable $e) {
            $this->log->alert('Rebuild database fault: '. $e);

            return false;
        }

        return (bool) $result;
    }

    /**
     * Get current database schema.
     */
    protected function getCurrentSchema(): DBALSchema
    {
        return $this->getConnection()
            ->getSchemaManager()
            ->createSchema();
    }

    /**
     * Get SQL queries of database schema.
     *
     * @return string[] Array of SQL queries.
     */
    public function toSql(DBALSchemaDiff $schema)
    {
        return $schema->toSaveSql($this->getPlatform());
    }

    /**
     * Get SQL queries to get from one to another schema.
     *
     * @return string[] Array of SQL queries.
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function getDiffSql(DBALSchema $fromSchema, DBALSchema $toSchema)
    {
        $schemaDiff = $this->comparator->compare($fromSchema, $toSchema);

        return $this->toSql($schemaDiff);
    }

    /**
     * Init Rebuild Actions, get all classes and create them.
     */
    protected function initRebuildActions(?DBALSchema $currentSchema = null, ?DBALSchema $metadataSchema = null): void
    {
        $methodList = [
            'beforeRebuild',
            'afterRebuild',
        ];

        /** @var array<string,class-string<\Espo\Core\Utils\Database\Schema\BaseRebuildActions>> $classes */
        $classes = $this->classMap->getData($this->rebuildActionsPath, null, $methodList);

        $objects = [
            'beforeRebuild' => [],
            'afterRebuild' => [],
        ];

        foreach ($classes as $className) {
            $actionObj = new $className(
                $this->metadata,
                $this->config,
                $this->entityManager,
                $this->log
            );

            if (isset($currentSchema)) {
                $actionObj->setCurrentSchema($currentSchema);
            }

            if (isset($metadataSchema)) {
                $actionObj->setMetadataSchema($metadataSchema);
            }

            foreach ($methodList as $methodName) {
                if (method_exists($actionObj, $methodName)) {
                    $objects[$methodName][] = $actionObj;
                }
            }
        }

        $this->rebuildActions = $objects;
    }

    /**
     * Execute actions for RebuildAction classes.
     *
     * @param 'beforeRebuild'|'afterRebuild' $action An action name, 'beforeRebuild' or 'afterRebuild'.
     */
    protected function executeRebuildActions(string $action = 'beforeRebuild'): void
    {
        if (!isset($this->rebuildActions)) {
            $this->initRebuildActions();
        }

        assert($this->rebuildActions !== null);

        foreach ($this->rebuildActions[$action] as $rebuildActionClass) {
            $rebuildActionClass->$action();
        }
    }
}
