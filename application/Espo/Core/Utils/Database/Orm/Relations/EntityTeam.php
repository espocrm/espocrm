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

class EntityTeam extends Base
{
	protected function load($linkName, $entityName)
	{
		$linkParams = $this->getLinkParams();
		$foreignEntityName = $this->getForeignEntityName();

		return array(
			$entityName => array(
				'relations' => array(
					$linkName => array(
						'type' => 'manyMany',
						'entity' => $foreignEntityName,
						'relationName' => lcfirst($linkParams['relationName']),
						'midKeys' => array(
							'entity_id',
							'team_id',
						),
						'conditions' => array(
							'entityType' => $entityName,
						),
						'additionalColumns' => array(
							'entityType' => array(
								'type' => 'varchar',
        						'len' => 100,
							),
						),
					),
				),
			),
		);
	}

}