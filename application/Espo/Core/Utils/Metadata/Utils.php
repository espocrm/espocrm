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

namespace Espo\Core\Utils\Metadata;

class Utils
{
	private $metadata;

	public function __construct(\Espo\Core\Utils\Metadata $metadata)
	{
		$this->metadata = $metadata;
	}

	protected function getMetadata()
	{
		return $this->metadata;
	}

	/**
	 * Get field defenition by type in metadata, "fields" key
	 *
	 * @param  array | string $fieldDef - It can be a string or field defenition from entityDefs
	 * @return array | null
	 */
	public function getFieldDefsByType($fieldDef)
	{
		if (is_string($fieldDef)) {
			$fieldDef = array('type' => $fieldDef);
		}

		if (isset($fieldDef['dbType'])) {
			return $this->getMetadata()->get('fields.'.$fieldDef['dbType']);
		} else if (isset($fieldDef['type'])) {
			return $this->getMetadata()->get('fields.'.$fieldDef['type']);
		}

		return null;
	}

	public function getFieldDefsInFieldMeta($fieldDef)
	{
		$fieldDefsByType = $this->getFieldDefsByType($fieldDef);
		if (isset($fieldDefsByType['fieldDefs'])) {
			return $fieldDefsByType['fieldDefs'];
		}

		return null;
	}

	/**
	 * Get link definition defined in 'fields' metadata. In linkDefs can be used as value (e.g. "type": "hasChildren") and/or variables (e.g. "entityName":"{entity}"). Variables should be defined into fieldDefs (in 'entityDefs' metadata).
	 *
	 * @param  string $entityName
	 * @param  array  $fieldDef
	 * @param  array  $linkFieldDefsByType
	 * @return array | null
	 */
	public function getLinkDefsInFieldMeta($entityName, $fieldDef, array $linkFieldDefsByType = null)
	{
		if (!isset($fieldDefsByType)) {
			$fieldDefsByType = $this->getFieldDefsByType($fieldDef);
			if (!isset($fieldDefsByType['linkDefs'])) {
				return null;
			}
			$linkFieldDefsByType = $fieldDefsByType['linkDefs'];
		}

		foreach ($linkFieldDefsByType as $paramName => &$paramValue) {
			if (preg_match('/{(.*?)}/', $paramValue, $matches)) {
				if (in_array($matches[1], array_keys($fieldDef))) {
					$value = $fieldDef[$matches[1]];
				} else if (strtolower($matches[1]) == 'entity') {
					$value = $entityName;
				}

				if (isset($value)) {
					$paramValue = str_replace('{'.$matches[1].'}', $value, $paramValue);
				}
			}
		}

		return $linkFieldDefsByType;
	}

}


?>
