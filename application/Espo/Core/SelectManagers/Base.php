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

namespace Espo\Core\SelectManagers;

use \Espo\Core\Exceptions\Error;

use \Espo\Core\Acl;

class Base
{	
	protected $container;
	
	protected $user;
	
	protected $acl;
	
	protected $entityManager;
	
	protected $entityName;
	
	protected $metadata;

    public function __construct($entityManager, \Espo\Entities\User $user, Acl $acl, $metadata)
    {
    	$this->entityManager = $entityManager;
    	$this->user = $user;
    	$this->acl = $acl;
    	$this->metadata = $metadata;
    }
    
    public function setEntityName($entityName)
    {
    	$this->entityName = $entityName;
    }
    
    protected function limit($params, &$result)
    {
		if (isset($params['offset']) && !is_null($params['offset'])) {
			$result['offset'] = $params['offset'];
		}
		if (isset($params['maxSize']) && !is_null($params['maxSize'])) {
			$result['limit'] = $params['maxSize'];
		}
    }
    
    protected function order($params, &$result)
    {
		if (!empty($params['sortBy'])) {
			$result['orderBy'] = $params['sortBy'];
			$type = $this->metadata->get("entityDefs.{$this->entityName}.fields." . $result['orderBy'] . ".type");
			if ($type == 'link') {
				$result['orderBy'] .= 'Name';
			} else if ($type == 'linkParent') {
				$result['orderBy'] .= 'Type';
			}			
		}
		if (isset($params['asc'])) {
			if ($params['asc']) {
				$result['order'] = 'ASC';
			} else {
				$result['order'] = 'DESC';
			}
		}
    }
    
    protected function where($params, &$result)
    {
		if (!empty($params['where']) && is_array($params['where'])) {
			$where = array();
			
			foreach	($params['where'] as $item) {
				if ($item['type'] == 'boolFilters' && is_array($item['value'])) {
					foreach ($item['value'] as $filter) {
						$p = $this->getBoolFilterWhere($filter);
						if (!empty($p)) {
							$params['where'][] = $p; 
						}
					}
				}
			}
			
			$linkedWith = array();
			$ignoreList = array('linkedWith', 'boolFilters');
			foreach	($params['where'] as $item) {
				if (!in_array($item['type'], $ignoreList)) {
					$part = $this->getWherePart($item);
					if (!empty($part)) {
						$where[] = $part;
					}					
				} else {
					if ($item['type'] == 'linkedWith') {
						$linkedWith[$item['field']] = $item['value'];
					}
				}
			}
			
			if (!empty($linkedWith)) {
				$joins = array();
				
				$part = array();				
				foreach ($linkedWith as $link => $ids) {
					$joins[] = $link;
					$defs = $this->entityManager->getMetadata()->get($this->entityName);
					
					$entityName = $defs['relations'][$link]['entity'];
					if ($entityName) {
						$part[$entityName . '.id'] = $ids;
					}					
				}
				
				if (!empty($part)) {
					$where[] = $part;
				}
				$result['joins'] = $joins;
				$result['distinct'] = true;
				
			}
			
			$result['whereClause'] = $where;
		}
    }
    
    protected function q($params, &$result)
    {
		if (!empty($params['q'])) {
			if (empty($result['whereClause'])) {
				$result['whereClause'] = array();
			}
			$result['whereClause']['name*'] = $params['q'] . '%';
		}	
	}    
    
    protected function access(&$result)
    {
    	if ($this->acl->checkReadOnlyOwn($this->entityName)) {

    		if (!array_key_exists('whereClause', $result)) {
    			$result['whereClause'] = array();
    		}
    		$result['whereClause']['assignedUserId'] = $this->user->id;    				
    	}
    	if ($this->acl->checkReadOnlyTeam($this->entityName)) {
    		if (!array_key_exists('whereClause', $result)) {
    			$result['whereClause'] = array();
    		}
    		if (!array_key_exists('joins', $result)) {
    			$result['joins'] = array();
    		}
    		if (!in_array('teams', $result['joins'])) {
    			$result['joins'][] = 'teams';
    		}
    		   		
    		$result['whereClause']['Team.id'] = $this->user->get('teamsIds'); 			
    	}    	
    }
    
    public function getAclParams()
    {
    	$result = array();
    	$this->access($result);
    	return $result;
    }

	public function getSelectParams(array $params, $withAcl = false)
	{
		$result = array();
		
		$this->order($params, $result);		
		$this->limit($params, $result);
		$this->where($params, $result);
		$this->q($params, $result);
		
		if ($withAcl) {
			$this->access($result);
		}
		
		return $result;
	}
	
	protected function getWherePart($item)
	{
		$part = array();
		
		if (!empty($item['type'])) {
			switch ($item['type']) {
				case 'or':
				case 'and':
					if (is_array($item['value'])) {
						$arr = array();						
						foreach ($item['value'] as $i) {
							$a = $this->getWherePart($i);
							foreach ($a as $left => $right) {
								if (!empty($right)) {
									$arr[$left] = $right;
								}
							}
						}
						$part[strtoupper($item['type'])] = $arr;						
					}					
					break;				
				case 'like':
					$part[$item['field'] . '*'] = $item['value'];
					break;
				case 'equals':
				case 'on':
					$part[$item['field'] . '='] = $item['value'];
					break;
				case 'notEquals':
				case 'notOn':					
					$part[$item['field'] . '!='] = $item['value'];
					break;
				case 'greaterThan':
				case 'after':					
					$part[$item['field'] . '>'] = $item['value'];
					break;
				case 'lessThan':
				case 'before':					
					$part[$item['field'] . '<'] = $item['value'];
					break;
				case 'greaterThanOrEquals':				
					$part[$item['field'] . '>='] = $item['value'];
					break;
				case 'lessThanOrEquals':				
					$part[$item['field'] . '<'] = $item['value'];
					break;
				case 'in':
					$part[$item['field'] . '='] = $item['value'];
					break;
				case 'notIn':
					$part[$item['field'] . '!='] = $item['value'];
					break;
				case 'isBetween':
					if (is_array($item['value'])) {
						$part['AND'] = array(
							$item['field'] . '>=' => $item['value'][0],
							$item['field'] . '<=' => $item['value'][1],
						);
					}
					break;	
			}		
		}
		
		return $part;	
	}
	
	protected function getBoolFilterWhere($filterName, $entityName)
	{		
		$method = 'getBoolFilterWhere' . ucfirst($filterName);
		if (method_exists($this, $method)) {
			return $this->$method();
		}		
	}
	
	protected function getBoolFilterWhereOnlyMy()
	{
		return array(
			'type' => 'equals',
			'field' => 'assignedUserId',
			'value' => $this->user->id,
		);
	}
}

