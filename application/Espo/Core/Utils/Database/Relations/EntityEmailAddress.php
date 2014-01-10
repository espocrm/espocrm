<?php

namespace Espo\Core\Utils\Database\Relations;

use \Espo\Core\Utils\Util;

class EntityEmailAddress
{

	public function load($params, $foreignParams)
	{
		return array(
			$params['entityName'] => array(
			   	'fields' => array(
                	$params['link']['name'] => array(
						'select' => 'email_address.name',
				        'where' =>
				        array (
				          'LIKE' => 'email_address.name LIKE \'{text}\'',
				          '=' => 'email_address.name = \'{text}\'',
				        ),
				        'orderBy' => 'email_address.name {direction}',
					),
				),
				'relations' => array(
					$params['link']['name'].'es' => array(
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