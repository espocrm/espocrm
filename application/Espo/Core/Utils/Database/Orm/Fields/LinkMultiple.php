<?php

namespace Espo\Core\Utils\Database\Orm\Fields;

class LinkMultiple extends \Espo\Core\Utils\Database\Orm\Base
{

	public function load($entity, $field)
	{
        return array(
			$entity['name'] => array (
	           	'fields' => array(
	               	$field['name'].'Ids' => array(
						'type' => 'varchar',
						'notStorable' => true,
					),
					$field['name'].'Names' => array(
						'type' => 'varchar',
						'notStorable' => true,
					),
				),
			),
            'unset' => array(
                $entity['name'] => array(
                    'fields.'.$field['name'],
                ),
            ),
		);
	}


}