<?php

namespace Espo\Core\Utils\Database\Orm\Relations;

class HasMany extends \Espo\Core\Utils\Database\Orm\Base
{
	protected $allowParams = array(
		'relationName',
		'conditions',
		'additionalColumns',
	);

	public function load($params, $foreignParams)
	{
		$relationType = isset($params['link']['params']['relationName']) ? 'manyMany' : 'hasMany';

		$relation = array(
			$params['entityName'] => array (
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
						//'type' => 'hasMany',
						'type' => $relationType,
						'entity' => $params['targetEntity'],
						'foreignKey' => lcfirst($foreignParams['link']['name'].'Id'), //???: 'foreignKey' => $params['link']['name'].'Id',						
					),
				),
			),
		);	

        return $relation;
	}


}