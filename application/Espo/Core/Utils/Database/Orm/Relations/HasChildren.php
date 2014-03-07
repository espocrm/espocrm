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

namespace Espo\Core\Utils\Database\Orm\Relations;

class HasChildren extends \Espo\Core\Utils\Database\Orm\Base
{

	public function load($params, $foreignParams)
	{
		return array(
			$params['entityName'] => array (
				'fields' => array(
	               	$params['link']['name'].'Ids' => array(
						'type' => 'varchar',
						'notStorable' => true,
					),
					$params['link']['name'].'Names' => array(
						'type' => 'varchar',
						'notStorable' => true,
					),
				),
				'relations' => array(
                	$params['link']['name'] => array(
						'type' => 'hasChildren',
						'entity' => $params['targetEntity'],
						'foreignKey' => $foreignParams['link']['name'].'Id', //???: 'foreignKey' => $params['link']['name'].'Id',
						'foreignType' => $foreignParams['link']['name'].'Type', //???: 'foreignKey' => $params['link']['name'].'Id',
					),
				),
			),
		);
	}


}