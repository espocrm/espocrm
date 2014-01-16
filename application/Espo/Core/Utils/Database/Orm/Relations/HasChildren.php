<?php

namespace Espo\Core\Utils\Database\Orm\Relations;

class HasChildren extends \Espo\Core\Utils\Database\Orm\Base
{

	public function load($params, $foreignParams)
	{
		return array(
			$params['entityName'] => array (
				'relations' => array(
                	$params['link']['name'] => array(
						'type' => 'hasChildren',
						'entity' => $params['targetEntity'],
						'foreignKey' => $foreignParams['link']['name'].'Id', //???: 'foreignKey' => $params['link']['name'].'Id',
						'foreignType' => $foreignParams['link']['name'].'Type', //???: 'foreignKey' => $params['link']['name'].'Id',
					),
				),
			),
		);
	}


}