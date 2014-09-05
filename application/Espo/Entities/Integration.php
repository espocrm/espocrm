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

namespace Espo\Entities;

class Integration extends \Espo\Core\ORM\Entity
{	
	public function get($name)
	{
		if ($name == 'id') {
			return $this->id;
		}
		
		if ($this->hasField($name)) {
			if (array_key_exists($name, $this->valuesContainer)) {
				return $this->valuesContainer[$name];
			}
		} else {
			if ($this->get('data')) { 
				$data = json_decode($this->get('data'), true);
			} else {
				$data = array();
			}
			if (isset($data[$name])) {
				return $data[$name];
			}
		}
		return null;
	}
	
	public function set($p1, $p2)
	{
		if (is_array($p1)) {
			if ($p2 === null) {
				$p2 = false;
			}
			$this->populateFromArray($p1, $p2);
			return;
		}
		
		$name = $p1;
		$value = $p2;
		
		if ($name == 'id') {
			$this->id = $value;
			return;
		}
				
		if ($this->hasField($name)) {
			$this->valuesContainer[$name] = $value;
		} else {
			$data = json_decode($this->get('data'), true);
			if (empty($data)) {
				$data = array();
			}
			$data[$name] = $value;
			$this->set('data', json_encode($data));		
		}
	}
	
	public function populateFromArray(array $arr, $onlyAccessible = true, $reset = false)
	{
		if ($reset) {
			$this->reset();
		}
	
		foreach ($arr as $field => $value) {			
			if (is_string($field)) {
				if (is_array($value)) {
					$value = json_encode($value);
				}
				
				if ($this->hasField($field)) {
					$fields = $this->getFields();
					$fieldDefs = $fields[$field];
					
					if (!is_null($value)) {
						switch ($fieldDefs['type']) {
							case self::VARCHAR:						
								break;
							case self::BOOL:
								$value = ($value === 'true' || $value === '1' || $value === true);
								break;
							case self::INT:
								$value = intval($value);
								break;
							case self::FLOAT:
								$value = floatval($value);
								break;
							case self::JSON_ARRAY:
								$value = is_string($value) ? json_decode($value) : $value;
								if (!is_array($value)) {
									$value = null;
								}
								break;
							default:
								break;
						}
					}
				}
				

				$this->set($field, $value);
			}
		}
	}
	
	public function toArray()
	{		
		$arr = array();
		if (isset($this->id)) {
			$arr['id'] = $this->id;
		}
		foreach ($this->fields as $field => $defs) {		
			if ($field == 'id') {
				continue;
			}
			if ($this->has($field)) {
				$arr[$field] = $this->get($field);
			}
		}
		
		$data = json_decode($this->get('data'), true);
		if (empty($data)) {
			$data = array();
		}

		$arr = array_merge($arr, $data);		
		return $arr;
	}
	
}

