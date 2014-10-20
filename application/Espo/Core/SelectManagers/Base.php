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

    const MIN_LENGTH_FOR_CONTENT_SEARCH = 4;

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

    protected function getTextFilterFields()
    {
        return $this->metadata->get("entityDefs.{$this->entityName}.collection.textFilterFields", array('name'));
    }

    protected function where($params, &$result)
    {
        if (!empty($params['where']) && is_array($params['where'])) {
            $where = array();

            foreach    ($params['where'] as $item) {
                if ($item['type'] == 'boolFilters' && !empty($item['value']) && is_array($item['value'])) {
                    foreach ($item['value'] as $filter) {
                        $p = $this->getBoolFilterWhere($filter);
                        if (!empty($p)) {
                            $params['where'][] = $p;
                        }
                    }
                } else if ($item['type'] == 'textFilter' && !empty($item['value'])) {
                    if (!empty($item['value'])) {
                        if (empty($result['whereClause'])) {
                            $result['whereClause'] = array();
                        }
                        $fieldDefs = $this->entityManager->getEntity($this->entityName)->getFields();
                        $fieldList = $this->getTextFilterFields();
                        $d = array();
                        foreach ($fieldList as $field) {
                            if (
                                strlen($item['value']) >= self::MIN_LENGTH_FOR_CONTENT_SEARCH
                                &&
                                !empty($fieldDefs[$field]['type']) && $fieldDefs[$field]['type'] == 'text'
                            ) {
                                $d[$field . '*'] = '%' . $item['value'] . '%';
                            } else {
                                $d[$field . '*'] = $item['value'] . '%';
                            }
                        }
                        $where['OR'] = $d;
                    }
                }
            }

            $linkedWith = array();
            $ignoreList = array('linkedWith', 'boolFilters');
            foreach    ($params['where'] as $item) {
                if (!in_array($item['type'], $ignoreList)) {
                    $part = $this->getWherePart($item);
                    if (!empty($part)) {
                        $where[] = $part;
                    }
                } else {
                    if ($item['type'] == 'linkedWith' && !empty($item['value'])) {
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
            
            $fieldDefs = $this->entityManager->getEntity($this->entityName)->getFields();

            $value = $params['q'];

            $fieldList = $this->getTextFilterFields();
            $d = array();
            foreach ($fieldList as $field) {
                if (
                    strlen($item['value']) >= self::MIN_LENGTH_FOR_CONTENT_SEARCH
                    &&
                    !empty($fieldDefs[$field]['type']) && $fieldDefs[$field]['type'] == 'text'
                ) {
                    $d[$field . '*'] = '%' . $value . '%';
                } else {
                    $d[$field . '*'] = $value . '%';
                }
            }

            $result['whereClause']['OR'] = $d;
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
        if (!$this->user->isAdmin() && $this->acl->checkReadOnlyTeam($this->entityName)) {
            if (!array_key_exists('whereClause', $result)) {
                $result['whereClause'] = array();
            }
            $result['distinct'] = true;
            if (!array_key_exists('joins', $result)) {
                $result['joins'] = array();
            }
            if (!in_array('teams', $result['joins'])) {
                $result['leftJoins'][] = 'teams';
            }

            $result['whereClause']['OR'] = array(
                'Team.id' => $this->user->get('teamsIds'),
                'assignedUserId' => $this->user->id
            );
            //$result['whereClause']['Team.id'] = $this->user->get('teamsIds');
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
                case 'isTrue':
                    $part[$item['field'] . '='] = true;
                    break;
                case 'isFalse':
                    $part[$item['field'] . '='] = false;
                    break;
                case 'today':
                    $part[$item['field'] . '='] = date('Y-m-d');
                    break;
                case 'past':
                    $part[$item['field'] . '<'] = date('Y-m-d');
                    break;
                case 'future':
                    $part[$item['field'] . '>'] = date('Y-m-d');
                    break;
                case 'currentMonth':
                    $dt = new \DateTime();
                    $part['AND'] = array(
                        $item['field'] . '>=' => $dt->modify('first day of this month')->format('Y-m-d'),
                        $item['field'] . '<' => $dt->add(new \DateInterval('P1M'))->format('Y-m-d'),
                    );
                    break;
                case 'lastMonth':
                    $dt = new \DateTime();
                    $part['AND'] = array(
                        $item['field'] . '>=' => $dt->modify('first day of last month')->format('Y-m-d'),
                        $item['field'] . '<' => $dt->add(new \DateInterval('P1M'))->format('Y-m-d'),
                    );
                    break;
                case 'currentQuarter':
                    $dt = new \DateTime();
                    $quarter = ceil($dt->format('m') / 3);                        
                    $dt->modify('first day of January this year');                                
                    $part['AND'] = array(
                        $item['field'] . '>=' => $dt->add(new \DateInterval('P'.(($quarter - 1) * 3).'M'))->format('Y-m-d'),
                        $item['field'] . '<' => $dt->add(new \DateInterval('P3M'))->format('Y-m-d'),
                    );
                    break;
                case 'lastQuarter':
                    $dt = new \DateTime();
                    $quarter = ceil($dt->format('m') / 3);                    
                    $dt->modify('first day of January this year');                    
                    $quarter--;
                    if ($quarter == 0) {
                        $quarter = 4;
                        $dt->sub('P1Y');
                    }                        
                    $part['AND'] = array(
                        $item['field'] . '>=' => $dt->add(new \DateInterval('P'.(($quarter - 1) * 3).'M'))->format('Y-m-d'),
                        $item['field'] . '<' => $dt->add(new \DateInterval('P3M'))->format('Y-m-d'),
                    );
                    break;
                case 'currentYear':
                    $dt = new \DateTime();
                    $part['AND'] = array(
                        $item['field'] . '>=' => $dt->modify('first day of January this year')->format('Y-m-d'),
                        $item['field'] . '<' => $dt->add(new \DateInterval('P1Y'))->format('Y-m-d'),
                    );
                    break;
                case 'lastYear':
                    $dt = new \DateTime();
                    $part['AND'] = array(
                        $item['field'] . '>=' => $dt->modify('first day of January last year')->format('Y-m-d'),
                        $item['field'] . '<' => $dt->add(new \DateInterval('P1Y'))->format('Y-m-d'),
                    );
                    break;
                case 'between':
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

    protected function getBoolFilterWhere($filterName)
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

