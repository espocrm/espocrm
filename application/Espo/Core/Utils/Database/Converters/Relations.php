<?php

namespace Espo\Core\Utils\Database\Converters;

use Espo\Core\Utils\Util,
	Espo\ORM\Entity;


class Relations
{
	protected function getSortEntities($entity1, $entity2)
	{
		$entities = array(
        	Util::toCamelCase(lcfirst($entity1)),
        	Util::toCamelCase(lcfirst($entity2)),
		);

		sort($entities);

		return $entities;
	}

	protected function getJoinTable($tableName1, $tableName2)
	{
		$tables = $this->getSortEntities($tableName1, $tableName2);

		return Util::toCamelCase( implode('-', $tables) );
	}




	//todo sedine in foreign fieldDefs a key for current
	public function manyMany($params, $foreignParams)
	{
        $sortedEntities = $this->getSortEntities($params['entityName'], $foreignParams['entityName']);

		$relation = array();

		//check for duplication if defined a "foreign" key for both sides
		$process = true;
        if (isset($params['link']['params']['foreign']) && isset($foreignParams['link']['params']['foreign'])) {
        	$process = false;
        	if (strtolower($params['entityName']) == strtolower($sortedEntities[0])) {
            	$process = true;
        	}
        }

		if ($process) {
			$relation = array(
				$params['entityName'] => array(
					'relations' => array(
						$params['link']['name'] => array(
							'type' => Entity::MANY_MANY,
							'entity' => $params['targetEntity'],
							'relationName' => $this->getJoinTable($params['entityName'], $foreignParams['entityName']),
							'key' => 'id', //todo specify 'key'
							'foreignKey' => 'id', //todo specify 'foreignKey'
							'midKeys' => array(
								$sortedEntities[0].'Id',
								$sortedEntities[1].'Id',
							),
						),
					),
				),
			);
		}

    	return $relation;
	}


	public function hasMany($params, $foreignParams)
	{
		$relation = array(
			$params['entityName'] => array (
				'relations' => array(
                	$params['link']['name'] => array(
						'type' => Entity::HAS_MANY,
						'entity' => $params['targetEntity'],
						'foreignKey' => lcfirst($foreignParams['link']['name'].'Id'), //???: 'foreignKey' => $params['link']['name'].'Id',
					),
				),
			),
		);

        return $relation;
	}

	public function belongsTo($params, $foreignParams)
	{
		 $relation = array (
			$params['entityName'] => array (
				'fields' => array(
					$params['link']['name'].'Name' => array(
						'type' => Entity::FOREIGN,
						'relation' => $params['link']['name'],
						'notStorable' => true,
					),
					$params['link']['name'].'Id' => array(
						'type' => Entity::FOREIGN_ID,
					),
				),
				'relations' => array(
                	$params['link']['name'] => array(
						'type' => Entity::BELONGS_TO,
						'entity' => $params['targetEntity'],
						'key' => $params['link']['name'].'Id',
						'foreignKey' => 'id', //????
					),
				),
			),
		);

		if (isset($params['link']['params']['foreign'])) {  //???
        	$relation[$params['entityName']] ['fields'] [$params['link']['name'].'Name'] ['foreign'] = $params['link']['params']['foreign'];
		}

		return $relation;
	}

	public function hasChildren($params, $foreignParams)
	{
		$relation = array(
			$params['entityName'] => array (
				'relations' => array(
                	$params['link']['name'] => array(
						'type' => Entity::HAS_CHILDREN,
						'entity' => $params['targetEntity'],
						'foreignKey' => $foreignParams['link']['name'].'Id', //???: 'foreignKey' => $params['link']['name'].'Id',
						'foreignType' => $foreignParams['link']['name'].'Type', //???: 'foreignKey' => $params['link']['name'].'Id',
					),
				),
			),

            /*$foreignParams['entityName'] => array (
            	'fields' => array(
                	$foreignParams['link']['name'].'Id' => array(
						'type' => Entity::FOREIGN_ID,
					),
					$foreignParams['link']['name'].'Type' => array(
						'type' => Entity::FOREIGN_TYPE,
					),
					$foreignParams['link']['name'].'Name' => array(
						'type' => Entity::VARCHAR,
						'notStorable' => true,
					),
				),
			), */
		);


		return $relation;
	}

	public function belongsToParent($params, $foreignParams)
	{
        $relation = array();

		$entities = isset($params['link']['params']['entities']) ? $params['link']['params']['entities'] : array($params['entityName']);

		foreach($entities as $entity) {
        	$relation[$entity] = array (
            	'fields' => array(
                	$params['link']['name'].'Id' => array(
						'type' => Entity::FOREIGN_ID,
					),
					$params['link']['name'].'Type' => array(
						'type' => Entity::FOREIGN_TYPE,
					),
					$params['link']['name'].'Name' => array(
						'type' => Entity::VARCHAR,
						'notStorable' => true,
					),
				),
			);
		}

		return $relation;
	}


	public function hasOne($params, $foreignParams)
	{

	}


}