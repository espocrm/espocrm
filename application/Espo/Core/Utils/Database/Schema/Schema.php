<?php

namespace Espo\Core\Utils\Database\Schema;

use \Doctrine\DBAL\Types\Type;

class Schema
{
	private $config;

	private $metadata;

	private $fileManager;

	private $entityManager;

	private $comparator;

	private $converter;

	private $connection;

	protected $drivers = array(
		'mysqli' => '\Espo\Core\Utils\Database\DBAL\Driver\Mysqli\Driver',		
		'pdo_mysql' => '\Espo\Core\Utils\Database\DBAL\Driver\PDOMySql\Driver',		
	);

	protected $fieldTypePaths = array(
		'Espo/Core/Utils/Database/DBAL/FieldTypes',
		'Espo/Custom/Core/Utils/Database/DBAL/FieldTypes',
	);


	public function __construct(\Espo\Core\Utils\Config $config, \Espo\Core\Utils\Metadata $metadata, \Espo\Core\Utils\File\Manager $fileManager, \Espo\Core\ORM\EntityManager $entityManager)
	{
		$this->config = $config;
		$this->metadata = $metadata;
		$this->fileManager = $fileManager;
		$this->entityManager = $entityManager;

		$this->comparator = new \Espo\Core\Utils\Database\DBAL\Schema\Comparator();
		$this->initFieldTypes();

		$this->converter = new \Espo\Core\Utils\Database\Converter($this->metadata, $this->fileManager);
	}


	protected function getConfig()
	{
		return $this->config;
	}

	protected function getMetadata()
	{
		return $this->metadata;
	}

	protected function getFileManager()
	{
		return $this->fileManager;
	}

	protected function getEntityManager()
	{
		return $this->entityManager;
	}

	protected function getComparator()
	{
		return $this->comparator;
	}

	protected function getConverter()
	{
		return $this->converter;
	}

	public function getPlatform()
	{
    	return $this->getConnection()->getDatabasePlatform();
	}


	public function getConnection()
	{
		if (isset($this->connection)) {
        	return $this->connection;
		}

		$dbalConfig = new \Doctrine\DBAL\Configuration();

		$connectionParams = (array) $this->getConfig()->get('database');
		
		$connectionParams['driverClass'] = $this->drivers[ $connectionParams['driver'] ];
		unset($connectionParams['driver']);

		$this->connection = \Doctrine\DBAL\DriverManager::getConnection($connectionParams, $dbalConfig);

        return $this->connection;
	}


	protected function initFieldTypes()
	{
		foreach($this->fieldTypePaths as $path) {

        	$typeList = $this->getFileManager()->getFileList('application/'.$path, false, '\.php$');
			if ($typeList !== false) {
            	foreach($typeList as $name) {
					$typeName = preg_replace('/\.php$/i', '', $name);
					$dbalTypeName = strtolower($typeName);
					$class = \Espo\Core\Utils\Util::toFormat($path, '\\').'\\'.$typeName;

					if( ! Type::hasType($dbalTypeName) ) {
						Type::addType($dbalTypeName, $class);
			        } else {
                    	Type::overrideType($dbalTypeName, $class);
			        }

					$dbTypeName = method_exists($class, 'getDbTypeName') ? $class::getDbTypeName() : $dbalTypeName;

                    $this->getConnection()->getDatabasePlatform()->registerDoctrineTypeMapping($dbTypeName, $dbalTypeName);  
				}
			}
		}
	}



	/*
	 * Rebuild database schema
	 */
	public function rebuild()
	{
		if ($this->getConverter()->process() === false) {
        	return false;
		} 

		$currentSchema = $this->getCurrentSchema();
		$metadataSchema = $this->getConverter()->getSchemaFromMetadata();

		$queries = $this->getDiffSql($currentSchema, $metadataSchema);

		$result = true;
		$connection = $this->getConnection();
		foreach ($queries as $sql) {
			$GLOBALS['log']->add('DEBUG', 'SCHEMA, Execute Query: '.$sql);
			try {
            	$result &= (bool) $connection->executeQuery($sql);
			} catch (\Exception $e) {
				$GLOBALS['log']->add('EXCEPTION', 'Rebuild database fault: '.$e);
				$result = false;
			}
        }

        //addSystemUser
        $this->addSystemUser();
        //END: addSystemUser

		return (bool) $result;
	}


	protected function addSystemUser()
	{
		$userId = $this->getConfig()->get('systemUser.id');

		$entity = $this->getEntityManager()->getEntity('User', $userId);

		if (!isset($entity)) {

			$systemUser = (array) $this->getConfig()->get('systemUser');

			$entity = $this->getEntityManager()->getEntity('User');
			$entity->set($systemUser);			

			return $this->getEntityManager()->saveEntity($entity);			
		}		

		return true;
	}



	/*
	* Get current database schema
	*
	* @return \Doctrine\DBAL\Schema\Schema
	*/
	protected function getCurrentSchema()
	{
		return $this->getConnection()->getSchemaManager()->createSchema();
	}

    /*
	* Get SQL queries of database schema
	*
	* @params \Doctrine\DBAL\Schema\Schema $schema
    *
	* @return array - array of SQL queries
	*/
	public function toSql(\Doctrine\DBAL\Schema\SchemaDiff $schema)   //Doctrine\DBAL\Schema\SchemaDiff | \Doctrine\DBAL\Schema\Schema
	{
		return $schema->toSaveSql($this->getPlatform());
		//return $schema->toSql($this->getPlatform()); //it can return with DROP TABLE
	}


	/*
	* Get SQL queries to get from one to another schema
	*
	* @return array - array of SQL queries
	*/
	public function getDiffSql(\Doctrine\DBAL\Schema\Schema $fromSchema, \Doctrine\DBAL\Schema\Schema $toSchema)
	{
		$schemaDiff = $this->getComparator()->compare($fromSchema, $toSchema);

		return $this->toSql($schemaDiff); //$schemaDiff->toSql($this->getPlatform());
	}




}
