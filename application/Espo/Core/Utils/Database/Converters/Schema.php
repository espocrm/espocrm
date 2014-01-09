<?php

namespace Espo\Core\Utils\Database\Converters;

use Espo\Core\Utils\Util,
	Espo\ORM\Entity;


class Schema
{
	private $dbalSchema;

	protected $typeList;

	//pair espo::doctrine
	protected $allowedDbFieldParams = array(
		'len' => 'length',
		'default' => 'default',
		'notnull' => 'notnull',
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

	public function __construct()
	{
		$this->dbalSchema = new \Doctrine\DBAL\Schema\Schema();

		$this->typeList = array_keys(\Doctrine\DBAL\Types\Type::getTypesMap());
	}

	protected function getSchema()
	{
    	return $this->dbalSchema;
	}


	//convertToSchema
	public function process(array $ormMeta, $entityDefs)
	{
    	$GLOBALS['log']->add('Debug', 'Converters\Schema - Start: building schema');

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

		            /*case 'array':
		            case 'json_array':
		                $fieldParams['default'] = ''; //for db type TEXT can't be defined a default value
		                break;

					case 'bool':
                        $fieldParams['default'] = intval($fieldParams['default']);
                    	break;*/

					case 'int':
                        if (isset($fieldParams['autoincrement']) && $fieldParams['autoincrement']) {
                        	$uniqueColumns[] = Util::toUnderScore($fieldName);
                        }
						break;
		        }

                $fieldType = isset($fieldParams['dbType']) ? $fieldParams['dbType'] : $fieldParams['type'];
				if (!in_array($fieldType, $this->typeList)) {
                	$GLOBALS['log']->add('DEBUG', 'Converters\Schema::process(): Field type ['.$fieldType.'] does not exist '.$entityName.':'.$fieldName);
					continue;
				}

				$columnName = Util::toUnderScore($fieldName);
				if (!$tables[$entityName]->hasColumn($columnName)) {
                	$tables[$entityName]->addColumn($columnName, $fieldType, $this->getDbFieldParams($fieldParams));
				}


				//add index. It can be defined in entityDefs as "index"
				if (isset($fieldParams['index'])) {
 					if ($fieldParams['index'] === true) {
                    	$indexList[] = array($columnName);
					} else if (is_string($fieldParams['index'])) {
                        $indexList[ $fieldParams['index'] ][] = $columnName;
					}
				}
				//END: add index
			}

            $tables[$entityName]->setPrimaryKey($primaryColumns);
			if (!empty($indexList)) {
				foreach($indexList as $indexItem) {
                	$tables[$entityName]->addIndex($indexItem);
				}
			}
			if (!empty($uniqueColumns)) {
            	$tables[$entityName]->addUniqueIndex($uniqueColumns);
			}
		}

		//check and create columns/tables for relations
        foreach ($ormMeta as $entityName => $entityParams) {

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


		$GLOBALS['log']->add('Debug', 'Converters\Schema - End: building schema');

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
		if (isset($relationParams['conditions'])) {
			foreach($relationParams['conditions'] as $fieldName => $condition) {
            	$table->addColumn(Util::toUnderScore($fieldName), 'varchar', array('length'=>$this->defaultLength['varchar']));
			}
		}

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
		}
		//END: add additionalColumns


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

		return $dbFieldParams;
	}
}