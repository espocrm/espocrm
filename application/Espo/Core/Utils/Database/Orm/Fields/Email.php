<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014  Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 ************************************************************************/ 

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
					$field['name'] .'Data' => array(
						'type' => 'text',
						'notStorable' => true
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
