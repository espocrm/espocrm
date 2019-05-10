<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: https://www.espocrm.com
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
use \Espo\Core\InjectableFactory;
use \Espo\Core\Utils\FieldManagerUtil;
use \Espo\ORM\EntityManager;

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

    protected $additionalFilterTypeList = ['inCategory', 'isUserFromTeams'];

    protected $aclAttributeList = ['assignedUserId', 'createdById'];

    protected $aclPortalAttributeList = ['assignedUserId', 'createdById', 'contactId', 'accountId'];

    protected $textFilterUseContainsAttributeList = [];

    protected $selectAttributesDependancyMap = [];

    const MIN_LENGTH_FOR_CONTENT_SEARCH = 4;

    const MIN_LENGTH_FOR_FULL_TEXT_SEARCH = 4;

    protected $fullTextSearchDataCacheHash = [];

    public function __construct(EntityManager $entityManager, \Espo\Entities\User $user, Acl $acl, AclManager $aclManager, Metadata $metadata, Config $config, FieldManagerUtil $fieldManagerUtil, InjectableFactory $injectableFactory)
    {
        $this->entityManager = $entityManager;
        $this->user = $user;
        $this->acl = $acl;
        $this->aclManager = $aclManager;
        $this->metadata = $metadata;
        $this->config = $config;
        $this->fieldManagerUtil = $fieldManagerUtil;
        $this->injectableFactory = $injectableFactory;
    }

    protected function getEntityManager() : EntityManager
    {
        return $this->entityManager;
    }

    protected function getMetadata() : Metadata
    {
        return $this->metadata;
    }

    protected function getUser() : \Espo\Entities\User
    {
        return $this->user;
    }

    protected function getAcl() : Acl
    {
        return $this->acl;
    }

    protected function getConfig() : Config
    {
        return $this->config;
    }

    protected function getAclManager() : AclManager
    {
        return $this->aclManager;
    }

    protected function getInjectableFactory() : InjectableFactory
    {
        return $this->injectableFactory;
    }

    protected function getFieldManagerUtil() : FieldManagerUtil
    {
        return $this->fieldManagerUtil;
    }

    public function setEntityType(string $entityType)
    {
        $this->entityType = $entityType;
    }

    protected function getEntityType() : string
    {
        return $this->entityType;
    }

    protected function limit(?int $offset = null, ?int $maxSize = null, array &$result)
    {
        if (!is_null($offset)) {
            $result['offset'] = $offset;
        }
        if (!is_null($maxSize)) {
            $result['limit'] = $maxSize;
        }
    }

    protected function order(string $sortBy, $desc, array &$result)
    {
        if (is_string($desc)) {
            $desc = $desc === strtolower('desc');
        }

        if (!empty($sortBy)) {
            $result['orderBy'] = $sortBy;
            $type = $this->getMetadata()->get(['entityDefs', $this->getEntityType(), 'fields', $sortBy, 'type']);
            if (in_array($type, ['link', 'file', 'image'])) {
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
                        asort($list);
                    }
                    if ($desc) {
                        $list = array_reverse($list);
                    }
                    foreach ($list as $i => $listItem) {
                        $list[$i] = str_replace(',', '_COMMA_', $listItem);
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

    protected function getTextFilterFieldList() : array
    {
        return $this->getMetadata()->get(['entityDefs', $this->entityType, 'collection', 'textFilterFields'], ['name']);
    }

    protected function getSeed() : \Espo\ORM\Entity
    {
        if (empty($this->seed)) {
            $this->seed = $this->entityManager->getEntity($this->entityType);
        }
        return $this->seed;
    }

    public function applyWhere(array $where, array &$result)
    {
        $this->prepareResult($result);
        $this->where($where, $result);
    }

    protected function where(array $where, array &$result)
    {
        $this->prepareResult($result);

        foreach ($where as $item) {
            if (!isset($item['type'])) continue;

            if ($item['type'] == 'bool' && !empty($item['value']) && is_array($item['value'])) {
                foreach ($item['value'] as $filter) {
                    $p = $this->getBoolFilterWhere($filter);
                    if (!empty($p)) {
                        $where[] = $p;
                    }
                    $this->applyBoolFilter($filter, $result);
                }
            } else if ($item['type'] == 'textFilter') {
                if (isset($item['value']) || $item['value'] !== '') {
                    $this->textFilter($item['value'], $result);
                }
            } else if ($item['type'] == 'primary' && !empty($item['value'])) {
                $this->applyPrimaryFilter($item['value'], $result);
            }
        }

        $whereClause = $this->convertWhere($where, false, $result);

        $result['whereClause'] = array_merge($result['whereClause'], $whereClause);

        $this->applyLeftJoinsFromWhere($where, $result);
    }

    public function convertWhere(array $where, bool $ignoreAdditionaFilterTypes = false, array &$result = []) : array
    {
        $whereClause = [];

        $ignoreTypeList = array_merge(['bool', 'primary'], $this->additionalFilterTypeList);

        foreach ($where as $item) {
            if (!isset($item['type'])) continue;

            $type = $item['type'];
            if (!in_array($type, $ignoreTypeList)) {
                $part = $this->getWherePart($item, $result);
                if (!empty($part)) {
                    $whereClause[] = $part;
                }
            } else {
                if (!$ignoreAdditionaFilterTypes && in_array($type, $this->additionalFilterTypeList)) {
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

        return $whereClause;
    }

    protected function applyLinkedWith(string $link, $idsValue, array &$result)
    {
        $part = [];

        if (is_array($idsValue) && count($idsValue) == 1) {
            $idsValue = $idsValue[0];
        }

        $seed = $this->getSeed();

        if (!$seed->hasRelation($link)) return;

        $relDefs = $this->getSeed()->getRelations();

        $relationType = $seed->getRelationType($link);

        $defs = $relDefs[$link];
        if ($relationType == 'manyMany') {
            $this->addLeftJoin([$link, $link . 'Filter'], $result);
            $midKeys = $seed->getRelationParam($link, 'midKeys');

            if (!empty($midKeys)) {
                $key = $midKeys[1];
                $part[$link . 'Filter' . 'Middle.' . $key] = $idsValue;
            }
        } else if ($relationType == 'hasMany') {
            $alias = $link . 'Filter';
            $this->addLeftJoin([$link, $alias], $result);

            $part[$alias . '.id'] = $idsValue;
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

    protected function applyIsUserFromTeams(string $link, $idsValue, array &$result)
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

            $result['whereClause'][] = [
                $aliasName . 'Middle.teamId' => $idsValue
            ];
        } else {
            return;
        }

        $this->setDistinct(true, $result);
    }

    public function applyInCategory(string $link, $value, array &$result)
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

    protected function q(array $params, array &$result)
    {
        if (isset($params['q']) && $params['q'] !== '') {
            $textFilter = $params['q'];
            $this->textFilter($textFilter, $result);
        }
    }

    public function manageAccess(array &$result)
    {
        $this->prepareResult($result);
        $this->applyAccess($result);
    }

    public function manageTextFilter(string $textFilter, array &$result)
    {
        $this->prepareResult($result);
        $this->q(['q' => $textFilter], $result);
    }

    public function getEmptySelectParams() : array
    {
        $result = [];
        $this->prepareResult($result);

        return $result;
    }

    protected function prepareResult(array &$result)
    {
        if (empty($result)) {
            $result = [];
        }
        if (empty($result['joins'])) {
            $result['joins'] = [];
        }
        if (empty($result['leftJoins'])) {
            $result['leftJoins'] = [];
        }
        if (empty($result['whereClause'])) {
            $result['whereClause'] = [];
        }
        if (empty($result['customJoin'])) {
            $result['customJoin'] = '';
        }
        if (empty($result['additionalSelectColumns'])) {
            $result['additionalSelectColumns'] = [];
        }
        if (empty($result['joinConditions'])) {
            $result['joinConditions'] = [];
        }
    }

    protected function checkIsPortal() : bool
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
                    } else if ($this->getAcl()->checkReadNo($this->getEntityType())) {
                        $this->accessNo($result);
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
                    } else if ($this->getAcl()->checkReadNo($this->getEntityType())) {
                        $this->accessNo($result);
                    }
                }
            }
        }
    }

    protected function accessNo(array &$result)
    {
        $result['whereClause'][] = [
            'id' => null
        ];
    }

    protected function accessOnlyOwn(&$result)
    {
        if ($this->hasAssignedUsersField()) {
            $this->setDistinct(true, $result);
            $this->addLeftJoin(['assignedUsers', 'assignedUsersAccess'], $result);
            $result['whereClause'][] = [
                'assignedUsersAccess.id' => $this->getUser()->id
            ];
            return;
        }

        if ($this->hasAssignedUserField()) {
            $result['whereClause'][] = [
                'assignedUserId' => $this->getUser()->id
            ];
            return;
        }

        if ($this->hasCreatedByField()) {
            $result['whereClause'][] = [
                'createdById' => $this->getUser()->id
            ];
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
            $result['whereClause'][] = [
                'OR' => [
                    'teamsAccess.id' => $this->getUser()->getLinkMultipleIdList('teams'),
                    'assignedUsersAccess.id' => $this->getUser()->id
                ]
            ];
            return;
        }

        $d = [
            'teamsAccess.id' => $this->getUser()->getLinkMultipleIdList('teams')
        ];
        if ($this->hasAssignedUserField()) {
            $d['assignedUserId'] = $this->getUser()->id;
        } else if ($this->hasCreatedByField()) {
            $d['createdById'] = $this->getUser()->id;
        }
        $result['whereClause'][] = [
            'OR' => $d
        ];
    }

    protected function accessPortalOnlyOwn(&$result)
    {
        if ($this->getSeed()->hasAttribute('createdById')) {
            $result['whereClause'][] = [
                'createdById' => $this->getUser()->id
            ];
        } else {
            $result['whereClause'][] = [
                'id' => null
            ];
        }
    }

    protected function accessPortalOnlyContact(&$result)
    {
        $d = [];

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
                $d[] = [
                    'parentType' => 'Contact',
                    'parentId' => $contactId
                ];
            }
        }

        if (!empty($d)) {
            $result['whereClause'][] = [
                'OR' => $d
            ];
        } else {
            $result['whereClause'][] = [
                'id' => null
            ];
        }
    }

    protected function accessPortalOnlyAccount(&$result)
    {
        $d = [];

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
                $d[] = [
                    'parentType' => 'Account',
                    'parentId' => $accountIdList
                ];
                if ($contactId) {
                    $d[] = [
                        'parentType' => 'Contact',
                        'parentId' => $contactId
                    ];
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
            $result['whereClause'][] = [
                'OR' => $d
            ];
        } else {
            $result['whereClause'][] = [
                'id' => null
            ];
        }
    }

    protected function hasAssignedUsersField() : bool
    {
        if ($this->getSeed()->hasRelation('assignedUsers') && $this->getSeed()->hasAttribute('assignedUsersIds')) {
            return true;
        }
        return false;
    }

    protected function hasAssignedUserField() : bool
    {
        if ($this->getSeed()->hasAttribute('assignedUserId')) {
            return true;
        }
        return false;
    }

    protected function hasCreatedByField() : bool
    {
        if ($this->getSeed()->hasAttribute('createdById')) {
            return true;
        }
        return false;
    }

    protected function hasTeamsField() : bool
    {
        if ($this->getSeed()->hasRelation('teams') && $this->getSeed()->hasAttribute('teamsIds')) {
            return true;
        }
        return false;
    }

    public function getAclParams() : array
    {
        $result = [];
        $this->applyAccess($result);
        return $result;
    }

    public function buildSelectParams(array $params, bool $withAcl = false, bool $checkWherePermission = false, bool $forbidComplexExpressions = false) : array
    {
        return $this->getSelectParams($params, $withAcl, $checkWherePermission, $forbidComplexExpressions);
    }

    public function getSelectParams(array $params, bool $withAcl = false, bool $checkWherePermission = false, bool $forbidComplexExpressions = false) : array
    {
        $result = [];
        $this->prepareResult($result);

        if (!empty($params['orderBy'])) {
            $isDesc = false;
            if (isset($params['order'])) {
                $isDesc = $params['order'] === 'desc';
            }
            $orderBy = $params['orderBy'];

            if ($forbidComplexExpressions) {
                if (!is_string($orderBy) || strpos($orderBy, '.') !== false || strpos($orderBy, ':') !== false) {
                    throw new Forbidden("Complex expressions are forbidden in orderBy.");
                }
            }

            $this->order($orderBy, $isDesc, $result);
        } else if (!empty($params['sortBy'])) {
            if (isset($params['order'])) {
                $isDesc = $params['order'] === 'desc';
            } else if (isset($params['asc'])) {
                $isDesc = $params['asc'] !== true;
            }

            $orderBy = $params['sortBy'];

            if ($forbidComplexExpressions) {
                if (!is_string($orderBy) || strpos($orderBy, '.') !== false || strpos($orderBy, ':') !== false) {
                    throw new Forbidden("Complex expressions are forbidden in orderBy.");
                }
            }

            $this->order($orderBy, $isDesc, $result);
        } else if (!empty($params['order'])) {
            $orderBy = $this->getMetadata()->get(['entityDefs', $this->getEntityType(), 'collection', 'orderBy']);
            $isDesc = $params['order'] === 'desc';
            $this->order($orderBy, $isDesc, $result);
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

        if (!empty($params['filterList']) && is_array($params['filterList'])) {
            foreach ($params['filterList'] as $filterName) {
                $this->applyFilter($filterName, $result);
            }
        }

        if (!empty($params['where']) && is_array($params['where'])) {
            if ($checkWherePermission) {
                $this->checkWhere($params['where'], $checkWherePermission, $forbidComplexExpressions);
            }
            $this->where($params['where'], $result);
        }

        if (isset($params['textFilter']) && $params['textFilter'] !== '') {
            $this->textFilter($params['textFilter'], $result);
        }

        $this->q($params, $result);

        if ($withAcl) {
            $this->access($result);
        }

        $this->applyAdditional($params, $result);

        return $result;
    }

    public function checkWhere(array $where, bool $checkWherePermission = true, bool $forbidComplexExpressions = false)
    {
        foreach ($where as $w) {
            $attribute = null;
            if (isset($w['field'])) {
                $attribute = $w['field'];
            }
            if (isset($w['attribute'])) {
                $attribute = $w['attribute'];
            }

            $type = null;
            if (isset($w['type'])) {
                $type = $w['type'];
            }

            if ($forbidComplexExpressions) {
                if ($type && in_array($type, ['subQueryIn', 'subQueryNotIn', 'not'])) {
                    throw new Forbidden("SelectManager::checkWhere: Sub-queries are forbidden.");
                }
            }

            if ($attribute && $forbidComplexExpressions) {
                if (strpos($attribute, '.') !== false || strpos($attribute, ':')) {
                    throw new Forbidden("SelectManager::checkWhere: Complex expressions are forbidden.");
                }
            }

            if ($attribute && $checkWherePermission) {
                $argumentList = \Espo\ORM\DB\Query\Base::getAllAttributesFromComplexExpression($attribute);
                foreach ($argumentList as $argument) {
                    $this->checkWhereArgument($argument, $type);
                }
            }

            if (!empty($w['value']) && is_array($w['value'])) {
                $this->checkWhere($w['value'], $checkWherePermission, $forbidComplexExpressions);
            }
        }
    }

    protected function checkWhereArgument($attribute, $type)
    {
        $entityType = $this->getEntityType();

        if (strpos($attribute, '.')) {
            list($link, $attribute) = explode('.', $attribute);
            if (!$this->getSeed()->hasRelation($link)) {
                // TODO allow alias
                throw new Forbidden("SelectManager::checkWhere: Unknown relation '{$link}' in where.");
            }
            $entityType = $this->getSeed($this->getEntityType())->getRelationParam($link, 'entity');
            if (!$entityType) {
                throw new Forbidden("SelectManager::checkWhere: Bad relation.");
            }
            if (!$this->getAcl()->checkScope($entityType)) {
                throw new Forbidden();
            }
        }

        if ($type && in_array($type, ['isLinked', 'isNotLinked', 'linkedWith', 'notLinkedWith', 'isUserFromTeams'])) {
            if (in_array($attribute, $this->getAcl()->getScopeForbiddenFieldList($entityType))) {
                throw new Forbidden();
            }
            if (
                $this->getSeed()->hasRelation($attribute)
                &&
                in_array($attribute, $this->getAcl()->getScopeForbiddenLinkList($entityType))
            ) {
                throw new Forbidden();
            }
        } else {
            if (in_array($attribute, $this->getAcl()->getScopeForbiddenAttributeList($entityType))) {
                throw new Forbidden();
            }
        }
    }

    public function getUserTimeZone() : string
    {
        if (empty($this->userTimeZone)) {
            $preferences = $this->getEntityManager()->getEntity('Preferences', $this->getUser()->id);
            if ($preferences) {
                $timeZone = $preferences->get('timeZone');
                $this->userTimeZone = $timeZone;
            } else {
                $this->userTimeZone = 'UTC';
            }

            if (!$this->userTimeZone) {
                $this->userTimeZone = 'UTC';
            }
        }

        return $this->userTimeZone;
    }

    public function transformDateTimeWhereItem(array $item) : ?array
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

        if (empty($value) && in_array($type, ['on', 'before', 'after'])) {
            return null;
        }

        $where = [];
        $where['attribute'] = $attribute;

        $dt = new \DateTime('now', new \DateTimeZone($timeZone));

        switch ($type) {
            case 'today':
                $where['type'] = 'between';
                $dt->setTime(0, 0, 0);
                $dtTo = clone $dt;
                $dtTo->modify('+1 day -1 second');
                $dt->setTimezone(new \DateTimeZone('UTC'));
                $dtTo->setTimezone(new \DateTimeZone('UTC'));
                $from = $dt->format($format);
                $to = $dtTo->format($format);
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
            case 'olderThanXDays':
                $where['type'] = 'before';
                $number = strval(intval($item['value']));
                $dt->modify('-'.$number.' day');
                $dt->setTime(0, 0, 0);
                $dt->setTimezone(new \DateTimeZone('UTC'));
                $where['value'] = $dt->format($format);
                break;
            case 'afterXDays':
                $where['type'] = 'after';
                $number = strval(intval($item['value']));
                $dt->modify('+'.$number.' day');
                $dt->setTime(0, 0, 0);
                $dt->setTimezone(new \DateTimeZone('UTC'));
                $where['value'] = $dt->format($format);
                break;
            case 'on':
                $where['type'] = 'between';
                $dt = new \DateTime($value, new \DateTimeZone($timeZone));
                $dtTo = clone $dt;
                if (strlen($value) <= 10)
                    $dtTo->modify('+1 day -1 second');
                $dt->setTimezone(new \DateTimeZone('UTC'));
                $dtTo->setTimezone(new \DateTimeZone('UTC'));
                $from = $dt->format($format);
                $to = $dtTo->format($format);
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
                if (strlen($value) <= 10)
                    $dt->modify('+1 day -1 second');

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
                    if (strlen($value[1]) <= 10)
                        $dt->modify('+1 day -1 second');
                    $to = $dt->format($format);

                    $where['value'] = [$from, $to];
                }
               break;

            case 'currentMonth':
            case 'lastMonth':
            case 'nextMonth':
                $where['type'] = 'between';
                $dtFrom = new \DateTime('now', new \DateTimeZone($timeZone));
                $dtFrom = $dt->modify('first day of this month')->setTime(0, 0, 0);

                if ($type == 'lastMonth') {
                    $dtFrom->modify('-1 month');
                } else if ($type == 'nextMonth') {
                    $dtFrom->modify('+1 month');
                }

                $dtTo = clone $dtFrom;
                $dtTo->modify('+1 month');

                $dtFrom->setTimezone(new \DateTimeZone('UTC'));
                $dtTo->setTimezone(new \DateTimeZone('UTC'));

                $where['value'] = [$dtFrom->format($format), $dtTo->format($format)];
                break;

            case 'currentQuarter':
            case 'lastQuarter':
                $where['type'] = 'between';
                $dt = new \DateTime('now', new \DateTimeZone($timeZone));
                $quarter = ceil($dt->format('m') / 3);

                $dtFrom = clone $dt;
                $dtFrom->modify('first day of January this year')->setTime(0, 0, 0);

                if ($type === 'lastQuarter') {
                    $quarter--;
                    if ($quarter == 0) {
                        $quarter = 4;
                        $dtFrom->modify('-1 year');
                    }
                }

                $dtFrom->add(new \DateInterval('P'.(($quarter - 1) * 3).'M'));
                $dtTo = clone $dtFrom;
                $dtTo->add(new \DateInterval('P3M'));
                $dtFrom->setTimezone(new \DateTimeZone('UTC'));
                $dtTo->setTimezone(new \DateTimeZone('UTC'));
                $where['value'] = [
                    $dtFrom->format($format),
                    $dtTo->format($format)
                ];
                break;

            case 'currentYear':
            case 'lastYear':
                $where['type'] = 'between';
                $dtFrom = new \DateTime('now', new \DateTimeZone($timeZone));
                $dtFrom->modify('first day of January this year')->setTime(0, 0, 0);
                if ($type == 'lastYear') {
                    $dtFrom->modify('-1 year');
                }
                $dtTo = clone $dtFrom;
                $dtTo = $dtTo->modify('+1 year');
                $dtFrom->setTimezone(new \DateTimeZone('UTC'));
                $dtTo->setTimezone(new \DateTimeZone('UTC'));
                $where['value'] = [
                    $dtFrom->format($format),
                    $dtTo->format($format)
                ];
                break;

            case 'currentFiscalYear':
            case 'lastFiscalYear':
                $where['type'] = 'between';
                $dtToday = new \DateTime('now', new \DateTimeZone($timeZone));
                $dt = clone $dtToday;
                $fiscalYearShift = $this->getConfig()->get('fiscalYearShift', 0);
                $dt->modify('first day of January this year')->modify('+' . $fiscalYearShift . ' months')->setTime(0, 0, 0);
                if (intval($dtToday->format('m')) < $fiscalYearShift + 1) {
                    $dt->modify('-1 year');
                }
                if ($type === 'lastFiscalYear') {
                    $dt->modify('-1 year');
                }
                $dtFrom = clone $dt;
                $dtTo = clone $dt;
                $dtTo = $dtTo->modify('+1 year');
                $dtFrom->setTimezone(new \DateTimeZone('UTC'));
                $dtTo->setTimezone(new \DateTimeZone('UTC'));
                $where['value'] = [
                    $dtFrom->format($format),
                    $dtTo->format($format)
                ];
                break;

            case 'currentFiscalQuarter':
            case 'lastFiscalQuarter':
                $where['type'] = 'between';
                $dtToday = new \DateTime('now', new \DateTimeZone($timeZone));
                $dt = clone $dtToday;
                $fiscalYearShift = $this->getConfig()->get('fiscalYearShift', 0);
                $dt->modify('first day of January this year')->modify('+' . $fiscalYearShift . ' months')->setTime(0, 0, 0);
                $month = intval($dtToday->format('m'));
                $quarterShift = floor(($month - $fiscalYearShift - 1) / 3);
                if ($quarterShift) {
                    if ($quarterShift >= 0) {
                        $dt->add(new \DateInterval('P'.($quarterShift * 3).'M'));
                    } else {
                        $quarterShift *= -1;
                        $dt->sub(new \DateInterval('P'.($quarterShift * 3).'M'));
                    }
                }
                if ($type === 'lastFiscalQuarter') {
                    $dt->modify('-3 months');
                }
                $dtFrom = clone $dt;
                $dtTo = clone $dt;
                $dtTo = $dtTo->modify('+3 months');
                $dtFrom->setTimezone(new \DateTimeZone('UTC'));
                $dtTo->setTimezone(new \DateTimeZone('UTC'));
                $where['value'] = [
                    $dtFrom->format($format),
                    $dtTo->format($format)
                ];
                break;

            default:
                $where['type'] = $type;
        }

        $where['originalType'] = $type;

        return $where;
    }

    public function convertDateTimeWhere(array $item) : ?array
    {
        $where = $this->transformDateTimeWhereItem($item);
        $result = $this->getWherePart($where);
        return $result;
    }

    protected function getWherePart($item, array &$result = [])
    {
        $part = [];

        $attribute = null;
        if (!empty($item['field'])) { // for backward compatibility
            $attribute = $item['field'];
        }
        if (!empty($item['attribute'])) {
            $attribute = $item['attribute'];
        }

        if (!is_null($attribute) && !is_string($attribute)) {
            throw new Error('Bad attribute in where statement');
        }

        if (!empty($attribute) && !empty($item['type'])) {
            $methodName = 'getWherePart' . ucfirst($attribute) . ucfirst($item['type']);
            if (method_exists($this, $methodName)) {
                $value = null;
                if (array_key_exists('value', $item)) {
                    $value = $item['value'];
                }
                return $this->$methodName($value, $result);
            }
        }

        if (!empty($item['dateTime'])) {
            return $this->convertDateTimeWhere($item);
        }

        if (!array_key_exists('value', $item)) {
            $item['value'] = null;
        }
        $value = $item['value'];

        $timeZone = null;
        if (isset($item['timeZone'])) {
            $timeZone = $item['timeZone'];
        }

        if (!empty($item['type'])) {
            $type = $item['type'];

            switch ($type) {
                case 'or':
                case 'and':
                    if (!is_array($value)) break;

                    $sqWhereClause = [];
                    foreach ($value as $sqWhereItem) {
                        $sqWherePart = $this->getWherePart($sqWhereItem, $result);
                        foreach ($sqWherePart as $left => $right) {
                            if (!empty($right) || is_null($right) || $right === '' || $right === 0 || $right === false) {
                                $sqWhereClause[] = [$left => $right];
                            }
                        }
                    }
                    $part[strtoupper($type)] = $sqWhereClause;

                    break;

                case 'not':
                case 'subQueryNotIn':
                case 'subQueryIn':
                    if (!is_array($value)) break;

                    $sqWhereClause = [];
                    $sqResult = $this->getEmptySelectParams();
                    foreach ($value as $sqWhereItem) {
                        $sqWherePart = $this->getWherePart($sqWhereItem, $sqResult);
                        foreach ($sqWherePart as $left => $right) {
                            if (!empty($right) || is_null($right) || $right === '' || $right === 0 || $right === false) {
                                $sqWhereClause[] = [$left => $right];
                            }
                        }
                    }

                    $this->applyLeftJoinsFromWhere($value, $sqResult);
                    $key = $type === 'subQueryIn' ? 'id=s' : 'id!=s';
                    $part[$key] = [
                        'selectParams' =>  [
                            'select' => ['id'],
                            'whereClause' => $sqWhereClause,
                            'leftJoins' => $sqResult['leftJoins'] ?? [],
                            'joins' => $sqResult['joins'] ?? [],
                        ]
                    ];

                    break;

                case 'expression':
                    $key = $attribute;
                    if (substr($key, -1) !== ':') $key .= ':';
                    $part[$key] = null;
                    break;

                case 'like':
                    $part[$attribute . '*'] = $value;
                    break;

                case 'notLike':
                    $part[$attribute . '!*'] = $value;
                    break;

                case 'equals':
                case 'on':
                    $part[$attribute . '='] = $value;
                    break;

                case 'startsWith':
                    $part[$attribute . '*'] = $value . '%';
                    break;

                case 'endsWith':
                    $part[$attribute . '*'] = '%' . $value;
                    break;

                case 'contains':
                    $part[$attribute . '*'] = '%' . $value . '%';
                    break;

                case 'notContains':
                    $part[$attribute . '!*'] = '%' . $value . '%';
                    break;

                case 'notEquals':
                case 'notOn':
                    $part[$attribute . '!='] = $value;
                    break;

                case 'greaterThan':
                case 'after':
                    $part[$attribute . '>'] = $value;
                    break;

                case 'lessThan':
                case 'before':
                    $part[$attribute . '<'] = $value;
                    break;

                case 'greaterThanOrEquals':
                    $part[$attribute . '>='] = $value;
                    break;

                case 'lessThanOrEquals':
                    $part[$attribute . '<='] = $value;
                    break;

                case 'in':
                    $part[$attribute . '='] = $value;
                    break;

                case 'notIn':
                    $part[$attribute . '!='] = $value;
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
                    $part['AND'] = [
                        $attribute . '>=' => $dt2->format('Y-m-d'),
                        $attribute . '<=' => $dt1->format('Y-m-d'),
                    ];
                    break;

                case 'lastXDays':
                    $dt1 = new \DateTime();
                    $dt2 = clone $dt1;
                    $number = strval(intval($value));

                    $dt2->modify('-'.$number.' days');
                    $part['AND'] = [
                        $attribute . '>=' => $dt2->format('Y-m-d'),
                        $attribute . '<=' => $dt1->format('Y-m-d'),
                    ];
                    break;

                case 'nextXDays':
                    $dt1 = new \DateTime();
                    $dt2 = clone $dt1;
                    $number = strval(intval($value));
                    $dt2->modify('+'.$number.' days');
                    $part['AND'] = [
                        $attribute . '>=' => $dt1->format('Y-m-d'),
                        $attribute . '<=' => $dt2->format('Y-m-d'),
                    ];
                    break;

                case 'olderThanXDays':
                    $dt1 = new \DateTime();
                    $number = strval(intval($value));
                    $dt1->modify('-'.$number.' days');
                    $part[$attribute . '<'] = $dt1->format('Y-m-d');
                    break;

                case 'afterXDays':
                    $dt1 = new \DateTime();
                    $number = strval(intval($value));
                    $dt1->modify('+'.$number.' days');
                    $part[$attribute . '>'] = $dt1->format('Y-m-d');
                    break;

                case 'currentMonth':
                    $dt = new \DateTime();
                    $part['AND'] = [
                        $attribute . '>=' => $dt->modify('first day of this month')->format('Y-m-d'),
                        $attribute . '<' => $dt->add(new \DateInterval('P1M'))->format('Y-m-d'),
                    ];
                    break;

                case 'lastMonth':
                    $dt = new \DateTime();
                    $part['AND'] = [
                        $attribute . '>=' => $dt->modify('first day of last month')->format('Y-m-d'),
                        $attribute . '<' => $dt->add(new \DateInterval('P1M'))->format('Y-m-d'),
                    ];
                    break;

                case 'nextMonth':
                    $dt = new \DateTime();
                    $part['AND'] = [
                        $attribute . '>=' => $dt->modify('first day of next month')->format('Y-m-d'),
                        $attribute . '<' => $dt->add(new \DateInterval('P1M'))->format('Y-m-d'),
                    ];
                    break;

                case 'currentQuarter':
                    $dt = new \DateTime();
                    $quarter = ceil($dt->format('m') / 3);
                    $dt->modify('first day of January this year');
                    $part['AND'] = [
                        $attribute . '>=' => $dt->add(new \DateInterval('P'.(($quarter - 1) * 3).'M'))->format('Y-m-d'),
                        $attribute . '<' => $dt->add(new \DateInterval('P3M'))->format('Y-m-d'),
                    ];
                    break;

                case 'lastQuarter':
                    $dt = new \DateTime();
                    $quarter = ceil($dt->format('m') / 3);
                    $dt->modify('first day of January this year');
                    $quarter--;
                    if ($quarter == 0) {
                        $quarter = 4;
                        $dt->modify('-1 year');
                    }
                    $part['AND'] = [
                        $attribute . '>=' => $dt->add(new \DateInterval('P'.(($quarter - 1) * 3).'M'))->format('Y-m-d'),
                        $attribute . '<' => $dt->add(new \DateInterval('P3M'))->format('Y-m-d'),
                    ];
                    break;

                case 'currentYear':
                    $dt = new \DateTime();
                    $part['AND'] = [
                        $attribute . '>=' => $dt->modify('first day of January this year')->format('Y-m-d'),
                        $attribute . '<' => $dt->add(new \DateInterval('P1Y'))->format('Y-m-d'),
                    ];
                    break;

                case 'lastYear':
                    $dt = new \DateTime();
                    $part['AND'] = [
                        $attribute . '>=' => $dt->modify('first day of January last year')->format('Y-m-d'),
                        $attribute . '<' => $dt->add(new \DateInterval('P1Y'))->format('Y-m-d'),
                    ];
                    break;

                case 'currentFiscalYear':
                case 'lastFiscalYear':
                    $dtToday = new \DateTime();
                    $dt = new \DateTime();
                    $fiscalYearShift = $this->getConfig()->get('fiscalYearShift', 0);
                    $dt->modify('first day of January this year')->modify('+' . $fiscalYearShift . ' months');
                    if (intval($dtToday->format('m')) < $fiscalYearShift + 1) {
                        $dt->modify('-1 year');
                    }
                    if ($type === 'lastFiscalYear') {
                        $dt->modify('-1 year');
                    }
                    $part['AND'] = [
                        $attribute . '>=' => $dt->format('Y-m-d'),
                        $attribute . '<' => $dt->add(new \DateInterval('P1Y'))->format('Y-m-d')
                    ];
                    break;

                case 'currentFiscalQuarter':
                case 'lastFiscalQuarter':
                    $dtToday = new \DateTime();
                    $dt = new \DateTime();
                    $fiscalYearShift = $this->getConfig()->get('fiscalYearShift', 0);
                    $dt->modify('first day of January this year')->modify('+' . $fiscalYearShift . ' months');
                    $month = intval($dtToday->format('m'));
                    $quarterShift = floor(($month - $fiscalYearShift - 1) / 3);
                    if ($quarterShift) {
                        if ($quarterShift >= 0) {
                            $dt->add(new \DateInterval('P'.($quarterShift * 3).'M'));
                        } else {
                            $quarterShift *= -1;
                            $dt->sub(new \DateInterval('P'.($quarterShift * 3).'M'));
                        }
                    }
                    if ($type === 'lastFiscalQuarter') {
                        $dt->modify('-3 months');
                    }
                    $part['AND'] = [
                        $attribute . '>=' => $dt->format('Y-m-d'),
                        $attribute . '<' => $dt->add(new \DateInterval('P3M'))->format('Y-m-d')
                    ];
                    break;

                case 'between':
                    if (is_array($value)) {
                        $part['AND'] = [
                            $attribute . '>=' => $value[0],
                            $attribute . '<=' => $value[1],
                        ];
                    }
                    break;

                case 'columnLike':
                case 'columnIn':
                case 'columnIsNull':
                case 'columnNotIn':
                    $link = $this->getMetadata()->get(['entityDefs', $this->entityType, 'fields', $attribute, 'link']);
                    $column = $this->getMetadata()->get(['entityDefs', $this->entityType, 'fields', $attribute, 'column']);
                    $alias =  $link . 'Filter' . strval(rand(10000, 99999));
                    $this->setDistinct(true, $result);
                    $this->addLeftJoin([$link, $alias], $result);
                    $columnKey = $alias . 'Middle.' . $column;
                    if ($type === 'columnIn') {
                        $part[$columnKey] = $value;
                    } else if ($type === 'columnNotIn') {
                        $part[$columnKey . '!='] = $value;
                    } else if ($type === 'columnIsNull') {
                        $part[$columnKey] = null;
                    } else if ($type === 'columnIsNotNull') {
                        $part[$columnKey . '!='] = null;
                    } else if ($type === 'columnLike') {
                        $part[$columnKey . '*'] = $value;
                    } else if ($type === 'columnStartsWith') {
                        $part[$columnKey . '*'] = $value . '%';
                    } else if ($type === 'columnEndsWith') {
                        $part[$columnKey . '*'] = '%' . $value;
                    } else if ($type === 'columnContains') {
                        $part[$columnKey . '*'] = '%' . $value . '%';
                    } else if ($type === 'columnEquals') {
                        $part[$columnKey . '='] = $value;
                    } else if ($type === 'columnNotEquals') {
                        $part[$columnKey . '!='] = $value;
                    }
                    break;

                case 'isNotLinked':
                    if (!$result) break;
                    $alias = $attribute . 'IsNotLinkedFilter' . strval(rand(10000, 99999));
                    $part[$alias . '.id'] = null;
                    $this->setDistinct(true, $result);
                    $this->addLeftJoin([$attribute, $alias], $result);
                    break;

                case 'isLinked':
                    if (!$result) break;
                    $alias = $attribute . 'IsLinkedFilter' . strval(rand(10000, 99999));
                    $part[$alias . '.id!='] = null;
                    $this->setDistinct(true, $result);
                    $this->addLeftJoin([$attribute, $alias], $result);
                    break;

                case 'linkedWith':
                    $seed = $this->getSeed();
                    $link = $attribute;
                    if (!$seed->hasRelation($link)) break;

                    $alias =  $link . 'Filter' . strval(rand(10000, 99999));

                    if (is_null($value) || !$value && !is_array($value)) break;

                    $relationType = $seed->getRelationType($link);

                    if ($relationType == 'manyMany') {
                        $this->addLeftJoin([$link, $alias], $result);
                        $midKeys = $seed->getRelationParam($link, 'midKeys');

                        if (!empty($midKeys)) {
                            $key = $midKeys[1];
                            $part[$alias . 'Middle.' . $key] = $value;
                        }
                    } else if ($relationType == 'hasMany') {
                        $this->addLeftJoin([$link, $alias], $result);

                        $part[$alias . '.id'] = $value;
                    } else if ($relationType == 'belongsTo') {
                        $key = $seed->getRelationParam($link, 'key');
                        if (!empty($key)) {
                            $part[$key] = $value;
                        }
                    } else if ($relationType == 'hasOne') {
                        $this->addLeftJoin([$link, $alias], $result);
                        $part[$alias . '.id'] = $value;
                    } else {
                        break;;
                    }
                    $this->setDistinct(true, $result);
                    break;

                case 'notLinkedWith':
                    $seed = $this->getSeed();
                    $link = $attribute;
                    if (!$seed->hasRelation($link)) break;

                    if (is_null($value)) break;

                    $relationType = $seed->getRelationType($link);

                    $alias = $link . 'NotLinkedFilter' . strval(rand(10000, 99999));

                    if ($relationType == 'manyMany') {
                        $this->addLeftJoin([$link, $alias], $result);
                        $midKeys = $seed->getRelationParam($link, 'midKeys');

                        if (!empty($midKeys)) {
                            $key = $midKeys[1];
                            $result['joinConditions'][$alias] = [$key => $value];
                            $part[$alias . 'Middle.' . $key] = null;
                        }
                    } else if ($relationType == 'hasMany') {
                        $this->addLeftJoin([$link, $alias], $result);
                        $result['joinConditions'][$alias] = ['id' => $value];
                        $part[$alias . '.id'] = null;
                    } else if ($relationType == 'belongsTo') {
                        $key = $seed->getRelationParam($link, 'key');
                        if (!empty($key)) {
                            $part[$key . '!='] = $value;
                        }
                    } else if ($relationType == 'hasOne') {
                        $this->addLeftJoin([$link, $alias], $result);
                        $part[$alias . '.id!='] = $value;
                    } else {
                        break;
                    }
                    $this->setDistinct(true, $result);
                    break;

                case 'arrayAnyOf':
                case 'arrayNoneOf':
                case 'arrayIsEmpty':
                case 'arrayIsNotEmpty':
                    $arrayValueAlias = 'arrayFilter' . strval(rand(10000, 99999));
                    $arrayAttribute = $attribute;
                    $arrayEntityType = $this->getEntityType();
                    $idPart = 'id';

                    if (strpos($attribute, '.') > 0) {
                        list($arrayAttributeLink, $arrayAttribute) = explode('.', $attribute);
                        $seed = $this->getSeed();
                        $arrayEntityType = $seed->getRelationParam($arrayAttributeLink, 'entity');
                        $idPart = $arrayAttributeLink . '.id';
                    }

                    if ($type === 'arrayAnyOf') {
                        if (is_null($value) || !$value && !is_array($value)) break;
                        $this->addLeftJoin(['ArrayValue', $arrayValueAlias, [
                            $arrayValueAlias . '.entityId:' => $idPart,
                            $arrayValueAlias . '.entityType' => $arrayEntityType,
                            $arrayValueAlias . '.attribute' => $arrayAttribute
                        ]], $result);
                        $part[$arrayValueAlias . '.value'] = $value;
                    } else if ($type === 'arrayNoneOf') {
                        if (is_null($value) || !$value && !is_array($value)) break;
                        $this->addLeftJoin(['ArrayValue', $arrayValueAlias, [
                            $arrayValueAlias . '.entityId:' => $idPart,
                            $arrayValueAlias . '.entityType' => $arrayEntityType,
                            $arrayValueAlias . '.attribute' => $arrayAttribute,
                            $arrayValueAlias . '.value=' => $value
                        ]], $result);
                        $part[$arrayValueAlias . '.id'] = null;
                    } else if ($type === 'arrayIsEmpty') {
                        $this->addLeftJoin(['ArrayValue', $arrayValueAlias, [
                            $arrayValueAlias . '.entityId:' => $idPart,
                            $arrayValueAlias . '.entityType' => $arrayEntityType,
                            $arrayValueAlias . '.attribute' => $arrayAttribute
                        ]], $result);
                        $part[$arrayValueAlias . '.id'] = null;
                    } else if ($type === 'arrayIsNotEmpty') {
                        $this->addLeftJoin(['ArrayValue', $arrayValueAlias, [
                            $arrayValueAlias . '.entityId:' => $idPart,
                            $arrayValueAlias . '.entityType' => $arrayEntityType,
                            $arrayValueAlias . '.attribute' => $arrayAttribute
                        ]], $result);
                        $part[$arrayValueAlias . '.id!='] = null;
                    }

                    $this->setDistinct(true, $result);
            }
        }

        return $part;
    }

    public function applyOrder(string $sortBy, $desc, array &$result)
    {
        $this->prepareResult($result);
        $this->order($sortBy, $desc, $result);
    }

    public function applyLimit(?int $offset, ?int $maxSize, array &$result)
    {
        $this->prepareResult($result);
        $this->limit($offset, $maxSize, $result);
    }

    public function applyPrimaryFilter(string $filterName, array &$result)
    {
        $this->prepareResult($result);

        $method = 'filter' . ucfirst($filterName);
        if (method_exists($this, $method)) {
            $this->$method($result);
        } else {
            $className = $this->getMetadata()->get(['entityDefs', $this->entityType, 'collection', 'filters', $filterName, 'className']);
            if ($className) {
                if (!class_exists($className)) {
                    $GLOBALS['log']->error("Could find class for filter {$filterName}.");
                    return;
                }
                $impl = $this->getInjectableFactory()->createByClassName($className);
                if (!$impl) {
                    $GLOBALS['log']->error("Could not create filter {$filterName} implementation.");
                    return;
                }
                $impl->applyFilter($this->entityType, $filterName, $result, $this);
            }
        }
    }

    public function applyFilter(string $filterName, array &$result)
    {
        $this->applyPrimaryFilter($filterName, $result);
    }

    public function applyBoolFilter(string $filterName, array &$result)
    {
        $this->prepareResult($result);

        $method = 'boolFilter' . ucfirst($filterName);
        if (method_exists($this, $method)) {
            $this->$method($result);
        }
    }

    public function applyTextFilter(string $textFilter, array &$result)
    {
        $this->prepareResult($result);
        $this->textFilter($textFilter, $result);
    }

    public function applyAdditional(array $params, array &$result)
    {

    }

    public function hasJoin($join, array &$result)
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

    public function hasLeftJoin($leftJoin, array &$result)
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

    public function hasLinkJoined($join, array &$result)
    {
        if (in_array($join, $result['joins'])) {
            return true;
        }

        foreach ($result['joins'] as $item) {
            if (is_array($item) && count($item) > 1) {
                if ($item[0] == $join) {
                    return true;
                }
            }
        }

        if (in_array($join, $result['leftJoins'])) {
            return true;
        }

        foreach ($result['leftJoins'] as $item) {
            if (is_array($item) && count($item) > 1) {
                if ($item[0] == $join) {
                    return true;
                }
            }
        }

        return false;
    }

    public function addJoin($join, array &$result)
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

    public function addLeftJoin($leftJoin, array &$result)
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

    public function setJoinCondition(string $join, $condition, array &$result)
    {
        $result['joinConditions'][$join] = $condition;
    }

    public function setDistinct(bool $distinct, array &$result)
    {
        $result['distinct'] = (bool) $distinct;
    }

    public function addAndWhere(array $whereClause, array &$result)
    {
        $result['whereClause'][] = $whereClause;
    }

    public function addOrWhere(array $whereClause, array &$result)
    {
        $result['whereClause'][] = [
            'OR' => $whereClause
        ];
    }

    public function getFullTextSearchDataForTextFilter($textFilter, $isAuxiliaryUse = false)
    {
        if (array_key_exists($textFilter, $this->fullTextSearchDataCacheHash)) {
            return $this->fullTextSearchDataCacheHash[$textFilter];
        }

        if ($this->getConfig()->get('fullTextSearchDisabled')) {
            return null;
        }

        $result = null;

        $fieldList = $this->getTextFilterFieldList();

        if ($isAuxiliaryUse) {
            $textFilter = str_replace('%', '', $textFilter);
        }

        $fullTextSearchColumnList = $this->getEntityManager()->getOrmMetadata()->get($this->getEntityType(), ['fullTextSearchColumnList']);

        $useFullTextSearch = false;

        if (
            $this->getMetadata()->get(['entityDefs', $this->getEntityType(), 'collection', 'fullTextSearch'])
            &&
            !empty($fullTextSearchColumnList)
        ) {
            $fullTextSearchMinLength = $this->getConfig()->get('fullTextSearchMinLength', self::MIN_LENGTH_FOR_FULL_TEXT_SEARCH);
            if (!$fullTextSearchMinLength) {
                $fullTextSearchMinLength = 0;
            }
            $textFilterWoWildcards = str_replace('*', '', $textFilter);
            if (mb_strlen($textFilterWoWildcards) >= $fullTextSearchMinLength) {
                $useFullTextSearch = true;
            }
        }

        $fullTextSearchFieldList = [];

        if ($useFullTextSearch) {
            foreach ($fieldList as $field) {
                if (strpos($field, '.') !== false) {
                    continue;
                }

                $defs = $this->getMetadata()->get(['entityDefs', $this->getEntityType(), 'fields', $field], []);
                if (empty($defs['type'])) continue;
                $fieldType = $defs['type'];
                if (!empty($defs['notStorable'])) continue;
                if (!$this->getMetadata()->get(['fields', $fieldType, 'fullTextSearch'])) continue;
                $fullTextSearchFieldList[] = $field;
            }
            if (!count($fullTextSearchFieldList)) {
                $useFullTextSearch = false;
            }
        }

        if (empty($fullTextSearchColumnList)) {
            $useFullTextSearch = false;
        }

        if ($isAuxiliaryUse) {
            if (mb_strpos($textFilter, '@') !== false) {
                $useFullTextSearch = false;
            }
        }

        if ($useFullTextSearch) {
            $textFilter = str_replace(['(', ')'], '', $textFilter);

            if (
                $isAuxiliaryUse && mb_strpos($textFilter, '*') === false
                ||
                mb_strpos($textFilter, ' ') === false
                &&
                mb_strpos($textFilter, '+') === false
                &&
                mb_strpos($textFilter, '-') === false
                &&
                mb_strpos($textFilter, '*') === false
            ) {
                $function = 'MATCH_NATURAL_LANGUAGE';
            } else {
                $function = 'MATCH_BOOLEAN';
            }

            $textFilter = str_replace('"*', '"', $textFilter);
            $textFilter = str_replace('*"', '"', $textFilter);

            while (strpos($textFilter, '**')) {
                $textFilter = str_replace('**', '*', $textFilter);
                $textFilter = trim($textFilter);
            }

            while (mb_substr($textFilter, -2)  === ' *') {
                $textFilter = mb_substr($textFilter, 0, mb_strlen($textFilter) - 2);
                $textFilter = trim($textFilter);
            }

            $fullTextSearchColumnSanitizedList = [];
            $query = $this->getEntityManager()->getQuery();
            foreach ($fullTextSearchColumnList as $i => $field) {
                $fullTextSearchColumnSanitizedList[$i] = $query->sanitize($query->toDb($field));
            }

            $where = $function . ':' . implode(',', $fullTextSearchColumnSanitizedList) . ':' . $textFilter;

            $result = [
                'where' => $where,
                'fieldList' => $fullTextSearchFieldList,
                'columnList' => $fullTextSearchColumnList
            ];
        }

        $this->fullTextSearchDataCacheHash[$textFilter] = $result;

        return $result;
    }

    protected function textFilter($textFilter, array &$result)
    {
        $fieldDefs = $this->getSeed()->getAttributes();
        $fieldList = $this->getTextFilterFieldList();
        $group = [];

        $textFilterContainsMinLength = $this->getConfig()->get('textFilterContainsMinLength', self::MIN_LENGTH_FOR_CONTENT_SEARCH);

        $fullTextSearchData = null;

        $forceFullTextSearch = false;

        $useFullTextSearch = !empty($result['useFullTextSearch']);

        if (mb_strpos($textFilter, 'ft:') === 0) {
            $textFilter = mb_substr($textFilter, 3);
            $useFullTextSearch = true;
            $forceFullTextSearch = true;
        }

        $textFilterForFullTextSearch = $textFilter;

        $skipWidlcards = false;

        if (mb_strpos($textFilter, '*') !== false) {
            $skipWidlcards = true;
            $textFilter = str_replace('*', '%', $textFilter);
        } else {
            if (!$useFullTextSearch) {
                $textFilterForFullTextSearch .= '*';
            }
        }

        $textFilterForFullTextSearch = str_replace('%', '*', $textFilterForFullTextSearch);

        $skipFullTextSearch = false;
        if (!$forceFullTextSearch) {
            if (mb_strpos($textFilterForFullTextSearch, '*') === 0) {
                $skipFullTextSearch = true;
            } else if (mb_strpos($textFilterForFullTextSearch, ' *') !== false) {
                $skipFullTextSearch = true;
            }
        }

        $fullTextSearchData = null;
        if (!$skipFullTextSearch) {
            $fullTextSearchData = $this->getFullTextSearchDataForTextFilter($textFilterForFullTextSearch, !$useFullTextSearch);
        }

        $fullTextGroup = [];

        $fullTextSearchFieldList = [];
        if ($fullTextSearchData) {
            $fullTextGroup[] = $fullTextSearchData['where'];
            $fullTextSearchFieldList = $fullTextSearchData['fieldList'];
        }

        foreach ($fieldList as $field) {
            if ($useFullTextSearch) {
                if (in_array($field, $fullTextSearchFieldList)) continue;
            }
            if ($forceFullTextSearch) continue;

            $seed = $this->getSeed();

            $attributeType = null;

            if (strpos($field, '.') !== false) {
                list($link, $foreignField) = explode('.', $field);
                $foreignEntityType = $seed->getRelationParam($link, 'entity');
                $seed = $this->getEntityManager()->getEntity($foreignEntityType);
                $this->addLeftJoin($link, $result);
                if ($seed->getRelationParam($link, 'type') === $seed::HAS_MANY) {
                    $this->setDistinct(true, $result);
                }
                $attributeType = $seed->getAttributeType($foreignField);
            } else {
                $attributeType = $seed->getAttributeType($field);
            }

            if ($attributeType === 'int') {
                if (is_numeric($textFilter)) {
                    $group[$field] = intval($textFilter);
                }
                continue;
            }

            if (!$skipWidlcards) {
                if (
                    mb_strlen($textFilter) >= $textFilterContainsMinLength
                    &&
                    (
                        $attributeType == 'text'
                        ||
                        in_array($field, $this->textFilterUseContainsAttributeList)
                        ||
                        $attributeType == 'varchar' && $this->getConfig()->get('textFilterUseContainsForVarchar')
                    )
                ) {
                    $expression = '%' . $textFilter . '%';
                } else {
                    $expression = $textFilter . '%';
                }
            } else {
                $expression = $textFilter;
            }

            if ($fullTextSearchData) {
                if (!$useFullTextSearch) {
                    if (in_array($field, $fullTextSearchFieldList)) {
                        if (!array_key_exists('OR', $fullTextGroup)) {
                            $fullTextGroup['OR'] = [];
                        }
                        $fullTextGroup['OR'][$field . '*'] = $expression;
                        continue;
                    }
                }
            }

            $group[$field . '*'] = $expression;
        }

        if (!$forceFullTextSearch) {
            $this->applyAdditionalToTextFilterGroup($textFilter, $group, $result);
        }

        if (!empty($fullTextGroup)) {
            $group['AND'] = $fullTextGroup;
        }

        if (count($group) === 0) {
            $result['whereClause'][] = [
                'id' => null
            ];
        }

        $result['whereClause'][] = [
            'OR' => $group
        ];
    }

    protected function applyAdditionalToTextFilterGroup(string $textFilter, array &$group, array &$result)
    {
    }

    public function applyAccess(array &$result)
    {
        $this->prepareResult($result);
        $this->access($result);
    }

    protected function boolFilters(array $params, array &$result)
    {
        if (!empty($params['boolFilterList']) && is_array($params['boolFilterList'])) {
            foreach ($params['boolFilterList'] as $filterName) {
                $this->applyBoolFilter($filterName, $result);
            }
        }
    }

    protected function getBoolFilterWhere(string $filterName)
    {
        $method = 'getBoolFilterWhere' . ucfirst($filterName);
        if (method_exists($this, $method)) {
            return $this->$method();
        }
    }

    protected function boolFilterOnlyMy(&$result)
    {
        if (!$this->checkIsPortal()) {
            if ($this->hasAssignedUsersField()) {
                $this->setDistinct(true, $result);
                $this->addLeftJoin(['assignedUsers', 'assignedUsersAccess'], $result);
                $result['whereClause'][] = [
                    'assignedUsersAccess.id' => $this->getUser()->id
                ];
            } else if ($this->hasAssignedUserField()) {
                $result['whereClause'][] = [
                    'assignedUserId' => $this->getUser()->id
                ];
            } else {
                $result['whereClause'][] = [
                    'createdById' => $this->getUser()->id
                ];
            }
        } else {
            $result['whereClause'][] = [
                'createdById' => $this->getUser()->id
            ];
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

    public function mergeSelectParams(array $selectParams1, ?array $selectParams2) : array
    {
        if (!$selectParams2) {
            return $selectParams1;
        }
        if (!isset($selectParams1['whereClause'])) {
            $selectParams1['whereClause'] = [];
        }
        if (!empty($selectParams2['whereClause'])) {
            $selectParams1['whereClause'][] = $selectParams2['whereClause'];
        }

        if (!isset($selectParams1['havingClause'])) {
            $selectParams1['havingClause'] = [];
        }
        if (!empty($selectParams2['havingClause'])) {
            $selectParams1['havingClause'][] = $selectParams2['havingClause'];
        }

        if (!empty($selectParams2['leftJoins'])) {
            foreach ($selectParams2['leftJoins'] as $item) {
                $this->addLeftJoin($item, $selectParams1);
            }
        }

        if (!empty($selectParams2['joins'])) {
            foreach ($selectParams2['joins'] as $item) {
                $this->addJoin($item, $selectParams1);
            }
        }

        if (isset($selectParams2['select'])) {
            $selectParams1['select'] = $selectParams2['select'];
        }

        if (isset($selectParams2['customJoin'])) {
            if (!isset($selectParams1['customJoin'])) {
                $selectParams1['customJoin'] = '';
            }
            $selectParams1['customJoin'] .= ' ' . $selectParams2['customJoin'];
        }

        if (isset($selectParams2['customWhere'])) {
            if (!isset($selectParams1['customWhere'])) {
                $selectParams1['customWhere'] = '';
            }
            $selectParams1['customWhere'] .= ' ' . $selectParams2['customWhere'];
        }

        if (isset($selectParams2['additionalSelectColumns'])) {
            if (!isset($selectParams1['additionalSelectColumns'])) {
                $selectParams1['additionalSelectColumns'] = [];
            }
            foreach ($selectParams2['additionalSelectColumns'] as $key => $item) {
                $selectParams1['additionalSelectColumns'][$key] = $item;
            }
        }

        if (isset($selectParams2['joinConditions'])) {
            if (!isset($selectParams1['joinConditions'])) {
                $selectParams1['joinConditions'] = [];
            }
            foreach ($selectParams2['joinConditions'] as $key => $item) {
                $selectParams1['joinConditions'][$key] = $item;
            }
        }

        if (isset($selectParams2['orderBy'])) {
            $selectParams1['orderBy'] = $selectParams2['orderBy'];
        }
        if (isset($selectParams2['order'])) {
            $selectParams1['order'] = $selectParams2['order'];
        }

        if (!empty($selectParams2['distinct'])) {
            $selectParams1['distinct'] = true;
        }

        return $selectParams1;
    }

    public function applyLeftJoinsFromWhere($where, array &$result)
    {
        if (!is_array($where)) return;

        foreach ($where as $item) {
            $this->applyLeftJoinsFromWhereItem($item, $result);
        }
    }

    public function applyLeftJoinsFromWhereItem($item, array &$result)
    {
        $type = $item['type'] ?? null;

        if ($type) {
            if (in_array($type, ['subQueryNotIn', 'subQueryIn', 'not'])) return;

            if (in_array($type, ['or', 'and', 'having'])) {
                $value = $item['value'] ?? null;
                if (!is_array($value)) return;
                foreach ($value as $listItem) {
                    $this->applyLeftJoinsFromWhereItem($listItem, $result);
                }
                return;
            }
        }

        $attribute = $item['attribute'] ?? null;
        if (!$attribute) return;

        $this->applyLeftJoinsFromAttribute($attribute, $result);
    }

    protected function applyLeftJoinsFromAttribute(string $attribute, array &$result)
    {
        if (strpos($attribute, ':') !== false) {
            $argumentList = \Espo\ORM\DB\Query\Base::getAllAttributesFromComplexExpression($attribute);
            foreach ($argumentList as $argument) {
                $this->applyLeftJoinsFromAttribute($argument, $result);
            }
            return;
        }

        if (strpos($attribute, '.') !== false) {
            list($link, $attribute) = explode('.', $attribute);
            if ($this->getSeed()->hasRelation($link) && !$this->hasLeftJoin($link, $result)) {
                $this->addLeftJoin($link, $result);
                if ($this->getSeed()->getRelationType($link) === \Espo\ORM\Entity::HAS_MANY) {
                    $result['distinct'] = true;
                }
            }
            return;
        }

        $attributeType = $this->getSeed()->getAttributeType($attribute);
        if ($attributeType === 'foreign') {
            $relation = $this->getSeed()->getAttributeParam($attribute, 'relation');
            if ($relation) {
                $this->addLeftJoin($relation, $result);
            }
        }
    }

    public function getSelectAttributeList(array $params) : ?array
    {
        if (array_key_exists('select', $params)) {
            $passedAttributeList = $params['select'];
        } else {
            return null;
        }

        $seed = $this->getSeed();

        $attributeList = [];
        if (!in_array('id', $passedAttributeList)) {
            $attributeList[] = 'id';
        }

        $aclAttributeList = $this->aclAttributeList;
        if ($this->getUser()->isPortal()) {
            $aclAttributeList = $this->aclPortalAttributeList;
        }

        foreach ($aclAttributeList as $attribute) {
            if (!in_array($attribute, $passedAttributeList) && $seed->hasAttribute($attribute)) {
                $attributeList[] = $attribute;
            }
        }

        foreach ($passedAttributeList as $attribute) {
            if (!in_array($attribute, $attributeList) && $seed->hasAttribute($attribute)) {
                $attributeList[] = $attribute;
            }
        }

        if (!empty($params['orderBy'])) {
            $sortByField = $params['orderBy'];

            $sortByAttributeList = $this->getFieldManagerUtil()->getAttributeList($this->getEntityType(), $sortByField);
            foreach ($sortByAttributeList as $attribute) {
                if (!in_array($attribute, $attributeList) && $seed->hasAttribute($attribute)) {
                    $attributeList[] = $attribute;
                }
            }
        }

        foreach ($this->selectAttributesDependancyMap as $attribute => $dependantAttributeList) {
            if (in_array($attribute, $attributeList)) {
                foreach ($dependantAttributeList as $dependantAttribute) {
                    if (!in_array($dependantAttribute, $attributeList)) {
                        $attributeList[] = $dependantAttribute;
                    }
                }
            }
        }

        return $attributeList;
    }
}
