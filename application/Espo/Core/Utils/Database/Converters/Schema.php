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
	);


	//todo: same array in Converters\Orm
	protected $idParams = array(
		'dbType' => 'varchar',
		'len' => '24',
	);

	public function __construct()
	{
		$this->dbalSchema = new \Doctrine\DBAL\Schema\Schema();

		$this->typeList = array_keys(\Doctrine\DBAL\Types\Type::getTypesMap());
	}

	protected function getDbalSchema()
	{
    	return $this->dbalSchema;
	}



	//convertToSchema
	public function process(array $databaseMeta, $entityDefs)
	{
		$schema = $this->getDbalSchema();

		$tables = array();
		foreach ($databaseMeta as $entityName => $entityParams) {

            $tables[$entityName] = $schema->createTable( Util::toUnderScore($entityName) );

			$primaryColumns = array();
			$uniqueColumns = array();
        	foreach ($entityParams['fields'] as $fieldName => $fieldParams) {

				if (isset($fieldParams['notStorable']) && $fieldParams['notStorable']) {
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
                	$GLOBALS['log']->add('DEBUG', 'Field type ['.$fieldType.'] does not exist '.$entityName.':'.$fieldName);
					continue;
				}

				$tables[$entityName]->addColumn(Util::toUnderScore($fieldName), $fieldType, $this->getDbFieldParams($fieldParams));
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
                        $tables[$tableName] = $schema->createTable( Util::toUnderScore($tableName) );
                        $tables[$tableName]->addColumn('id', $this->idParams['dbType'], array('length'=>$this->idParams['len']));

                        $relationEntities = array($entityName, $relationParams['entity']);
                        $relationKeys = array($relationParams['key'], $relationParams['foreignKey']);
						foreach($relationParams['midKeys'] as $index => $midKey) {
							$usMidKey = Util::toUnderScore($midKey);
                        	$tables[$tableName]->addColumn($usMidKey, $this->idParams['dbType'], array('length'=>$this->idParams['len']));

							$relationKey = Util::toUnderScore($relationKeys[$index]);
                            $tables[$tableName]->addForeignKeyConstraint($tables[$relationEntities[$index]], array($usMidKey), array($relationKey), array("onUpdate" => "CASCADE"));
						}

                        $tables[$tableName]->addColumn('deleted', 'bool', array('default' => 0));
						$tables[$tableName]->setPrimaryKey(array("id"));
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

		return $schema;
	}


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