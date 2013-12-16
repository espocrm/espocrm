<?php

namespace Espo\Core\Utils\Database\Converters;

use Espo\Core\Utils\Util;


class Schema
{
	private $dbalSchema;

	protected $typeList;

	//pair espo::doctrine
	protected $allowedDbFieldParams = array(
		'len' => 'length',
		'default' => 'default',
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
		        }

                $fieldType = isset($fieldParams['dbType']) ? $fieldParams['dbType'] : $fieldParams['type'];
				if (!in_array($fieldType, $this->typeList)) {
                	$GLOBALS['log']->add('DEBUG', 'Field type ['.$fieldType.'] does not exist '.$entityName.':'.$fieldName);
					continue;
				}

				$tables[$entityName]->addColumn(Util::toUnderScore($fieldName), $fieldType, $this->getDbFieldParams($fieldParams));
			}

            $tables[$entityName]->setPrimaryKey($primaryColumns);
		}

       /* foreach ($databaseMeta as $entityName => $entityParams) {

        	foreach ($databaseMeta['relations'] as $relationName => $relationParams) {
            	$myForeign->addForeignKeyConstraint($myTable, array("user_id"), array("id"), array("onUpdate" => "CASCADE"));
			}
        }*/

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