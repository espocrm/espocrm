<?php

namespace Espo\Core\Utils\Database\DBAL\Schema;

use Doctrine\DBAL\Schema\Column;


class Comparator extends \Doctrine\DBAL\Schema\Comparator
{

	public function diffColumn(Column $column1, Column $column2)
    {
        $changedProperties = array();
        if ( $column1->getType() != $column2->getType() ) {

			//espo: fix problem with executing query for custom types
			$column1DbTypeName = method_exists($column1->getType(), 'getDbTypeName') ? $column1->getType()->getDbTypeName() : $column1->getType()->getName();
			$column2DbTypeName = method_exists($column2->getType(), 'getDbTypeName') ? $column2->getType()->getDbTypeName() : $column2->getType()->getName();

			if (strtolower($column1DbTypeName) != strtolower($column2DbTypeName)) {
            	$changedProperties[] = 'type';
			}            
			//END: espo
        }

        if ($column1->getNotnull() != $column2->getNotnull()) {
            $changedProperties[] = 'notnull';
        }

        if ($column1->getDefault() != $column2->getDefault()) {
            $changedProperties[] = 'default';
        }

        if ($column1->getUnsigned() != $column2->getUnsigned()) {
            $changedProperties[] = 'unsigned';
        }

        if ($column1->getType() instanceof \Doctrine\DBAL\Types\StringType) {
            // check if value of length is set at all, default value assumed otherwise.
            $length1 = $column1->getLength() ?: 255;
            $length2 = $column2->getLength() ?: 255;
            if ($length1 != $length2) {
                $changedProperties[] = 'length';
            }

            if ($column1->getFixed() != $column2->getFixed()) {
                $changedProperties[] = 'fixed';
            }
        }

        if ($column1->getType() instanceof \Doctrine\DBAL\Types\DecimalType) {
            if (($column1->getPrecision()?:10) != ($column2->getPrecision()?:10)) {
                $changedProperties[] = 'precision';
            }
            if ($column1->getScale() != $column2->getScale()) {
                $changedProperties[] = 'scale';
            }
        }

        if ($column1->getAutoincrement() != $column2->getAutoincrement()) {
            $changedProperties[] = 'autoincrement';
        }

        // only allow to delete comment if its set to '' not to null.
        if ($column1->getComment() !== null && $column1->getComment() != $column2->getComment()) {
            $changedProperties[] = 'comment';
        }

        $options1 = $column1->getCustomSchemaOptions();
        $options2 = $column2->getCustomSchemaOptions();

        $commonKeys = array_keys(array_intersect_key($options1, $options2));

        foreach ($commonKeys as $key) {
            if ($options1[$key] !== $options2[$key]) {
                $changedProperties[] = $key;
            }
        }

        $diffKeys = array_keys(array_diff_key($options1, $options2) + array_diff_key($options2, $options1));

        $changedProperties = array_merge($changedProperties, $diffKeys);

        return $changedProperties;
    }

}