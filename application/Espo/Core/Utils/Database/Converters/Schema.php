<?php

namespace Espo\Core\Utils\Database\Converters;

use Espo\Core\Utils\Util;


class Schema
{
	private $dbalSchema;

	protected $typeList;

	protected $idParams = array(
		'type' => 'varchar',
		'len' => '24',
	);

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

				if ($fieldParams['type'] == 'id') {
					$primaryColumns[] = Util::toUnderScore($fieldName);
				}

				switch ($fieldParams['type']) {
		            case 'id':
                        $primaryColumns[] = Util::toUnderScore($fieldName);

                    case 'id':
		            case 'foreignId':
		                $fieldParams = $this->idParams;
		                break;

		            case 'array':
		            case 'json_array':
		                $fieldParams['default'] = ''; //for db type TEXT can't be defined a default value
		                break;
		        }

				if (!in_array($fieldParams['type'], $this->typeList)) {
                	$GLOBALS['log']->add('DEBUG', 'Field type ['.$fieldParams['type'].'] does not exist '.$entityName.':'.$fieldName);
					continue;
				}

				//echo  Util::toUnderScore($fieldName).', '.$fieldParams['type'].', '.print_r($this->getDbFieldParams($fieldParams),1).'<br />';

				$tables[$entityName]->addColumn(Util::toUnderScore($fieldName), $fieldParams['type'], $this->getDbFieldParams($fieldParams));
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