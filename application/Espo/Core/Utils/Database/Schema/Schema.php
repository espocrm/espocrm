<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014  Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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
 ************************************************************************/
namespace Espo\Core\Utils\Database\Schema;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\SchemaDiff;
use Doctrine\DBAL\Types\Type;
use Espo\Core\ORM\EntityManager;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Database\Converter;
use Espo\Core\Utils\Database\DBAL\Schema\Comparator;
use Espo\Core\Utils\File\ClassParser;
use Espo\Core\Utils\File\Manager;
use Espo\Core\Utils\Log;
use Espo\Core\Utils\Metadata;
use Espo\Core\Utils\Util;

class Schema
{

    protected $drivers = array(
        'mysqli' => '\Espo\Core\Utils\Database\DBAL\Driver\Mysqli\Driver',
        'pdo_mysql' => '\Espo\Core\Utils\Database\DBAL\Driver\PDOMySql\Driver',
    );

    protected $fieldTypePaths = array(
        'application/Espo/Core/Utils/Database/DBAL/FieldTypes',
        'custom/Espo/Custom/Core/Utils/Database/DBAL/FieldTypes',
    );

    /**
     * Paths of rebuild action folders
     *
     * @var array
     */
    protected $rebuildActionsPath = array(
        'corePath' => 'application/Espo/Core/Utils/Database/Schema/rebuildActions',
        'customPath' => 'custom/Espo/Custom/Core/Utils/Database/Schema/rebuildActions',
    );

    /**
     * Array of rebuildActions classes in format:
     *  array(
     *      'beforeRebuild' => array(...),
     *      'afterRebuild' => array(...),
     *  )
     *
     * @var array
     */
    protected $rebuildActionClasses = null;

    private $config;

    private $metadata;

    private $fileManager;

    private $entityManager;

    private $classParser;

    private $comparator;

    private $converter;

    private $connection;

    public function __construct(
        Config $config,
        Metadata $metadata,
        Manager $fileManager,
        EntityManager $entityManager,
        ClassParser $classParser
    ){
        $this->config = $config;
        $this->metadata = $metadata;
        $this->fileManager = $fileManager;
        $this->entityManager = $entityManager;
        $this->classParser = $classParser;
        $this->comparator = new Comparator();
        $this->initFieldTypes();
        $this->converter = new Converter($this->metadata, $this->fileManager);
    }

    protected function initFieldTypes()
    {
        foreach ($this->fieldTypePaths as $path) {
            $typeList = $this->getFileManager()->getFileList($path, false, '\.php$');
            if ($typeList !== false) {
                foreach ($typeList as $name) {
                    $typeName = preg_replace('/\.php$/i', '', $name);
                    $dbalTypeName = strtolower($typeName);
                    $filePath = Util::concatPath($path, $typeName);
                    $class = Util::getClassName($filePath);
                    if (!Type::hasType($dbalTypeName)) {
                        Type::addType($dbalTypeName, $class);
                    } else {
                        Type::overrideType($dbalTypeName, $class);
                    }
                    $dbTypeName = method_exists($class, 'getDbTypeName') ? $class::getDbTypeName() : $dbalTypeName;
                    $this->getConnection()->getDatabasePlatform()->registerDoctrineTypeMapping($dbTypeName,
                        $dbalTypeName);
                }
            }
        }
    }

    protected function getFileManager()
    {
        return $this->fileManager;
    }

    public function getConnection()
    {
        if (isset($this->connection)) {
            return $this->connection;
        }
        $dbalConfig = new Configuration();
        $connectionParams = $this->getConfig()->get('database');
        $connectionParams['driverClass'] = $this->drivers[$connectionParams['driver']];
        unset($connectionParams['driver']);
        $this->connection = DriverManager::getConnection($connectionParams, $dbalConfig);
        return $this->connection;
    }

    protected function getConfig()
    {
        return $this->config;
    }

    public function rebuild($entityList = null)
    {
        /**
         * @var Log $log
         */
        if ($this->getConverter()->process() === false) {
            return false;
        }
        $currentSchema = $this->getCurrentSchema();
        $metadataSchema = $this->getConverter()->getSchemaFromMetadata($entityList);
        $this->initRebuildActions($currentSchema, $metadataSchema);
        $this->executeRebuildActions('beforeRebuild');
        $queries = $this->getDiffSql($currentSchema, $metadataSchema);
        $result = true;
        $connection = $this->getConnection();
        $log = $GLOBALS['log'];
        foreach ($queries as $sql) {
            $log->debug('SCHEMA, Execute Query: ' . $sql);
            try{
                $result &= (bool)$connection->executeQuery($sql);
            } catch(\Exception $e){
                $log->alert('Rebuild database fault: ' . $e);
                $result = false;
            }
        }
        $this->executeRebuildActions('afterRebuild');
        return (bool)$result;
    }

    protected function getConverter()
    {
        return $this->converter;
    }

    protected function getCurrentSchema()
    {
        return $this->getConnection()->getSchemaManager()->createSchema();
    }

    /**
     * Init Rebuild Actions, get all classes and create them
     *
     * @param null $currentSchema
     * @param null $metadataSchema
     *
     * @throws \Espo\Core\Exceptions\Error
     * @return void
     */
    protected function initRebuildActions($currentSchema = null, $metadataSchema = null)
    {
        /**
         * @var BaseRebuildActions $rebuildActionClass
         */
        $methods = array('beforeRebuild', 'afterRebuild');
        $this->getClassParser()->setAllowedMethods($methods);
        $rebuildActions = $this->getClassParser()->getData($this->rebuildActionsPath);
        $classes = array();
        foreach ($rebuildActions as $actionName => $actionClass) {
            $rebuildActionClass = new $actionClass($this->metadata, $this->config, $this->entityManager);
            if (isset($currentSchema)) {
                $rebuildActionClass->setCurrentSchema($currentSchema);
            }
            if (isset($metadataSchema)) {
                $rebuildActionClass->setMetadataSchema($metadataSchema);
            }
            foreach ($methods as $methodName) {
                if (method_exists($rebuildActionClass, $methodName)) {
                    $classes[$methodName][] = $rebuildActionClass;
                }
            }
        }
        $this->rebuildActionClasses = $classes;
    }

    protected function getClassParser()
    {
        return $this->classParser;
    }

    /**
     * Execute actions for RebuildAction classes
     *
     * @param  string $action action name, possible values 'beforeRebuild' | 'afterRebuild'
     *
     * @return void
     */
    protected function executeRebuildActions($action = 'beforeRebuild')
    {
        if (!isset($this->rebuildActionClasses)) {
            $this->initRebuildActions();
        }
        if (isset($this->rebuildActionClasses[$action])) {
            foreach ($this->rebuildActionClasses[$action] as $rebuildActionClass) {
                $rebuildActionClass->$action();
            }
        }
    }

    /*
     * Rebuild database schema
     */
    public function getDiffSql(\Doctrine\DBAL\Schema\Schema $fromSchema, \Doctrine\DBAL\Schema\Schema $toSchema)
    {
        $schemaDiff = $this->getComparator()->compare($fromSchema, $toSchema);
        return $this->toSql($schemaDiff); //$schemaDiff->toSql($this->getPlatform());
    }

    /*
    * Get current database schema
    *
    * @return \Doctrine\DBAL\Schema\Schema
    */
    protected function getComparator()
    {
        return $this->comparator;
    }

    /*
    * Get SQL queries of database schema
    *
    * @params \Doctrine\DBAL\Schema\Schema $schema
    *
    * @return array - array of SQL queries
    */
    public function toSql(
        SchemaDiff $schema
    )   //Doctrine\DBAL\Schema\SchemaDiff | \Doctrine\DBAL\Schema\Schema
    {
        return $schema->toSaveSql($this->getPlatform());
        //return $schema->toSql($this->getPlatform()); //it can return with DROP TABLE
    }

    /*
    * Get SQL queries to get from one to another schema
    *
    * @return array - array of SQL queries
    */
    public function getPlatform()
    {
        return $this->getConnection()->getDatabasePlatform();
    }

    protected function getMetadata()
    {
        return $this->metadata;
    }

    protected function getEntityManager()
    {
        return $this->entityManager;
    }
}
