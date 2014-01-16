<?php

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