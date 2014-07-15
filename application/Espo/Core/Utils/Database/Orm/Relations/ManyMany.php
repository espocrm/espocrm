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

use Espo\Core\Utils\Util;

class ManyMany extends Base
{
	protected $allowParams = array(
		'relationName',
		'conditions',
		'additionalColumns',
	);

	protected function load($linkName, $entityName)
	{
		$foreignEntityName = $this->getForeignEntityName();

		return array(
			$entityName => array(
				'fields' => array(
	               	$linkName.'Ids' => array(
						'type' => 'varchar',
						'notStorable' => true,
					),
					$linkName.'Names' => array(
						'type' => 'varchar',
						'notStorable' => true,
					),
				),
				'relations' => array(
					$linkName => array(
						'type' => 'manyMany',
						'entity' => $foreignEntityName,
						'relationName' => $this->getJoinTable($entityName, $foreignEntityName),
						'key' => 'id', //todo specify 'key'
						'foreignKey' => 'id', //todo specify 'foreignKey'
						'midKeys' => array(
							lcfirst($entityName).'Id',
							lcfirst($foreignEntityName).'Id',
						),
					),
				),
			),
		);
	}

	protected function getJoinTable($tableName1, $tableName2)
	{
		$tables = $this->getSortEntities($tableName1, $tableName2);

		return Util::toCamelCase( implode('-', $tables) );
	}

    protected function getSortEntities($entity1, $entity2)
	{
		$entities = array(
        	Util::toCamelCase(lcfirst($entity1)),
        	Util::toCamelCase(lcfirst($entity2)),
		);

		sort($entities);

		return $entities;
	}

}