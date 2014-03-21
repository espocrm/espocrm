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

use Espo\Core\Utils\Util,
	Espo\ORM\Entity;


class Converter
{
	private $dbalSchema;
	private $fileManager;

	private $customTablePath = 'application/Espo/Core/Utils/Database/Schema/tables';

	protected $typeList;

	//pair ORM => doctrine
	protected $allowedDbFieldParams = array(
		'len' => 'length',
		'default' => 'default',
		'notNull' => 'notnull',
		'autoincrement' => 'autoincrement',
		'unique' => 'unique',
	);


	//todo: same array in Converters\Orm
	protected $idParams = array(
		'dbType' => 'varchar',
		'len' => '24',
	);

    //todo: same array in Converters\Orm
	protected $defaultLength = array(
		'varchar' => 255,
		'int' => 11,
	);

	protected $notStorableTypes = array(
		'foreign'
	);

	public function __construct(\Espo\Core\Utils\File\Manager $fileManager)
	{
		$this->fileManager = $fileManager;

		$this->dbalSchema = new \Doctrine\DBAL\Schema\Schema();

		$this->typeList = array_keys(\Doctrine\DBAL\Types\Type::getTypesMap());
	}


	protected function getFileManager()
	{
    	return $this->fileManager;
	}

	protected function getSchema()
	{
    	return $this->dbalSchema;
	}


	public function process(array $ormMeta, $entityDefs)
	{
    	$GLOBALS['log']->debug('Schema\Converter - Start: building schema');

		//check if exist files in "Tables" directory and merge with ormMetadata
        $ormMeta = Util::merge($ormMeta, $this->getCustomTables());

        //unset some keys in orm
        if (isset($ormMeta['unset'])) {
			$ormMeta = Util::unsetInArray($ormMeta, $ormMeta['unset']);
			unset($ormMeta['unset']);
		} //END: unset some keys in orm


		$schema = $this->getSchema();

		$tables = array();
		foreach ($ormMeta as $entityName => $entityParams) {

            $tables[$entityName] = $schema->createTable( Util::toUnderScore($entityName) );

			$primaryColumns = array();
			$uniqueColumns = array();
			$indexList = array(); //list of indexes like array( array(comlumn1, column2), array(column3))
        	foreach ($entityParams['fields'] as $fieldName => $fieldParams) {

				if ((isset($fieldParams['notStorable']) && $fieldParams['notStorable']) || in_array($fieldParams['type'], $this->notStorableTypes)) {
					continue;
				}

				switch ($fieldParams['type']) {
		            case 'id':
                        $primaryColumns[] = Util::toUnderScore($fieldName);
						break;
		        }

                $fieldType = isset($fieldParams['dbType']) ? $fieldParams['dbType'] : $fieldParams['type'];
				if (!in_array($fieldType, $this->typeList)) {
                	$GLOBALS['log']->debug('Converters\Schema::process(): Field type ['.$fieldType.'] does not exist '.$entityName.':'.$fieldName);
					continue;
				}

				$columnName = Util::toUnderScore($fieldName);
				if (!$tables[$entityName]->hasColumn($columnName)) {
                	$tables[$entityName]->addColumn($columnName, $fieldType, $this->getDbFieldParams($fieldParams));
				}

				//add unique
				if ($fieldParams['type']!= 'id' && isset($fieldParams['unique'])) {
		        	$uniqueColumns = $this->getKeyList($columnName, $fieldParams['unique'], $uniqueColumns);
		        } //END: add unique

				//add index. It can be defined in entityDefs as "index"
				if (isset($fieldParams['index'])) {
					$indexList = $this->getKeyList($columnName, $fieldParams['index'], $indexList);
				} //END: add index
			}

            $tables[$entityName]->setPrimaryKey($primaryColumns);
			if (!empty($indexList)) {
				foreach($indexList as $indexItem) {
                	$tables[$entityName]->addIndex($indexItem);
				}
			}

			if (!empty($uniqueColumns)) {
				foreach($uniqueColumns as $uniqueItem) {
                	$tables[$entityName]->addUniqueIndex($uniqueItem);
				}
			}
		}

		//check and create columns/tables for relations
        foreach ($ormMeta as $entityName => $entityParams) {

			if (!isset($entityParams['relations'])) {
				continue;
			}

        	foreach ($entityParams['relations'] as $relationName => $relationParams) {

                 switch ($relationParams['type']) {
		            case 'manyMany':
						$tableName = $relationParams['relationName'];

                        //check for duplication tables
						if (!isset($tables[$tableName])) { //no needs to create the table if it already exists
                        	$tables[$tableName] = $this->prepareManyMany($entityName, $relationParams, $tables);
						}
						break;

		            case 'belongsTo':
						$foreignEntity = $relationParams['entity'];
						$columnName = Util::toUnderScore($relationParams['key']);
						$foreignKey = Util::toUnderScore($relationParams['foreignKey']);
		                $tables[$entityName]->addForeignKeyConstraint($tables[$foreignEntity], array($columnName), array($foreignKey), array("onUpdate" => "CASCADE"));
		                break;
		        }
			}
        }
		//END: check and create columns/tables for relations


		$GLOBALS['log']->debug('Schema\Converter - End: building schema');

		return $schema;
	}

	/**
     * Prepare a relation table for the manyMany relation
     *
     * @param array $relationParams
     * @param array $tables
     * @param bool $isForeignKey
	 *
     * @return \Doctrine\DBAL\Schema\Table
     */
	protected function prepareManyMany($entityName, $relationParams, $tables)
	{
    	$tableName = $relationParams['relationName'];

        $isForeignKey = true;
		if (!isset($relationParams['key']) || !isset($relationParams['foreignKey'])) {
        	$isForeignKey = false;
		}


		$table = $this->getSchema()->createTable( Util::toUnderScore($tableName) );
		$table->addColumn('id', 'int', array('length'=>$this->defaultLength['int'], 'autoincrement' => true,));  //'unique' => true,

		if ($isForeignKey) {
			$relationEntities = array($entityName, $relationParams['entity']);
			$relationKeys = array($relationParams['key'], $relationParams['foreignKey']);
		}

		//add midKeys to a schema
		foreach($relationParams['midKeys'] as $index => $midKey) {

			$usMidKey = Util::toUnderScore($midKey);
            $table->addColumn($usMidKey, $this->idParams['dbType'], array('length'=>$this->idParams['len']));

			if ($isForeignKey) {
            	$relationKey = Util::toUnderScore($relationKeys[$index]);
				$table->addForeignKeyConstraint($tables[$relationEntities[$index]], array($usMidKey), array($relationKey), array("onUpdate" => "CASCADE"));
			} else {
            	$table->addIndex(array($usMidKey));
			}
		} //END: add midKeys to a schema


		//add additionalColumns
		if (isset($relationParams['additionalColumns'])) {
			foreach($relationParams['additionalColumns'] as $fieldName => $fieldParams) {

				if (!isset($fieldParams['type'])) {
                	$fieldParams = array_merge($fieldParams, array(
						'type' => 'varchar',
						'length' => $this->defaultLength['varchar'],
					));
				}

            	$table->addColumn(Util::toUnderScore($fieldName), $fieldParams['type'], $this->getDbFieldParams($fieldParams));
			}
		} //END: add additionalColumns


		$table->addColumn('deleted', 'bool', array('default' => 0));
		$table->setPrimaryKey(array("id"));

        return $table;
	}


	protected function getDbFieldParams($fieldParams)
	{
		$dbFieldParams = array();

		foreach($this->allowedDbFieldParams as $espoName => $dbalName) {

        	if (isset($fieldParams[$espoName])) {
            	$dbFieldParams[$dbalName] = $fieldParams[$espoName];
			}
		}

		switch ($fieldParams['type']) {
            case 'array':
            case 'json_array':
                $dbFieldParams['default'] = ''; //for db type TEXT can't be defined a default value
                break;

			case 'bool':
	            $dbFieldParams['default'] = intval($dbFieldParams['default']);
	            break;
        }


        if ( isset($fieldParams['autoincrement']) && $fieldParams['autoincrement'] ) {
        	$dbFieldParams['unique'] = true;
        }

		return $dbFieldParams;
	}

	/**
	 * Get key list (index, unique). Ex. index => true OR index => 'somename'
	 * @param  string $columnName Column name (underscore field name)
	 * @param  bool | string $keyValue
	 * @return array
	 */
	protected function getKeyList($columnName, $keyValue, array $keyList)
	{
		if ($keyValue === true) {
        	$keyList[] = array($columnName);
		} else if (is_string($keyValue)) {
            $keyList[$keyValue][] = $columnName;
		}

		return $keyList;
	}


	/*
	 * @return array - ormMeta
	 */
	protected function getCustomTables()
	{
		$customTables = array();

		$fileList = $this->getFileManager()->getFileList($this->customTablePath, false, '\.php$', 'file');

		foreach($fileList as $fileName) {
			$fileData = $this->getFileManager()->getContents( array($this->customTablePath, $fileName) );
			if (is_array($fileData)) {
            	$customTables = Util::merge($customTables, $fileData);
			}
		}

        return $customTables;
	}


}