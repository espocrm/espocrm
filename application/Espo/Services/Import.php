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

namespace Espo\Services;

use \Espo\Core\Exceptions\Forbidden;
use \Espo\Core\Exceptions\NotFound;
use \Espo\Core\Exceptions\Error;

use Espo\ORM\Entity;

class Import extends \Espo\Core\Services\Base
{	
	protected $dependencies = array(
		'entityManager',
		'user',
		'metadata',
		'acl',
		'selectManagerFactory',
		'config',
		'serviceFactory',
		'fileManager',
	);
	
	protected $dateFormatsMap = array(
		'YYYY-MM-DD' => 'Y-m-d',
		'DD-MM-YYYY' => 'd-m-Y',
		'MM-DD-YYYY' => 'm-d-Y',
		'MM/DD/YYYY' => 'm/d/Y',
		'DD/MM/YYYY' => 'd/m/Y',
		'DD.MM.YYYY' => 'd.m.Y',
		'MM.DD.YYYY' => 'm.d.Y',
		'YYYY.MM.DD' => 'Y.m.d',		
	);
	
	protected $timeFormatsMap = array(
		'HH:mm' => 'H:i',
		'hh:mm a' => 'h:i a',
		'hh:mma' => 'h:ia',
		'hh:mm A' => 'h:iA',
		'hh:mmA' => 'h:iA',		
	);
	
	protected function getSelectManagerFactory()
	{
		return $this->injections['selectManagerFactory'];
	}

	protected function getEntityManager()
	{
		return $this->injections['entityManager'];
	}
	
	protected function getFileManager()
	{
		return $this->injections['fileManager'];
	}

	protected function getUser()
	{
		return $this->injections['user'];
	}
	
	protected function getAcl()
	{
		return $this->injections['acl'];
	}
	
	protected function getMetadata()
	{
		return $this->injections['metadata'];
	}
	
	protected function getConfig()
	{
		return $this->injections['config'];
	}	
	
	protected function getServiceFactory()
	{
		return $this->injections['serviceFactory'];
	}
	
	
	protected function readCsvString(&$string, $CSV_SEPARATOR = ';', $CSV_ENCLOSURE = '"', $CSV_LINEBREAK = "\n")
	{
		$o = array();
		$cnt = strlen($string);
		$esc = false;
		$escesc = false;
		$num = 0;
		$i = 0;
		while ($i < $cnt) {
			$s = $string[$i];
			if ($s == $CSV_LINEBREAK) {
				if ($esc) {
					$o[$num].= $s;
				}
				else {
					$i++;
					break;
				}
			}
			elseif ($s == $CSV_SEPARATOR) {
				if ($esc) {
					$o[$num].= $s;
				}
				else {
					$num++;
					$esc = false;
					$escesc = false;
				}
			}
			elseif ($s == $CSV_ENCLOSURE) {
				if ($escesc) {
					$o[$num].= $CSV_ENCLOSURE;
					$escesc = false;
				}

				if ($esc) {
					$esc = false;
					$escesc = true;
				}
				else {
					$esc = true;
					$escesc = false;
				}
			}
			else {
				if ($escesc) {
					$o[$num].= $CSV_ENCLOSURE;
					$escesc = false;
				}

				$o[$num].= $s;
			}

			$i++;
		}
		$string = substr($string, $i);
		return $o;
	}
	
	public function revert($scope, array $idsToRemove)
	{
		$ids = array();
		if (!empty($scope) && !empty($idsToRemove)) {
			foreach ($idsToRemove as $id) {
				$entity = $this->getEntityManager()->getEntity($scope, $id); 
				if ($this->getEntityManager()->removeEntity($entity)) {
					$ids[] = $id;	
				}
			}
		}
		return $ids;		
	}
	
	public function import($scope, array $fields, $attachmentId, array $params = array())
	{
		$delimiter = ',';
		if (!empty($params['fieldDelimiter'])) {
			$delimiter = $params['fieldDelimiter'];
		}
		$enclosure = '"';
		if (!empty($params['textQualifier'])) {
			$enclosure = $params['textQualifier'];
		}		
		
		$contents = $this->getFileManager()->getContents('data/upload/' . $attachmentId);
		
		if (empty($contents)) {
			throw new Error('Import error');
		}		
		
		$result	= array(
			'importedIds' => array(),
			'updatedIds' => array(),
			'duplicateIds' => array(),			 
		);	
		$i = -1;
		while ($arr = $this->readCsvString($contents, $delimiter, $enclosure)) {
			$i++;			
			if ($i == 0 && !empty($params['headerRow'])) {
				continue;
			}
			if (count($arr) == 1 && empty($arr[0])) {
				continue;
			}
			$r = $this->importRow($scope, $fields, $arr, $params);
			if (!empty($r['imported'])) {
				$result['importedIds'][] = $r['id'];
			}
			if (!empty($r['updated'])) {
				$result['updatedIds'][] = $r['id'];
			}
			if (!empty($r['duplicate'])) {
				$result['duplicateIds'][] = $r['id'];
			}
					
		}
		return array(
			'countCreated' => count($result['importedIds']),
			'countUpdated' => count($result['updatedIds']),
			'importedIds' => $result['importedIds'],
			'duplicateIds' => $result['duplicateIds'],
		);	
	}
	
	public function importRow($scope, array $fields, array $row, array $params = array())
	{
		// TODO create related records or related if exists, e.g. Account from accountName (skip users)
		// Duplicate check
		
		$id = null;
		if (!empty($params['action'])) {
			if ($params['action'] == 'createAndUpdate' && in_array('id', $fields)) {
				$i = array_search('id', $fields);
				$id = $row[$i];
				if (empty($id)) {
					$id = null;
				}			
			}
		}
		
		
		$entity = $this->getEntityManager()->getEntity($scope, $id);
		
		$entity->set('assignedUserId', $this->getUser()->id);
		
		if (!empty($params['defaultValues'])) {
			$entity->set(get_object_vars($params['defaultValues']));
		}
		
		$fieldsDefs = $entity->getFields();
		$relDefs = $entity->getRelations();

		foreach ($fields as $i => $field) {
			if (!empty($field)) {
				if ($field == 'id') {
					continue;
				}								
				$value = $row[$i];			
				if (array_key_exists($field, $fieldsDefs)) {
					if ($value !== '') {
						$type = $this->getMetadata()->get("entityDefs.{$scope}.fields.{$field}.type");
						if ($type == 'personName') {
							$lastNameField = 'last' . ucfirst($field);
							$firstNameField = 'first' . ucfirst($field);
							
							$firstName = '';
							$lastName = $value;
							switch ($params['personNameFormat']) {

								case 'f l':
									$pos = strpos($value, ' ');
									if ($pos) {
										$firstName = trim(substr($value, 0, $pos));
										$lastName = trim(substr($value, $pos + 1));
									}
									break;
								case 'l f':
									$pos = strpos($value, ' ');
									if ($pos) {
										$lastName = trim(substr($value, 0, $pos));
										$firstName = trim(substr($value, $pos + 1));
									}
									break;
								case 'l, f':
									$pos = strpos($value, ',');
									if ($pos) {
										$lastName = trim(substr($value, 0, $pos));
										$firstName = trim(substr($value, $pos + 1));
									}
									break;
							}
							
							if (!$entity->get($firstNameField)) {
								$entity->set($firstNameField, $firstName);
							}
							if (!$entity->get($lastNameField)) {
								$entity->set($lastNameField, $lastName);
							}
							continue;
						}					
						$entity->set($field, $this->parseValue($entity, $field, $value, $params));
					}	
				}
			}		
		}
		
		
		
		foreach ($fields as $i => $field) {
			if (array_key_exists($field, $fieldsDefs) && $fieldsDefs[$field]['type'] == Entity::FOREIGN) {
				if ($entity->has($field)) {
					$relation = $fieldsDefs[$field]['relation'];					
					if ($field == $relation . 'Name' && !$entity->has($relation . 'Id') && array_key_exists($relation, $relDefs)) {
						if ($relDefs[$relation]['type'] == Entity::BELONGS_TO) {
							$name = $entity->get($field);
							$scope = $relDefs[$relation]['entity'];							
							$found = $this->getEntityManager()->getRepository($scope)->where(array('name' => $name))->findOne();
														
							if ($found) {
								$entity->set($relation . 'Id', $found->id);
							} else {										
								if (!in_array($scope, 'User', 'Team')) {
								
									// TODO create related record with name $name and relate
								}
							}										
						}												
					}
				}
			}
			
		}

		$result = array();
		
		$a = $entity->toArray();
		
		if ($this->getEntityManager()->saveEntity($entity)) {
			$result['id'] = $entity->id;
			if (empty($id)) {
				$result['imported'] = true;
			} else {
				$result['updated'] = true;
			}
		}
		return $result;
	}
	
	protected function parseValue(Entity $entity, $field, $value, $params = array())
	{
		$decimalMark = '.';
		if (!empty($params['decimalMark'])) {
			$decimalMark = $params['decimalMark'];
		}
		
		$defaultCurrency = 'USD';
		if (!empty($params['defaultCurrency'])) {
			$dateFormat = $params['defaultCurrency'];
		}
		
		$dateFormat = 'Y-m-d';
		if (!empty($params['dateFormat'])) {
			$dateFormat = $params['dateFormat'];
		}
		
		$timeFormat = 'H:i';
		if (!empty($params['timeFormat'])) {
			$timeFormat = $params['timeFormat'];
		}
		
		$fieldDefs = $entity->getFields();
		
		if (!empty($fieldDefs[$field])) {
			$type = $fieldDefs[$field]['type'];
			
			switch ($type) {
				case Entity::DATE:
					$dt = \DateTime::createFromFormat($dateFormat, $value);
					if ($dt) {
						return $dt->format('Y-m-d');
					}
					break;
				case Entity::DATETIME:
					$dt = \DateTime::createFromFormat($dateFormat . ' ' . $timeFormat, $value);
					if ($dt) {
						return $dt->format('Y-m-d H:i');
					}
					break;
				case Entity::FLOAT:										
					$currencyField = $field . 'Currency';
					if ($entity->hasField($currencyField)) {
						if (!$entity->has($currencyField)) {
							$entity->set($currencyField, $defaultCurrency);
						}
					}
					
					$a = explode($decimalMark, $value);
					$a[0] = preg_replace('/[^A-Za-z0-9\-]/', '', $a[0]);
					
					if (count($a) > 1) {
						return $a[0] . '.' . $a[1];
					} else {
						return $a[0];
					}
					break;
			}
			
		}
		return $value;		
	}
}

