<?php

namespace Espo\Core\Utils\Database\Helpers;

class EntityEmailAddress
{

	public function getRelation($params, $foreignParams)
	{
		return array(
			$params['entityName'] => array(
				'relations' => array(
					$params['link']['name'] => array(
						'type' => 'manyMany',
						'entity' => 'EmailAddress',
						'relationName' => 'entityEmailAddress',
						'midKeys' => array(
							'entity_id',
							'email_address_id',
						),
						'conditions' => array(
							'entityType' => $params['entityName'],
						),
						'additionalColumns' => array(
							'primary' => array(
								'type' => 'bool',
        						'default' => false,
							),
						),
					),
				),
			),
		);
	}

}