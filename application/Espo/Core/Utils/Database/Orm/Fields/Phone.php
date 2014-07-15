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

class Phone extends \Espo\Core\Utils\Database\Orm\Base
{
	protected function load($fieldName, $entityName)
	{
		return array(
			$entityName => array(
				'fields' => array(
					$fieldName => array(
						'select' => 'phone_number.name',
						'where' =>
						array (
						  'LIKE' => 'phone_number.name LIKE \'{text}\'',
						  '=' => 'phone_number.name = \'{text}\'',
						),
						'orderBy' => 'phone_number.name {direction}',
					),
					$fieldName .'Data' => array(
						'type' => 'text',
						'notStorable' => true
					),
				),
				'relations' => array(
					$fieldName.'s' => array(
						'type' => 'manyMany',
						'entity' => 'PhoneNumber',
						'relationName' => 'entityPhoneNumber',
						'midKeys' => array(
							'entity_id',
							'phone_number_id',
						),
						'conditions' => array(
							'entityType' => $entityName,
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

