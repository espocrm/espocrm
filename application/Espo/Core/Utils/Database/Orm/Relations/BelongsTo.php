<?php

namespace Espo\Core\Utils\Database\Orm\Relations;

class BelongsTo extends \Espo\Core\Utils\Database\Orm\Base
{

	public function load($params, $foreignParams)
	{
		return array (
			$params['entityName'] => array (
				'fields' => array(
					$params['link']['name'].'Name' => array(
						'type' => 'foreign',
						'relation' => $params['link']['name'],
						'foreign' => $this->getForeignField('name', $foreignParams['entityName']),
					),
					$params['link']['name'].'Id' => array(
						'type' => 'foreignId',
						'index' => true,
					),
				),
				'relations' => array(
                	$params['link']['name'] => array(
						'type' => 'belongsTo',
						'entity' => $params['targetEntity'],
						'key' => $params['link']['name'].'Id',
						'foreignKey' => 'id', //????
					),
				),
			),
		);
	}


}