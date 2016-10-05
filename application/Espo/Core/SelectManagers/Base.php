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
use \Espo\Core\Exceptions\Forbidden;

use \Espo\Core\Acl;
use \Espo\Core\AclManager;
use \Espo\Core\Utils\Metadata;
use \Espo\Core\Utils\Config;

class Base
{
    protected $container;

    protected $user;

    protected $acl;

    protected $entityManager;

    protected $entityType;

    protected $metadata;

    private $config;

    private $seed = null;

    private $userTimeZone = null;

    protected $additionalFilterTypeList = ['linkedWith', 'inCategory', 'isUserFromTeams'];

    const MIN_LENGTH_FOR_CONTENT_SEARCH = 4;

    public function __construct($entityManager, \Espo\Entities\User $user, Acl $acl, AclManager $aclManager, Metadata $metadata, Config $config)
    {
        $this->entityManager = $entityManager;
        $this->user = $user;
        $this->acl = $acl;
        $this->aclManager = $aclManager;

        $this->metadata = $metadata;
        $this->config = $config;
    }

    protected function getEntityManager()
    {
        return $this->entityManager;
    }

    protected function getMetadata()
    {
        return $this->metadata;
    }

    protected function getUser()
    {
        return $this->user;
    }

    protected function getAcl()
    {
        return $this->acl;
    }

    protected function getConfig()
    {
        return $this->config;
    }

    protected function getAclManager()
    {
        return $this->aclManager;
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

    protected function order($sortBy, $desc = false, &$result)
    {
        if (!empty($sortBy)) {

            $result['orderBy'] = $sortBy;
            $type = $this->getMetadata()->get(['entityDefs', $this->getEntityType(), 'fields', $sortBy, 'type']);
            if ($type === 'link') {
                $result['orderBy'] .= 'Name';
            } else if ($type === 'linkParent') {
                $result['orderBy'] .= 'Type';
            } else if ($type === 'address') {
                if (!$desc) {
                    $orderPart = 'ASC';
                } else {
                    $orderPart = 'DESC';
                }
                $result['orderBy'] = [[$sortBy . 'Country', $orderPart], [$sortBy . 'City', $orderPart], [$sortBy . 'Street', $orderPart]];
                return;
            } else if ($type === 'enum') {
                $list = $this->getMetadata()->get(['entityDefs', $this->getEntityType(), 'fields', $sortBy, 'options']);
                if ($list && is_array($list) && count($list)) {
                    if ($this->getMetadata()->get(['entityDefs', $this->getEntityType(), 'fields', $sortBy, 'isSorted'])) {
                        $list = asort($list);
                    }
                    if ($desc) {
                        $list = array_reverse($list);
                    }
                    $result['orderBy'] = 'LIST:' . $sortBy . ':' . implode(',', $list);
                    return;
                }
            }
        }
        if (!$desc) {
            $result['order'] = 'ASC';
        } else {
            $result['order'] = 'DESC';
        }
    }

    protected function getTextFilterFieldList()
    {
        return $this->getMetadata()->get("entityDefs.{$this->entityType}.collection.textFilterFields", ['name']);
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


        $ignoreTypeList = array_merge(['bool', 'primary'], $this->additionalFilterTypeList);

        $additionalFilters = array();
        foreach ($where as $item) {
            $type = $item['type'];
            if (!in_array($type, $ignoreTypeList)) {
                $part = $this->getWherePart($item);
                if (!empty($part)) {
                    $whereClause[] = $part;
                }
            } else {
                if (in_array($type, $this->additionalFilterTypeList)) {
                    if (!empty($item['value'])) {
                        $methodName = 'apply' . ucfirst($type);

                        if (method_exists($this, $methodName)) {
                            $attribute = null;
                            if (isset($item['field'])) {
                                $attribute = $item['field'];
                            }
                            if (isset($item['attribute'])) {
                                $attribute = $item['attribute'];
                            }
                            if ($attribute) {
                                $this->$methodName($attribute, $item['value'], $result);
                            }
                        }
                    }
                }
            }
        }

        $result['whereClause'] = array_merge($result['whereClause'], $whereClause);
    }

    protected function applyLinkedWith($link, $idsValue, &$result)
    {
        $part = array();

        if (is_array($idsValue) && count($idsValue) == 1) {
            $idsValue = $idsValue[0];
        }

        $seed = $this->getSeed();

        if (!$seed->hasRelation($link)) return;

        $relDefs = $this->getSeed()->getRelations();

        $relationType = $seed->getRelationType($link);

        $defs = $relDefs[$link];
        if ($relationType == 'manyMany') {
            $this->addJoin([$link, $link . 'Filter'], $result);
            $midKeys = $seed->getRelationParam($link, 'midKeys');

            if (!empty($midKeys)) {
                $key = $midKeys[1];
                $part[$link . 'Filter' . 'Middle.' . $key] = $idsValue;
            }
        } else if ($relationType == 'belongsTo') {
            $key = $seed->getRelationParam($link, 'key');
            if (!empty($key)) {
                $part[$key] = $idsValue;
            }
        } else if ($relationType == 'hasOne') {
            $this->addJoin([$link, $link . 'Filter'], $result);
            $part[$link . 'Filter' . '.id'] = $idsValue;
        } else {
            return;
        }

        if (!empty($part)) {
            $result['whereClause'][] = $part;
        }

        $this->setDistinct(true, $result);
    }

    protected function applyIsUserFromTeams($link, $idsValue, &$result)
    {
        if (is_array($idsValue) && count($idsValue) == 1) {
            $idsValue = $idsValue[0];
        }

        $query = $this->getEntityManager()->getQuery();

        $seed = $this->getSeed();

        $relDefs = $seed->getRelations();

        if (!$seed->hasRelation($link)) return;

        $relationType = $seed->getRelationType($link);

        if ($relationType == 'belongsTo') {
            $key = $seed->getRelationParam($link, 'key');

            $aliasName = 'usersTeams' . ucfirst($link);

            $result['customJoin'] .= "
                JOIN team_user AS {$aliasName}Middle ON {$aliasName}Middle.user_id = ".$query->toDb($seed->getEntityType()).".".$query->toDb($key)." AND {$aliasName}Middle.deleted = 0
                JOIN team AS {$aliasName} ON {$aliasName}.deleted = 0 AND {$aliasName}Middle.team_id = {$aliasName}.id
            ";

            $result['whereClause'][] = array(
                $aliasName . 'Middle.teamId' => $idsValue
            );
        } else {
            return;
        }

        $this->setDistinct(true, $result);
    }

    public function applyInCategory($link, $value, &$result)
    {
        $relDefs = $this->getSeed()->getRelations();

        $query = $this->getEntityManager()->getQuery();

        $tableName = $query->toDb($this->getSeed()->getEntityType());

        if (!empty($relDefs[$link])) {
            $defs = $relDefs[$link];

            $foreignEntity = $defs['entity'];
            if (empty($foreignEntity)) {
                return;
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
                    $result['whereClause'][$pathName . '.ascendorId'] = $value;
                }
            } else if ($defs['type'] == 'belongsTo') {
                if (!empty($defs['key'])) {
                    $key = $defs['key'];
                    $result['customJoin'] .= "
                        JOIN " . $query->toDb($pathName) . " AS `{$pathName}` ON {$pathName}.descendor_id = {$tableName}." . $query->toDb($key) . "
                    ";
                    $result['whereClause'][$pathName . '.ascendorId'] = $value;
                }
            }
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

    public function getEmptySelectParams()
    {
        $result = array();
        $this->prepareResult($result);

        return $result;
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

    protected function checkIsPortal()
    {
        return !!$this->getUser()->get('portalId');
    }

    protected function access(&$result)
    {
        if (!$this->checkIsPortal()) {
            if ($this->getAcl()->checkReadOnlyOwn($this->getEntityType())) {
                $this->accessOnlyOwn($result);
            } else {
                if (!$this->getUser()->isAdmin()) {
                    if ($this->getAcl()->checkReadOnlyTeam($this->getEntityType())) {
                        $this->accessOnlyTeam($result);
                    }
                }
            }
        } else {
            if ($this->getAcl()->checkReadOnlyOwn($this->getEntityType())) {
                $this->accessPortalOnlyOwn($result);
            } else {
                if ($this->getAcl()->checkReadOnlyAccount($this->getEntityType())) {
                    $this->accessPortalOnlyAccount($result);
                } else {
                    if ($this->getAcl()->checkReadOnlyContact($this->getEntityType())) {
                        $this->accessPortalOnlyContact($result);
                    }
                }
            }
        }
    }

    protected function accessOnlyOwn(&$result)
    {
        if ($this->hasAssignedUsersField()) {
            $this->setDistinct(true, $result);
            $this->addLeftJoin('assignedUsers', $result);
            $result['whereClause'][] = array(
                'assignedUsers.id' => $this->getUser()->id
            );
            return;
        }

        if ($this->hasAssignedUserField()) {
            $result['whereClause'][] = array(
                'assignedUserId' => $this->getUser()->id
            );
            return;
        }

        if ($this->hasCreatedByField()) {
            $result['whereClause'][] = array(
                'createdById' => $this->getUser()->id
            );
        }
    }

    protected function accessOnlyTeam(&$result)
    {
        if (!$this->hasTeamsField()) {
            return;
        }

        $this->setDistinct(true, $result);
        $this->addLeftJoin(['teams', 'teamsAccess'], $result);

        if ($this->hasAssignedUsersField()) {
            $this->addLeftJoin(['assignedUsers', 'assignedUsersAccess'], $result);
            $result['whereClause'][] = array(
                'OR' => array(
                    'teamsAccess.id' => $this->getUser()->getLinkMultipleIdList('teams'),
                    'assignedUsersAccess.id' => $this->getUser()->id
                )
            );
            return;
        }

        $d = array(
            'teamsAccess.id' => $this->getUser()->getLinkMultipleIdList('teams')
        );
        if ($this->hasAssignedUserField()) {
            $d['assignedUserId'] = $this->getUser()->id;
        } else if ($this->hasCreatedByField()) {
            $d['createdById'] = $this->getUser()->id;
        }
        $result['whereClause'][] = array(
            'OR' => $d
        );
    }

    protected function accessPortalOnlyOwn(&$result)
    {
        if ($this->getSeed()->hasAttribute('createdById')) {
            $result['whereClause'][] = array(
                'createdById' => $this->getUser()->id
            );
        } else {
            $result['whereClause'][] = array(
                'id' => null
            );
        }
    }

    protected function accessPortalOnlyContact(&$result)
    {
        $d = array();

        $contactId = $this->getUser()->get('contactId');

        if ($contactId) {
            if ($this->getSeed()->hasAttribute('contactId')) {
                $d['contactId'] = $contactId;
            }
            if ($this->getSeed()->hasRelation('contacts')) {
                $this->addLeftJoin(['contacts', 'contactsAccess'], $result);
                $this->setDistinct(true, $result);
                $d['contactsAccess.id'] = $contactId;
            }
        }

        if ($this->getSeed()->hasAttribute('createdById')) {
            $d['createdById'] = $this->getUser()->id;
        }

        if ($this->getSeed()->hasAttribute('parentId') && $this->getSeed()->hasRelation('parent')) {
            $contactId = $this->getUser()->get('contactId');
            if ($contactId) {
                $d[] = array(
                    'parentType' => 'Contact',
                    'parentId' => $contactId
                );
            }
        }

        if (!empty($d)) {
            $result['whereClause'][] = array(
                'OR' => $d
            );
        } else {
            $result['whereClause'][] = array(
                'id' => null
            );
        }
    }

    protected function accessPortalOnlyAccount(&$result)
    {
        $d = array();

        $accountIdList = $this->getUser()->getLinkMultipleIdList('accounts');
        $contactId = $this->getUser()->get('contactId');

        if (count($accountIdList)) {
            if ($this->getSeed()->hasAttribute('accountId')) {
                $d['accountId'] = $accountIdList;
            }
            if ($this->getSeed()->hasRelation('accounts')) {
                $this->addLeftJoin(['accounts', 'accountsAccess'], $result);
                $this->setDistinct(true, $result);
                $d['accountsAccess.id'] = $accountIdList;
            }
            if ($this->getSeed()->hasAttribute('parentId') && $this->getSeed()->hasRelation('parent')) {
                $d[] = array(
                    'parentType' => 'Account',
                    'parentId' => $accountIdList
                );
                if ($contactId) {
                    $d[] = array(
                        'parentType' => 'Contact',
                        'parentId' => $contactId
                    );
                }
            }
        }

        if ($contactId) {
            if ($this->getSeed()->hasAttribute('contactId')) {
                $d['contactId'] = $contactId;
            }
            if ($this->getSeed()->hasRelation('contacts')) {
                $this->addLeftJoin(['contacts', 'contactsAccess'], $result);
                $this->setDistinct(true, $result);
                $d['contactsAccess.id'] = $contactId;
            }
        }

        if ($this->getSeed()->hasAttribute('createdById')) {
            $d['createdById'] = $this->getUser()->id;
        }

        if (!empty($d)) {
            $result['whereClause'][] = array(
                'OR' => $d
            );
        } else {
            $result['whereClause'][] = array(
                'id' => null
            );
        }
    }

    protected function hasAssignedUsersField()
    {
        if ($this->getSeed()->hasRelation('assignedUsers') && $this->getSeed()->hasAttribute('assignedUsersIds')) {
            return true;
        }
    }

    protected function hasAssignedUserField()
    {
        if ($this->getSeed()->hasAttribute('assignedUserId')) {
            return true;
        }
    }

    protected function hasCreatedByField()
    {
        if ($this->getSeed()->hasAttribute('createdById')) {
            return true;
        }
    }

    protected function hasTeamsField()
    {
        if ($this->getSeed()->hasRelation('teams') && $this->getSeed()->hasAttribute('teamsIds')) {
            return true;
        }
    }

    public function getAclParams()
    {
        $result = array();
        $this->applyAccess($result);
        return $result;
    }

    public function buildSelectParams(array $params, $withAcl = false, $checkWherePermission = false)
    {
        return $this->getSelectParams($params, $withAcl, $checkWherePermission);
    }

    public function getSelectParams(array $params, $withAcl = false, $checkWherePermission = false)
    {
        $result = array();
        $this->prepareResult($result);

        if (!empty($params['sortBy'])) {
            if (!array_key_exists('asc', $params)) {
                $params['asc'] = true;
            }
            $this->order($params['sortBy'], !$params['asc'], $result);
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
            if ($checkWherePermission) {
                $this->checkWhere($params['where']);
            }
            $this->where($params['where'], $result);
        }

        if (!empty($params['textFilter'])) {
            $this->textFilter($params['textFilter'], $result);
        }

        $this->q($params, $result);

        if ($withAcl) {
            $this->access($result);
        }

        $this->applyAdditional($result);

        return $result;
    }

    protected function checkWhere($where)
    {
        foreach ($where as $w) {
            $attribute = null;
            if (isset($w['field'])) {
                $attribute = $w['field'];
            }
            if (isset($w['attribute'])) {
                $attribute = $w['attribute'];
            }
            if ($attribute) {
                if (isset($w['type']) && $w['type'] === 'linkedWith') {
                    if (in_array($attribute, $this->getAcl()->getScopeForbiddenFieldList($this->getEntityType()))) {
                        throw new Forbidden();
                    }
                } else {
                    if (in_array($attribute, $this->getAcl()->getScopeForbiddenAttributeList($this->getEntityType()))) {
                        throw new Forbidden();
                    }
                }
            }
            if (!empty($w['value']) && is_array($w['value'])) {
                $this->checkWhere($w['value']);
            }
        }
    }

    public function getUserTimeZone()
    {
        if (empty($this->userTimeZone)) {
            $preferences = $this->getEntityManager()->getEntity('Preferences', $this->getUser()->id);
            if ($preferences) {
                $timeZone = $preferences->get('timeZone');
                $this->userTimeZone = $timeZone;
            } else {
                $this->userTimeZone = 'UTC';
            }
        }

        return $this->userTimeZone;
    }

    public function convertDateTimeWhere($item)
    {
        $format = 'Y-m-d H:i:s';

        $value = null;
        $timeZone = 'UTC';

        $attribute = null;
        if (isset($item['field'])) {
            $attribute = $item['field'];
        }
        if (isset($item['attribute'])) {
            $attribute = $item['attribute'];
        }

        if (!$attribute) {
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

        if (empty($value) && in_array($type, array('on', 'before', 'after'))) {
            return null;
        }

        $where = array();
        $where['attribute'] = $attribute;

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

        $attribute = null;
        if (!empty($item['field'])) { // for backward compatibility
            $attribute = $item['field'];
        }
        if (!empty($item['attribute'])) {
            $attribute = $item['attribute'];
        }

        if (!empty($attribute) && !empty($item['type'])) {
            $methodName = 'getWherePart' . ucfirst($attribute) . ucfirst($item['type']);
            if (method_exists($this, $methodName)) {
                $value = null;
                if (!empty($item['value'])) {
                    $value = $item['value'];
                }
                return $this->$methodName($value);
            }
        }


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
                                if (!empty($right) || is_null($right) || $right === '') {
                                    $arr[] = array($left => $right);
                                }
                            }
                        }
                        $part[strtoupper($item['type'])] = $arr;
                    }
                    break;
                case 'like':
                    $part[$attribute . '*'] = $item['value'];
                    break;
                case 'equals':
                case 'on':
                    $part[$attribute . '='] = $item['value'];
                    break;
                case 'startsWith':
                    $part[$attribute . '*'] = $item['value'] . '%';
                    break;
                case 'endsWith':
                    $part[$attribute . '*'] = '%' . $item['value'];
                    break;
                case 'contains':
                    $part[$attribute . '*'] = '%' . $item['value'] . '%';
                    break;
                case 'notEquals':
                case 'notOn':
                    $part[$attribute . '!='] = $item['value'];
                    break;
                case 'greaterThan':
                case 'after':
                    $part[$attribute . '>'] = $item['value'];
                    break;
                case 'lessThan':
                case 'before':
                    $part[$attribute . '<'] = $item['value'];
                    break;
                case 'greaterThanOrEquals':
                    $part[$attribute . '>='] = $item['value'];
                    break;
                case 'lessThanOrEquals':
                    $part[$attribute . '<'] = $item['value'];
                    break;
                case 'in':
                    $part[$attribute . '='] = $item['value'];
                    break;
                case 'notIn':
                    $part[$attribute . '!='] = $item['value'];
                    break;
                case 'isNull':
                    $part[$attribute . '='] = null;
                    break;
                case 'isNotNull':
                case 'ever':
                    $part[$attribute . '!='] = null;
                    break;
                case 'isTrue':
                    $part[$attribute . '='] = true;
                    break;
                case 'isFalse':
                    $part[$attribute . '='] = false;
                    break;
                case 'today':
                    $part[$attribute . '='] = date('Y-m-d');
                    break;
                case 'past':
                    $part[$attribute . '<'] = date('Y-m-d');
                    break;
                case 'future':
                    $part[$attribute . '>='] = date('Y-m-d');
                    break;
                case 'lastSevenDays':
                    $dt1 = new \DateTime();
                    $dt2 = clone $dt1;
                    $dt2->modify('-7 days');
                    $part['AND'] = array(
                        $attribute . '>=' => $dt2->format('Y-m-d'),
                        $attribute . '<=' => $dt1->format('Y-m-d'),
                    );
                    break;
                case 'lastXDays':
                    $dt1 = new \DateTime();
                    $dt2 = clone $dt1;
                    $number = strval(intval($item['value']));

                    $dt2->modify('-'.$number.' days');
                    $part['AND'] = array(
                        $attribute . '>=' => $dt2->format('Y-m-d'),
                        $attribute . '<=' => $dt1->format('Y-m-d'),
                    );
                    break;
                case 'nextXDays':
                    $dt1 = new \DateTime();
                    $dt2 = clone $dt1;
                    $number = strval(intval($item['value']));
                    $dt2->modify('+'.$number.' days');
                    $part['AND'] = array(
                        $attribute . '>=' => $dt1->format('Y-m-d'),
                        $attribute . '<=' => $dt2->format('Y-m-d'),
                    );
                    break;
                case 'currentMonth':
                    $dt = new \DateTime();
                    $part['AND'] = array(
                        $attribute . '>=' => $dt->modify('first day of this month')->format('Y-m-d'),
                        $attribute . '<' => $dt->add(new \DateInterval('P1M'))->format('Y-m-d'),
                    );
                    break;
                case 'lastMonth':
                    $dt = new \DateTime();
                    $part['AND'] = array(
                        $attribute . '>=' => $dt->modify('first day of last month')->format('Y-m-d'),
                        $attribute . '<' => $dt->add(new \DateInterval('P1M'))->format('Y-m-d'),
                    );
                    break;
                case 'currentQuarter':
                    $dt = new \DateTime();
                    $quarter = ceil($dt->format('m') / 3);
                    $dt->modify('first day of January this year');
                    $part['AND'] = array(
                        $attribute . '>=' => $dt->add(new \DateInterval('P'.(($quarter - 1) * 3).'M'))->format('Y-m-d'),
                        $attribute . '<' => $dt->add(new \DateInterval('P3M'))->format('Y-m-d'),
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
                        $attribute . '>=' => $dt->add(new \DateInterval('P'.(($quarter - 1) * 3).'M'))->format('Y-m-d'),
                        $attribute . '<' => $dt->add(new \DateInterval('P3M'))->format('Y-m-d'),
                    );
                    break;
                case 'currentYear':
                    $dt = new \DateTime();
                    $part['AND'] = array(
                        $attribute . '>=' => $dt->modify('first day of January this year')->format('Y-m-d'),
                        $attribute . '<' => $dt->add(new \DateInterval('P1Y'))->format('Y-m-d'),
                    );
                    break;
                case 'lastYear':
                    $dt = new \DateTime();
                    $part['AND'] = array(
                        $attribute . '>=' => $dt->modify('first day of January last year')->format('Y-m-d'),
                        $attribute . '<' => $dt->add(new \DateInterval('P1Y'))->format('Y-m-d'),
                    );
                    break;
                case 'between':
                    if (is_array($item['value'])) {
                        $part['AND'] = array(
                            $attribute . '>=' => $item['value'][0],
                            $attribute . '<=' => $item['value'][1],
                        );
                    }
                    break;
            }
        }

        return $part;
    }

    public function applyOrder($sortBy, $desc, &$result)
    {
        $this->prepareResult($result);
        $this->order($sortBy, $desc, $result);
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

    public function applyAdditional(&$result)
    {

    }

    public function hasJoin($join, &$result)
    {
        if (in_array($join, $result['joins'])) {
            return true;
        }

        foreach ($result['joins'] as $item) {
            if (is_array($item) && count($item) > 1) {
                if ($item[1] == $join) {
                    return true;
                }
            }
        }

        return false;
    }

    public function hasLeftJoin($leftJoin, &$result)
    {
        if (in_array($leftJoin, $result['leftJoins'])) {
            return true;
        }

        foreach ($result['leftJoins'] as $item) {
            if (is_array($item) && count($item) > 1) {
                if ($item[1] == $leftJoin) {
                    return true;
                }
            }
        }

        return false;
    }

    public function addJoin($join, &$result)
    {
        if (empty($result['joins'])) {
            $result['joins'] = [];
        }

        $alias = $join;
        if (is_array($join)) {
            if (count($join) > 1) {
                $alias = $join[1];
            } else {
                $alias = $join[0];
            }
        }
        foreach ($result['joins'] as $j) {
            $a = $j;
            if (is_array($j)) {
                if (count($j) > 1) {
                    $a = $j[1];
                } else {
                    $a = $j[0];
                }
            }
            if ($a === $alias) {
                return;
            }
        }

        $result['joins'][] = $join;
    }

    public function addLeftJoin($leftJoin, &$result)
    {
        if (empty($result['leftJoins'])) {
            $result['leftJoins'] = [];
        }

        $alias = $leftJoin;
        if (is_array($leftJoin)) {
            if (count($leftJoin) > 1) {
                $alias = $leftJoin[1];
            } else {
                $alias = $leftJoin[0];
            }
        }
        foreach ($result['leftJoins'] as $j) {
            $a = $j;
            if (is_array($j)) {
                if (count($j) > 1) {
                    $a = $j[1];
                } else {
                    $a = $j[0];
                }
            }
            if ($a === $alias) {
                return;
            }
        }

        $result['leftJoins'][] = $leftJoin;
    }

    public function setJoinCondition($join, $condition, &$result)
    {
        $result['joinConditions'][$join] = $condition;
    }

    public function setDistinct($distinct, &$result)
    {
        $result['distinct'] = (bool) $distinct;
    }

    public function addAndWhere($whereClause, &$result)
    {
        $result['whereClause'][] = $whereClause;
    }

    public function addOrWhere($whereClause, &$result)
    {
        $result['whereClause'][] = array(
            'OR' => $whereClause
        );
    }

    protected function textFilter($textFilter, &$result)
    {
        $fieldDefs = $this->getSeed()->getAttributes();
        $fieldList = $this->getTextFilterFieldList();
        $d = array();

        foreach ($fieldList as $field) {
            $expression = $textFilter . '%';
            if (
                strlen($textFilter) >= self::MIN_LENGTH_FOR_CONTENT_SEARCH
                &&
                (
                    !empty($fieldDefs[$field]['type']) && $fieldDefs[$field]['type'] == 'text'
                    ||
                    $this->getConfig()->get('textFilterUseContainsForVarchar')
                )
            ) {
                $expression = '%' . $textFilter . '%';
            } else {
                $expression = $textFilter . '%';
            }
            $d[$field . '*'] = $expression;
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
        if (!$this->checkIsPortal()) {
            if ($this->hasAssignedUserField()) {
                $result['whereClause'][] = array(
                    'assignedUserId' => $this->getUser()->id
                );
            } else {
                $result['whereClause'][] = array(
                    'createdById' => $this->getUser()->id
                );
            }
        } else {
            $result['whereClause'][] = array(
                'createdById' => $this->getUser()->id
            );
        }
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

