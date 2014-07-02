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

namespace Espo\Core\Utils;

use \Espo\Core\Exceptions\Error,
	\Espo\Core\Exceptions\Conflict;

class FieldManager
{
	private $metadata;

	private $language;

	private $metadataUtils;

	protected $metadataType = 'entityDefs';

	protected $customOptionName = 'isCustom';


	public function __construct(Metadata $metadata, Language $language)
	{
		$this->metadata = $metadata;
		$this->language = $language;

		$this->metadataUtils = new \Espo\Core\Utils\Metadata\Utils($this->metadata);
	}

	protected function getMetadata()
	{
		return $this->metadata;
	}

	protected function getLanguage()
	{
		return $this->language;
	}

	protected function getMetadataUtils()
	{
		return $this->metadataUtils;
	}


	public function read($name, $scope)
	{
		$fieldDef = $this->getFieldDef($name, $scope);

		$fieldDef['label'] = $this->getLanguage()->translate($name, 'fields', $scope);

		return $fieldDef;
	}

	public function create($name, $fieldDef, $scope)
	{
		$existingField = $this->getFieldDef($name, $scope);
		if (isset($existingField)) {
			throw new Conflict('Field ['.$name.'] exists in '.$scope);
		}

		return $this->update($name, $fieldDef, $scope);
	}

	public function update($name, $fieldDef, $scope)
	{
		/*Add option to metadata to identify the custom field*/
		if (!$this->isCore($name, $scope)) {
			$fieldDef[$this->customOptionName] = true;
		}

		$res = true;
		if (isset($fieldDef['label'])) {
			$res &= $this->setLabel($name, $fieldDef['label'], $scope);
			unset($fieldDef['label']);
		}

		$res &= $this->setEntityDefs($name, $fieldDef, $scope);

		return (bool) $res;
	}

	public function delete($name, $scope)
	{
		if ($this->isCore($name, $scope)) {
			throw new Error('Cannot delete core field ['.$name.'] in '.$scope);
		}

		$unsets = array(
			'fields.'.$name,
			'links.'.$name,
		);
		$res = $this->getMetadata()->delete($unsets, $this->metadataType, $scope);

		$this->deleteLabel($name, $scope);

		return $res;
	}

	protected function setEntityDefs($name, $fieldDef, $scope)
	{
		$fieldDef = $this->normalizeDefs($name, $fieldDef, $scope);

		$data = Json::encode($fieldDef);
		$res = $this->getMetadata()->set($data, $this->metadataType, $scope);

		return $res;
	}

	protected function setLabel($name, $value, $scope)
	{
		return $this->getLanguage()->set($name, $value, 'fields', $scope);
	}

	protected function deleteLabel($name, $scope)
	{
		return $this->getLanguage()->delete($name, 'fields', $scope);
	}

	protected function getFieldDef($name, $scope)
	{
		return $this->getMetadata()->get($this->metadataType.'.'.$scope.'.fields.'.$name);
	}

	protected function getLinkDef($name, $scope)
	{
		return $this->getMetadata()->get($this->metadataType.'.'.$scope.'.links.'.$name);
	}

	/**
	 * Add all needed block for a field defenition
	 *
	 * @param string $fieldName
	 * @param array $fieldDef
	 * @param string $scope
	 * @return array
	 */
	protected function normalizeDefs($fieldName, array $fieldDef, $scope)
	{
		if (isset($fieldDef['name'])) {
			unset($fieldDef['name']);
		}

		if (isset($fieldDef['linkDefs'])) {
			$linkDefs = $fieldDef['linkDefs'];
			unset($fieldDef['linkDefs']);
		}

		foreach ($fieldDef as $defName => $defValue) {
			if (!isset($defValue) || (is_string($defValue) && $defValue == '') ) {
				unset($fieldDef[$defName]);
			}
		}

		$metaFieldDef = $this->getMetadataUtils()->getFieldDefsInFieldMeta($fieldDef);
		if (isset($metaFieldDef)) {
			$fieldDef = Util::merge($metaFieldDef, $fieldDef);
		}

		$defs = array(
			'fields' => array(
				$fieldName => $fieldDef,
			),
		);

		/** Save links for a field. */
		$metaLinkDef = $this->getMetadataUtils()->getLinkDefsInFieldMeta($scope, $fieldDef);
		if (isset($linkDefs) || isset($metaLinkDef)) {
			$linkDefs = Util::merge((array) $metaLinkDef, (array) $linkDefs);
			$defs['links'] = array(
				$fieldName => $linkDefs,
			);
		}

		return $defs;
	}

	protected function isCore($name, $scope)
	{
		$existingField = $this->getFieldDef($name, $scope);
		if (isset($existingField) && (!isset($existingField[$this->customOptionName]) || !$existingField[$this->customOptionName])) {
			return true;
		}

		return false;
	}

}