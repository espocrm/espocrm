<?php

namespace Espo\Core\Utils\Database\Orm\Fields;

class LinkParent extends \Espo\Core\Utils\Database\Orm\Base
{

	public function load($entity, $field)
	{
        return array(
            $entity['name'] => array (
            	'fields' => array(
                	$field['name'].'Id' => array(
						'type' => 'foreignId',
						'index' => $field['name'],
					),
					$field['name'].'Type' => array(
						'type' => 'foreignType',
						'index' => $field['name'],
					),
					$field['name'].'Name' => array(
						'type' => 'varchar',
						'notStorable' => true,
					),
				),
			)
        );
	}


}