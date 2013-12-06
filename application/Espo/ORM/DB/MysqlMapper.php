<?php

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
	protected function composeSelectQuery($table, $select, $joins = '', $where = '', $order = '', $offset = null, $limit = null, $distinct = null)
	{	
		$sql = "SELECT";
		
		if (!empty($distinct)) {
			$sql .= " DISTINCT";
		}

		$sql .= " {$select} FROM {$table}";
		
		if (!empty($joins)) {
			$sql .= " {$joins}";
		}
		
		if (!empty($where)) {
			$sql .= " WHERE {$where}";
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
