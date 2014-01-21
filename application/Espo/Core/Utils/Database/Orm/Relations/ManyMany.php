<?php

namespace Espo\Core\Utils\Database\Orm\Relations;

use Espo\Core\Utils\Util;

class ManyMany extends \Espo\Core\Utils\Database\Orm\Base
{

	protected $allowParams = array(
		'relationName',
		'conditions',
		'additionalColumns',
	);

	public function load($params, $foreignParams)
	{
		return array(
			$params['entityName'] => array(
				'fields' => array(
	               	$params['link']['name'].'Ids' => array(
						'type' => 'varchar',
						'notStorable' => true,
					),
					$params['link']['name'].'Names' => array(
						'type' => 'varchar',
						'notStorable' => true,
					),
				),
				'relations' => array(
					$params['link']['name'] => array(
						'type' => 'manyMany',
						'entity' => $params['targetEntity'],
						'relationName' => $this->getJoinTable($params['entityName'], $foreignParams['entityName']),
						'key' => 'id', //todo specify 'key'
						'foreignKey' => 'id', //todo specify 'foreignKey'
						'midKeys' => array(
							lcfirst($params['entityName']).'Id',
							lcfirst($foreignParams['entityName']).'Id',
						),
					),
				),
			),
		);
	}

	protected function getJoinTable($tableName1, $tableName2)
	{
		$tables = $this->getSortEntities($tableName1, $tableName2);

		return Util::toCamelCase( implode('-', $tables) );
	}

    protected function getSortEntities($entity1, $entity2)
	{
		$entities = array(
        	Util::toCamelCase(lcfirst($entity1)),
        	Util::toCamelCase(lcfirst($entity2)),
		);

		sort($entities);

		return $entities;
	}

}