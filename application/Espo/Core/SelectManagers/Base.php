<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
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

    protected $entityType;

    protected $metadata;

    private $seed = null;

    private $userTimeZone = null;

    const MIN_LENGTH_FOR_CONTENT_SEARCH = 4;

    public function __construct($entityManager, \Espo\Entities\User $user, Acl $acl, $metadata)
    {
        $this->entityManager = $entityManager;
        $this->user = $user;
        $this->acl = $acl;

        $this->metadata = $metadata;
    }

    protected function getEntityManager()
    {
        return $this->entityManager;
    }

    protected function getUser()
    {
        return $this->user;
    }

    protected function getAcl()
    {
        return $this->acl;
    }

    public function setEntityType($entityType)
    {
        $this->entityType = $entityType;
    }

    protected function getEntityType()
    {
        return $this->entityType;
    }

    protected function limit($offset = null, $maxSize = null, &$result)
    {
        if (!is_null($offset)) {
            $result['offset'] = $offset;
        }
        if (!is_null($maxSize)) {
            $result['limit'] = $maxSize;
        }
    }

    protected function order($sortBy, $asc, &$result)
    {
        if (!empty($sortBy)) {
            $result['orderBy'] = $sortBy;
            $type = $this->metadata->get("entityDefs.{$this->entityType}.fields." . $result['orderBy'] . ".type");
            if ($type == 'link') {
                $result['orderBy'] .= 'Name';
            } else if ($type == 'linkParent') {
                $result['orderBy'] .= 'Type';
            }
        }
        if ($asc) {
            $result['order'] = 'ASC';
        } else {
            $result['order'] = 'DESC';
        }
    }

    protected function getTextFilterFields()
    {
        return $this->metadata->get("entityDefs.{$this->entityType}.collection.textFilterFields", array('name'));
    }

    protected function getSeed()
    {
        if (empty($this->seed)) {
            $this->seed = $this->entityManager->getEntity($this->entityType);
        }
        return $this->seed;
    }

    public function applyWhere($where, &$result)
    {
        $this->prepareResult($result);
        $this->where($where, $result);
    }

    protected function where($where, &$result)
    {
        $this->prepareResult($result);

        $whereClause = array();
        foreach ($where as $item) {
            if ($item['type'] == 'bool' && !empty($item['value']) && is_array($item['value'])) {
                foreach ($item['value'] as $filter) {
                    $p = $this->getBoolFilterWhere($filter);
                    if (!empty($p)) {
                        $where[] = $p;
                    }
                    $this->applyBoolFilter($filter, $result);
                }
            } else if ($item['type'] == 'textFilter' && !empty($item['value'])) {
                if (!empty($item['value'])) {
                    $this->textFilter($item['value'], $result);
                }
            } else if ($item['type'] == 'primary' && !empty($item['value'])) {
                $this->applyPrimaryFilter($item['value'], $result);
            }
        }

        $linkedWith = array();
        $inCategory = array();

        $ignoreList = ['linkedWith', 'inCategory', 'bool', 'primary'];
        foreach ($where as $item) {
            if (!in_array($item['type'], $ignoreList)) {
                $part = $this->getWherePart($item);
                if (!empty($part)) {
                    $whereClause[] = $part;
                }
            } else {
                if ($item['type'] == 'linkedWith' && !empty($item['value'])) {
                    $linkedWith[$item['field']] = $item['value'];
                } else if ($item['type'] == 'inCategory' && !empty($item['value'])) {
                    $inCategory[$item['field']] = $item['value'];
                }
            }
        }

        $result['whereClause'] = array_merge($result['whereClause'], $whereClause);

        if (!empty($linkedWith)) {
            $this->handleLinkedWith($linkedWith, $result);
        }
        if (!empty($inCategory)) {
            $this->handleInCategory($inCategory, $result);
        }
    }

    protected function handleLinkedWith($linkedWith, &$result)
    {
        $joins = [];

        $part = array();
        foreach ($linkedWith as $link => $idsValue) {
            if (is_array($idsValue) && count($idsValue) == 1) {
                $idsValue = $idsValue[0];
            }

            $relDefs = $this->getSeed()->getRelations();

            if (!empty($relDefs[$link])) {
                $defs = $relDefs[$link];
                if ($defs['type'] == 'manyMany') {
                    $joins[] = $link;
                    if (!empty($defs['midKeys'])) {
                        $key = $defs['midKeys'][1];
                        $part[$link . 'Middle.' . $key] = $idsValue;
                    }
                } else if ($defs['type'] == 'belongsTo') {
                    if (!empty($defs['key'])) {
                        $key = $defs['key'];
                        $part[$key] = $idsValue;
                    }
                }
            }
        }

        if (!empty($part)) {
            $result['whereClause'][] = $part;
        }
        $result['joins'] = array_merge($result['joins'], $joins);
        $result['joins'] = array_unique($result['joins']);
        $result['distinct'] = true;
    }

    protected function handleInCategory($inCategory, &$result)
    {
        $joins = [];

        $part = array();

        $query = $this->getEntityManager()->getQuery();

        $tableName = $query->toDb($this->getSeed()->getEntityType());

        foreach ($inCategory as $link => $val) {

            $relDefs = $this->getSeed()->getRelations();

            if (!empty($relDefs[$link])) {
                $defs = $relDefs[$link];

                $foreignEntity = $defs['entity'];
                if (empty($foreignEntity)) {
                    continue;
                }

                $pathName = lcfirst($query->sanitize($foreignEntity . 'Path'));

                if ($defs['type'] == 'manyMany') {

                    if (!empty($defs['midKeys'])) {
                        $result['distinct'] = true;
                        $result['joins'][] = $link;
                        $key = $defs['midKeys'][1];

                        $middleName = $link . 'Middle';

                        $result['customJoin'] .= "
                            JOIN " . $query->toDb($pathName) . " AS `{$pathName}` ON {$pathName}.descendor_id = ".$query->sanitize($middleName) . "." . $query->toDb($key) . "
                        ";
                        $part[$pathName . '.ascendorId'] = $val;
                    }
                } else if ($defs['type'] == 'belongsTo') {
                    if (!empty($defs['key'])) {
                        $key = $defs['key'];
                        $result['customJoin'] .= "
                            JOIN " . $query->toDb($pathName) . " AS `{$pathName}` ON {$pathName}.descendor_id = {$tableName}." . $query->toDb($key) . "
                        ";
                        $part[$pathName . '.ascendorId'] = $val;
                    }
                }
            }
        }


        if (!empty($part)) {
            $result['whereClause'][] = $part;
        }

    }

    protected function q($params, &$result)
    {
        if (!empty($params['q'])) {
            $this->textFilter($params['q'], $result);
        }
    }

    public function manageAccess(&$result)
    {
        $this->prepareResult($result);
        $this->applyAccess($result);
    }

    public function manageTextFilter($textFilter, &$result)
    {
        $this->prepareResult($result);
        $this->q(array('q' => $textFilter), $result);
    }

    protected function prepareResult(&$result)
    {
        if (empty($result)) {
            $result = array();
        }
        if (empty($result['joins'])) {
            $result['joins'] = [];
        }
        if (empty($result['leftJoins'])) {
            $result['leftJoins'] = [];
        }
        if (empty($result['whereClause'])) {
            $result['whereClause'] = array();
        }
        if (empty($result['customJoin'])) {
            $result['customJoin'] = '';
        }
        if (empty($result['additionalSelectColumns'])) {
            $result['additionalSelectColumns'] = array();
        }
        if (empty($result['joinConditions'])) {
            $result['joinConditions'] = array();
        }
    }

    protected function access(&$result)
    {
        if ($this->acl->checkReadOnlyOwn($this->entityType)) {
            $this->accessOnlyOwn($result);
        } else {
            if (!$this->user->isAdmin() && $this->acl->checkReadOnlyTeam($this->entityType)) {
                $this->accessOnlyTeam($result);
            }
        }
    }

    protected function accessOnlyOwn(&$result)
    {
        if ($this->getSeed()->hasField('assignedUserId')) {
            $result['whereClause'][] = array(
                'assignedUserId' => $this->getUser()->id
            );
            return;
        }

        if ($this->getSeed()->hasField('createdById')) {
            $result['whereClause'][] = array(
                'createdById' => $this->getUser()->id
            );
            return;
        }
    }

    protected function accessOnlyTeam(&$result)
    {
        if (!$this->getSeed()->hasField('teamsIds')) {
            return;
        }
        $result['distinct'] = true;
        if (!in_array('teams', $result['joins'])) {
            $result['leftJoins'][] = 'teams';
        }
        $result['whereClause'][] = array(
            'OR' => array(
                'teams.id' => $this->user->get('teamsIds'),
                'assignedUserId' => $this->getUser()->id
            )
        );
    }

    public function getAclParams()
    {
        $result = array();
        $this->applyAccess($result);
        return $result;
    }

    public function getSelectParams(array $params, $withAcl = false)
    {
        $result = array();
        $this->prepareResult($result);

        if (!empty($params['sortBy'])) {
            if (!array_key_exists('asc', $params)) {
                $params['asc'] = true;
            }
            $this->order($params['sortBy'], $params['asc'], $result);
        }

        if (!isset($params['offset'])) {
            $params['offset'] = null;
        }
        if (!isset($params['maxSize'])) {
            $params['maxSize'] = null;
        }
        $this->limit($params['offset'], $params['maxSize'], $result);

        if (!empty($params['primaryFilter'])) {
            $this->applyPrimaryFilter($params['primaryFilter'], $result);
        }

        if (!empty($params['boolFilterList']) && is_array($params['boolFilterList'])) {
            foreach ($params['boolFilterList'] as $filterName) {
                $this->applyBoolFilter($filterName, $result);
            }
        }

        if (!empty($params['where']) && is_array($params['where'])) {
            $this->where($params['where'], $result);
        }

        if (!empty($params['textFilter'])) {
            $this->textFilter($params['textFilter'], $result);
        }

        $this->q($params, $result);

        if ($withAcl) {
            $this->access($result);
        }

        return $result;
    }

    protected function getUserTimeZone()
    {
        if (empty($this->userTimeZone)) {
            $preferences = $this->getEntityManager()->getEntity('Preferences', $this->getUser()->id);
            $timeZone = $preferences->get('timeZone');
            $this->userTimeZone = $timeZone;
        }

        return $this->userTimeZone;
    }

    protected function convertDateTimeWhere($item)
    {
        $format = 'Y-m-d H:i:s';

        $value = null;
        $timeZone = 'UTC';

        if (empty($item['field'])) {
            return null;
        }
        if (empty($item['type'])) {
            return null;
        }
        if (!empty($item['value'])) {
            $value = $item['value'];
        }
        if (!empty($item['timeZone'])) {
            $timeZone = $item['timeZone'];
        }
        $type = $item['type'];
        $field = $item['field'];

        if (empty($value) && in_array($type, array('on', 'before', 'after'))) {
            return null;
        }

        $where = array();
        $where['field'] = $field;

        $dt = new \DateTime('now', new \DateTimeZone($timeZone));

        switch ($type) {
            case 'today':
                $where['type'] = 'between';
                $dt->setTime(0, 0, 0);
                $dt->setTimezone(new \DateTimeZone('UTC'));
                $from = $dt->format($format);
                $dt->modify('+1 day');
                $to = $dt->format($format);
                $where['value'] = [$from, $to];
                break;
            case 'past':
                $where['type'] = 'before';
                $dt->setTimezone(new \DateTimeZone('UTC'));
                $where['value'] = $dt->format($format);
                break;
            case 'future':
                $where['type'] = 'after';
                $dt->setTimezone(new \DateTimeZone('UTC'));
                $where['value'] = $dt->format($format);
                break;
            case 'lastSevenDays':
                $where['type'] = 'between';

                $dtFrom = clone $dt;

                $dt->setTimezone(new \DateTimeZone('UTC'));
                $to = $dt->format($format);


                $dtFrom->modify('-7 day');
                $dtFrom->setTime(0, 0, 0);
                $dtFrom->setTimezone(new \DateTimeZone('UTC'));

                $from = $dtFrom->format($format);

                $where['value'] = [$from, $to];

                break;
            case 'lastXDays':
                $where['type'] = 'between';

                $dtFrom = clone $dt;

                $dt->setTimezone(new \DateTimeZone('UTC'));
                $to = $dt->format($format);

                $number = strval(intval($item['value']));
                $dtFrom->modify('-'.$number.' day');
                $dtFrom->setTime(0, 0, 0);
                $dtFrom->setTimezone(new \DateTimeZone('UTC'));

                $from = $dtFrom->format($format);

                $where['value'] = [$from, $to];

                break;
            case 'nextXDays':
                $where['type'] = 'between';

                $dtTo = clone $dt;

                $dt->setTimezone(new \DateTimeZone('UTC'));
                $from = $dt->format($format);

                $number = strval(intval($item['value']));
                $dtTo->modify('+'.$number.' day');
                $dtTo->setTime(24, 59, 59);
                $dtTo->setTimezone(new \DateTimeZone('UTC'));

                $to = $dtTo->format($format);

                $where['value'] = [$from, $to];

                break;
            case 'on':
                $where['type'] = 'between';

                $dt = new \DateTime($value, new \DateTimeZone($timeZone));
                $dt->setTimezone(new \DateTimeZone('UTC'));
                $from = $dt->format($format);

                $dt->modify('+1 day');
                $to = $dt->format($format);
                $where['value'] = [$from, $to];
                break;
            case 'before':
                $where['type'] = 'before';
                $dt = new \DateTime($value, new \DateTimeZone($timeZone));
                $dt->setTimezone(new \DateTimeZone('UTC'));
                $where['value'] = $dt->format($format);
                break;
            case 'after':
                $where['type'] = 'after';
                $dt = new \DateTime($value, new \DateTimeZone($timeZone));
                $dt->setTimezone(new \DateTimeZone('UTC'));
                $where['value'] = $dt->format($format);
                break;
            case 'between':
                $where['type'] = 'between';
                if (is_array($value)) {
                    $dt = new \DateTime($value[0], new \DateTimeZone($timeZone));
                    $dt->setTimezone(new \DateTimeZone('UTC'));
                    $from = $dt->format($format);

                    $dt = new \DateTime($value[1], new \DateTimeZone($timeZone));
                    $dt->setTimezone(new \DateTimeZone('UTC'));
                    $to = $dt->format($format);

                    $where['value'] = [$from, $to];
                }
               break;
            default:
                $where['type'] = $type;
        }
        $result = $this->getWherePart($where);

        return $result;
    }

    protected function getWherePart($item)
    {
        $part = array();

        if (!empty($item['dateTime'])) {
            return $this->convertDateTimeWhere($item);
        }

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
                                    $arr[] = array($left => $right);
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
                case 'startsWith':
                    $part[$item['field'] . '*'] = $item['value'] . '%';
                    break;
                case 'contains':
                    $part[$item['field'] . '*'] = '%' . $item['value'] . '%';
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
                case 'isNull':
                    $part[$item['field'] . '='] = null;
                    break;
                case 'isNotNull':
                    $part[$item['field'] . '!='] = null;
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
                    $part[$item['field'] . '>='] = date('Y-m-d');
                    break;
                case 'lastSevenDays':
                    $dt1 = new \DateTime();
                    $dt2 = clone $dt1;
                    $dt2->modify('-7 days');
                    $part['AND'] = array(
                        $item['field'] . '>=' => $dt2->format('Y-m-d'),
                        $item['field'] . '<=' => $dt1->format('Y-m-d'),
                    );
                    break;
                case 'lastXDays':
                    $dt1 = new \DateTime();
                    $dt2 = clone $dt1;
                    $number = strval(intval($item['value']));

                    $dt2->modify('-'.$number.' days');
                    $part['AND'] = array(
                        $item['field'] . '>=' => $dt2->format('Y-m-d'),
                        $item['field'] . '<=' => $dt1->format('Y-m-d'),
                    );
                    break;
                case 'nextXDays':
                    $dt1 = new \DateTime();
                    $dt2 = clone $dt1;
                    $number = strval(intval($item['value']));
                    $dt2->modify('+'.$number.' days');
                    $part['AND'] = array(
                        $item['field'] . '>=' => $dt1->format('Y-m-d'),
                        $item['field'] . '<=' => $dt2->format('Y-m-d'),
                    );
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

    public function applyOrder($sortBy, $asc, &$result)
    {
        $this->prepareResult($result);
        $this->order($sortBy, $asc, $result);
    }

    public function applyLimit($offset, $maxSize, &$result)
    {
        $this->prepareResult($result);
        $this->limit($offset, $maxSize, $result);
    }

    public function applyPrimaryFilter($filterName, &$result)
    {
        $this->prepareResult($result);

        $method = 'filter' . ucfirst($filterName);
        if (method_exists($this, $method)) {
            $this->$method($result);
        }
    }

    public function applyBoolFilter($filterName, &$result)
    {
        $this->prepareResult($result);

        $method = 'boolFilter' . ucfirst($filterName);
        if (method_exists($this, $method)) {
            $this->$method($result);
        }
    }

    public function applyTextFilter($textFilter, &$result)
    {
        $this->prepareResult($result);
        $this->textFilter($textFilter, $result);
    }

    protected function textFilter($textFilter, &$result)
    {
        $fieldDefs = $this->getSeed()->getFields();
        $fieldList = $this->getTextFilterFields();
        $d = array();

        foreach ($fieldList as $field) {
            if (
                strlen($textFilter) >= self::MIN_LENGTH_FOR_CONTENT_SEARCH
                &&
                !empty($fieldDefs[$field]['type']) && $fieldDefs[$field]['type'] == 'text'
            ) {
                $d[$field . '*'] = '%' . $textFilter . '%';
            } else {
                $d[$field . '*'] = $textFilter . '%';
            }
        }
        $result['whereClause'][] = array(
            'OR' => $d
        );
    }

    public function applyAccess(&$result)
    {
        $this->prepareResult($result);
        $this->access($result);
    }

    protected function boolFilters($params, &$result)
    {
        if (!empty($params['boolFilterList']) && is_array($params['boolFilterList'])) {
            foreach ($params['boolFilterList'] as $filterName) {
                $this->applyBoolFilter($filterName, $result);
            }
        }
    }

    protected function getBoolFilterWhere($filterName)
    {
        $method = 'getBoolFilterWhere' . ucfirst($filterName);
        if (method_exists($this, $method)) {
            return $this->$method();
        }
    }

    protected function boolFilterOnlyMy(&$result)
    {
        $result['whereClause'][] = array(
            'assignedUserId' => $this->getUser()->id
        );
    }

    protected function filterFollowed(&$result)
    {
        $query = $this->getEntityManager()->getQuery();
        $result['customJoin'] .= "
            JOIN subscription ON
                subscription.entity_type = ".$query->quote($this->getEntityType())." AND
                subscription.entity_id = ".$query->toDb($this->getEntityType()).".id AND
                subscription.user_id = ".$query->quote($this->getUser()->id)."
        ";
    }

    protected function boolFilterFollowed(&$result)
    {
        $this->filterFollowed($result);
    }
}

