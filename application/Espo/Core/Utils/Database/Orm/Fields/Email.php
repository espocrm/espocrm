<?php

namespace Espo\Core\Utils\Database\Orm\Fields;

class Email extends \Espo\Core\Utils\Database\Orm\Base
{

	public function load($entity, $field)
	{
        return array(
			$entity['name'] => array(
			   	'fields' => array(
                	$field['name'] => array(
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
					$field['name'].'es' => array(
						'type' => 'manyMany',
						'entity' => 'EmailAddress',
						'relationName' => 'entityEmailAddress',
						'midKeys' => array(
							'entity_id',
							'email_address_id',
						),
						'conditions' => array(
							'entityType' => $entity['name'],
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