<?php

namespace Espo\Core;

use \Espo\Core\Exceptions\Error;

class SelectManager
{	
	protected $container;
	
	protected $user;
	
	protected $acl;
	
	protected $entityManager;
	
	protected $entityName;

    public function __construct(ORM\EntityManager $entityManager, \Espo\Entities\User $user, Acl $acl)
    {
    	$this->entityManager = $entityManager;
    	$this->user = $user;
    	$this->acl = $acl;
    }
    
    public function setEntityName($entityName)
    {
    	$this->entityName = $entityName;
    }

	public function getSelectParams(array $params, $withAcl = false)
	{
		$result = array();
		
		if (!empty($params['sortBy'])) {
			$result['orderBy'] = $params['sortBy'];
		}
		if (isset($params['asc'])) {
			if ($params['asc']) {
				$result['order'] = 'ASC';
			} else {
				$result['order'] = 'DESC';
			}
		}
		
		if (isset($params['offset']) && !is_null($params['offset'])) {
			$result['offset'] = $params['offset'];
		}
		if (isset($params['maxSize']) && !is_null($params['maxSize'])) {
			$result['limit'] = $params['maxSize'];
		}
		
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
			
			//print_r($where);
			//die;
			
			$result['whereClause'] = $where;
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

