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
	public function process(array $databaseMeta, $entityDefs)
	{
    	$GLOBALS['log']->add('Debug', 'Converters\Schema - Start: building schema');

		//pre actions
        $this->preProcess($databaseMeta, $entityDefs);
		//END: pre actions

		$schema = $this->getSchema();

		$tables = array();
		foreach ($databaseMeta as $entityName => $entityParams) {

            $tables[$entityName] = $schema->createTable( Util::toUnderScore($entityName) );

			$primaryColumns = array();
			$uniqueColumns = array();
        	foreach ($entityParams['fields'] as $fieldName => $fieldParams) {

				if ((isset($fieldParams['notStorable']) && $fieldParams['notStorable']) || in_array($fieldParams['type'], $this->notStorableTypes)) {
					continue;
				}

				switch ($fieldParams['type']) {
		            case 'id':
                        $primaryColumns[] = Util::toUnderScore($fieldName);

		            case 'array':
		            case 'json_array':
		                $fieldParams['default'] = ''; //for db type TEXT can't be defined a default value
		                break;

					case 'bool':
                        $fieldParams['default'] = intval($fieldParams['default']);
                    	break;

					case 'int':
                        if (isset($fieldParams['autoincrement']) && $fieldParams['autoincrement']) {
                        	$uniqueColumns[] = Util::toUnderScore($fieldName);
                        }
						break;
		        }

                $fieldType = isset($fieldParams['dbType']) ? $fieldParams['dbType'] : $fieldParams['type'];
				if (!in_array($fieldType, $this->typeList)) {
                	$GLOBALS['log']->add('DEBUG', 'Converters/Schema::process(): Field type ['.$fieldType.'] does not exist '.$entityName.':'.$fieldName);
					continue;
				}

				$columnName = Util::toUnderScore($fieldName);
				if (!$tables[$entityName]->hasColumn($columnName)) {
                	$tables[$entityName]->addColumn($columnName, $fieldType, $this->getDbFieldParams($fieldParams));
				}
			}

            $tables[$entityName]->setPrimaryKey($primaryColumns);
			if (!empty($uniqueColumns)) {
            	$tables[$entityName]->addUniqueIndex($uniqueColumns);
			}
		}

		//check and create columns/tables for relations
        foreach ($databaseMeta as $entityName => $entityParams) {

        	foreach ($entityParams['relations'] as $relationName => $relationParams) {

                 switch ($relationParams['type']) {
		            case 'manyMany':
						$tableName = $relationParams['relationName'];

                        //check for duplication tables
						if (!isset($tables[$tableName])) { //no needs to create the table if it already exists

							if (strtolower($tableName) == strtolower('EntityTeam')) {  //hardcode for Teams
								if (isset($relationParams['conditions'])) {
                                	$relationParams['midKeys'] = array_merge($relationParams['midKeys'], array_keys($relationParams['conditions']));
								}
								$tables[$tableName] = $this->prepareManyMany($entityName, $relationParams, $tables, false);
								//$table->addForeignKeyConstraint($tables['Team'], array($usMidKey), array($relationKey), array("onUpdate" => "CASCADE"));
							} else {
                            	$tables[$tableName] = $this->prepareManyMany($entityName, $relationParams, $tables);
							}

						}
						break;

		            case 'belongsTo':
						$foreignEntity = $relationParams['entity'];
						$columnName = Util::toUnderScore($relationParams['key']);
						$foreignKey = Util::toUnderScore($relationParams['foreignKey']);
		                $tables[$entityName]->addForeignKeyConstraint($tables[$foreignEntity], array($columnName), array($foreignKey), array("onUpdate" => "CASCADE"));
		                break;
		        }
            	//$myForeign->addForeignKeyConstraint($myTable, array("user_id"), array("id"), array("onUpdate" => "CASCADE"));
			}
        }
		//END: check and create columns/tables for relations


		$GLOBALS['log']->add('Debug', 'Converters\Schema - End: building schema');

		return $schema;
	}


	protected function preProcess(array &$databaseMeta, &$entityDefs)
	{
		return;
		/*echo '<pre>';
		print_r($databaseMeta);
		exit;*/


		//hardcode for emails
        $this->createEmailAddressTables();
		//END: hardcode for emails
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
	protected function prepareManyMany($entityName, $relationParams, $tables, $isForeignKey = true)
	{
    	$tableName = $relationParams['relationName'];

		$table = $this->getSchema()->createTable( Util::toUnderScore($tableName) );
		$table->addColumn('id', 'int', array('length'=>$this->defaultLength['int'], 'autoincrement' => true));

		if ($isForeignKey) {
			$relationEntities = array($entityName, $relationParams['entity']);
			if (!isset($relationParams['key'])) {
				print_r($relationParams);
			}
			$relationKeys = array($relationParams['key'], $relationParams['foreignKey']);
		}

		foreach($relationParams['midKeys'] as $index => $midKey) {
			$usMidKey = Util::toUnderScore($midKey);

			if (preg_match('/_id$/i', $usMidKey)) {
            	$table->addColumn($usMidKey, $this->idParams['dbType'], array('length'=>$this->idParams['len']));
			} else {
				$table->addColumn($usMidKey, 'varchar', array('length'=>$this->defaultLength['varchar']));
			}

			if ($isForeignKey) {
            	$relationKey = Util::toUnderScore($relationKeys[$index]);
				$table->addForeignKeyConstraint($tables[$relationEntities[$index]], array($usMidKey), array($relationKey), array("onUpdate" => "CASCADE"));
			}
		}

		$table->addColumn('deleted', 'bool', array('default' => 0));
		$table->setPrimaryKey(array("id"));

        return $table;
	}

	/*protected function getTableDefs($tableName)
	{
		$tableName = 'EmailAddress';
		$table = $this->getSchema()->createTable( Util::toUnderScore($tableName) );
        $table->addColumn('id', $this->idParams['dbType'], array('length'=>$this->idParams['len']));

		switch ($tableName) {
            case 'EmailAddress':
            	$primaryColumns[] = Util::toUnderScore($fieldName);
                break;

            case 'EntityEmailAddress':
            	$primaryColumns[] = Util::toUnderScore($fieldName);
                break;
        }
	} */


	protected function getDbFieldParams($fieldParams)
	{
		$dbFieldParams = array();

		foreach($this->allowedDbFieldParams as $espoName => $dbalName) {

        	if (isset($fieldParams[$espoName])) {
            	$dbFieldParams[$dbalName] = $fieldParams[$espoName];
			}
		}

		return $dbFieldParams;
	}
}