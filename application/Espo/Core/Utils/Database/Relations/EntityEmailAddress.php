<?php

namespace Espo\Core\Utils\Database\Relations;

class EntityEmailAddress
{

	public function load($params, $foreignParams)
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
                            'entityType' => array(
								'type' => 'varchar',
        						'len' => 100,
							),
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