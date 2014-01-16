<?php

namespace Espo\Core\Utils\Database\Orm\Relations;

class EntityTeam extends \Espo\Core\Utils\Database\Orm\Base
{

	public function load($params, $foreignParams)
	{
		return array(
			$params['entityName'] => array(
				'relations' => array(
					$params['link']['name'] => array(
						'type' => 'manyMany',
						'entity' => $params['targetEntity'],
						'relationName' => lcfirst($params['link']['params']['relationName']),
						'midKeys' => array(
							'entity_id',
							'team_id',
						),
						'conditions' => array(
							'entityType' => $params['entityName'],
						),
						'additionalColumns' => array(
							'entityType' => array(
								'type' => 'varchar',
        						'len' => 100,
							),
						),
					),
				),
			),
		);
	}

}