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

namespace Espo\ORM\DB;

use Espo\ORM\Entity;
use Espo\ORM\Classes\EntityCollection;
use PDO;

/**
 * Abstraction for MySQL DB.
 * Mapping of Entity to DB.
 * Should be used internally only.
 */
class MysqlMapper extends Mapper
{		
	protected function composeSelectQuery($table, $select, $joins = '', $where = '', $order = '', $offset = null, $limit = null, $distinct = null, $aggregation = false)
	{	
		$sql = "SELECT";
		
		/*if (!empty($distinct)) {
			$sql .= " DISTINCT";
		}*/

		$sql .= " {$select} FROM `{$table}`";
		
		if (!empty($joins)) {
			$sql .= " {$joins}";
		}
		
		if (!empty($where)) {
			$sql .= " WHERE {$where}";
		}
		
		if (!empty($distinct)) {
			$sql .= " GROUP BY `{$table}`.id";
		}
		
		if (!empty($order)) {
			$sql .= " {$order}";
		}
		
		if (is_null($offset) && !is_null($limit)) {
			$offset = 0;
		}
		
		if (!is_null($offset) && !is_null($limit)) {
			$offset = intval($offset);
			$limit = intval($limit);
			$sql .= " LIMIT {$offset}, {$limit}";
		}
		
		return $sql;
	}	
	
	protected function toDb($field)
	{	
		if (array_key_exists($field, $this->fieldsMapCache)) {
			return $this->fieldsMapCache[$field];
			
		} else {
			$dbField = strtolower(preg_replace('/(?<=[a-z])([A-Z])/', '_$1', $field));
			$this->fieldsMapCache[$field] = $dbField;
			return $dbField;
		}
	}
}
?>
