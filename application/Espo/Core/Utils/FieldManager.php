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

use \Espo\Core\Exceptions\Error;

class FieldManager
{
	private $metadata;

	protected $metadataType = 'entityDefs';

	protected $customOptionName = 'isCustom';


	public function __construct(Metadata $metadata)
	{
		$this->metadata = $metadata;
	}

	protected function getMetadata()
	{
		return $this->metadata;
	}


	public function read($name, $scope)
	{
		return $this->getMetadata()->get($this->metadataType.'.'.$scope.'.fields.'.$name);
	}

	public function create($name, $fieldDef, $scope)
	{
		$existingField = $this->read($name, $scope);
		if (isset($existingField)) {
			throw new Error('Field ['.$name.'] exists in '.$scope);
		}

		return $this->update($name, $fieldDef, $scope);
	}

	public function update($name, $fieldDef, $scope)
	{
		/*Add option to metadata to identify the custom field*/
		if (!$this->isCore($name, $scope)) {
			$fieldDef[$this->customOptionName] = true;
		}

		return $this->setEntityDefs($name, $fieldDef, $scope);
	}

	public function delete($name, $scope)
	{
		if ($this->isCore($name, $scope)) {
			throw new Error('Cannot delete core field ['.$name.'] in '.$scope);
		}

		$unsets = 'fields.'.$name;

		return $this->getMetadata()->unsets($unsets, $this->metadataType, $scope);
	}

	protected function setEntityDefs($name, $fieldDef, $scope)
	{
		$fieldDef = $this->normalizeDefs($name, $fieldDef);

		$data = Json::encode($fieldDef);
		$result = $this->getMetadata()->set($data, $this->metadataType, $scope);

		return $result;
	}

	/**
	 * Add all needed block for a field defenition
	 *
	 * @param string $fieldName
	 * @param array $fieldDef
	 * @return array
	 */
	protected function normalizeDefs($fieldName, array $fieldDef)
	{
		if (isset($fieldDef['name'])) {
			unset($fieldDef['name']);
		}

		foreach ($fieldDef as $defName => $defValue) {
			if (!isset($defValue)) {
				unset($fieldDef[$defName]);
			}
		}

		return array(
			'fields' => array(
				$fieldName => $fieldDef,
			),
		);
	}

	protected function isCore($name, $scope)
	{
		$existingField = $this->read($name, $scope);
		if (isset($existingField) && (!isset($existingField[$this->customOptionName]) || !$existingField[$this->customOptionName])) {
			return true;
		}

		return false;
	}


}