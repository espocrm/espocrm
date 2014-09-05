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

use Espo\Core\Utils\Util;

class PersonName extends \Espo\Core\Utils\Database\Orm\Base
{
	protected function load($fieldName, $entityName)
	{
		$foreignField = array('first' . ucfirst($fieldName), ' ', 'last' . ucfirst($fieldName));
		
		$tableName = Util::toUnderScore($entityName);

		$fullList = array(); //contains empty string (" ") like delimiter
		$fullListReverse = array(); //reverse of $fullList
		$fieldList = array(); //doesn't contain empty string (" ") like delimiter
		$like = array();
		$equal = array();

		foreach($foreignField as $foreignFieldName) {

			$fieldNameTrimmed = trim($foreignFieldName);
			if (!empty($fieldNameTrimmed)) {
				$columnName = $tableName.'.'.Util::toUnderScore($fieldNameTrimmed);

				$fullList[] = $fieldList[] = $columnName;
				$like[] = $columnName." LIKE {value}";
				$equal[] = $columnName." = {value}";
			} else {
				$fullList[] = "'".$foreignFieldName."'";
			}
		}

		$fullListReverse = array_reverse($fullList);

		return array(
			$entityName => array (
				'fields' => array(
					$fieldName => array(
						'type' => 'varchar',
						'select' => $this->getSelect($fullList),
						'where' => array(
							'LIKE' => "(".implode(" OR ", $like)." OR CONCAT(".implode(", ", $fullList).") LIKE {value} OR CONCAT(".implode(", ", $fullListReverse).") LIKE {value})",
							'=' => "(".implode(" OR ", $equal)." OR CONCAT(".implode(", ", $fullList).") = {value} OR CONCAT(".implode(", ", $fullListReverse).") = {value})",
						),
						'orderBy' => implode(", ", array_map(function ($item) {return $item . ' {direction}';}, $fieldList)),
					),
				),
			),
		);
	}

	protected function getSelect(array $fullList)
	{
		foreach ($fullList as &$item) {

			$rowItem = trim($item, " '");

			if (!empty($rowItem)) {
				$item = "IFNULL(".$item.", '')";
			}
		}

		$select = "TRIM(CONCAT(".implode(", ", $fullList)."))";

		return $select;
	}

}
