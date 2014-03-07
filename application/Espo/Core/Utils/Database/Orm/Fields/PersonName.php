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

	public function load($entity, $field)
	{
        $foreignField = $this->getForeignField($field['name'], $entity['name']);
		$tableName = Util::toUnderScore($entity['name']);

		$fullList = array(); //contains empty string (" ") like delimiter
		$fieldList = array(); //doesn't contain empty string (" ") like delimiter
		$like = array();
		foreach($foreignField as $fieldName) {

            $fieldNameTrimmed = trim($fieldName);
			if (!empty($fieldNameTrimmed)) {
				$columnName = $tableName.'.'.Util::toUnderScore($fieldNameTrimmed);

            	$fullList[] = $fieldList[] = $columnName;
				$like[] = $columnName." LIKE '{text}'";
			} else {
            	$fullList[] = "'".$fieldName."'";
			}
		}

       	return array(
			$entity['name'] => array (
	           	'fields' => array(
	               	$field['name'] => array(
                        'type' => 'varchar',
						'select' => "TRIM(CONCAT(".implode(", ", $fullList)."))",
					    'where' => array(
					    	'LIKE' => "(".implode(" OR ", $like)." OR CONCAT(".implode(", ", $fullList).") LIKE '{text}')",
					    ),
					    'orderBy' => implode(", ", array_map(function ($item) {return $item . ' {direction}';}, $fieldList)),
					),
				),
			),
		);
	}

}