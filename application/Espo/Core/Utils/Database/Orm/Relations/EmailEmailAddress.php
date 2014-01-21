<?php

namespace Espo\Core\Utils\Database\Orm\Relations;

class EmailEmailAddress extends \Espo\Core\Utils\Database\Orm\Relations\HasMany
{

	public function load($params, $foreignParams)
	{

		$parentRelation = parent::load($params, $foreignParams);

		$relation = array(
			$params['entityName'] => array (				
				'relations' => array(
                	$params['link']['name'] => array(						
						'midKeys' => array(
							lcfirst($params['entityName']).'Id',
							lcfirst($foreignParams['entityName']).'Id',
						),
					),
				),
			),
		);

		
		$relation = \Espo\Core\Utils\Util::merge($parentRelation, $relation);

		return $relation;
	}

}