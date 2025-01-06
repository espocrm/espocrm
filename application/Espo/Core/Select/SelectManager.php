<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Core\Select;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;

use Espo\Core\Acl;
use Espo\Core\AclManager;
use Espo\Core\InjectableFactory;
use Espo\Core\ORM\EntityManager;
use Espo\Core\ORM\Type\FieldType;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\FieldUtil;
use Espo\Core\Utils\Metadata;

use Espo\Entities\StreamSubscription;
use Espo\ORM\Defs\Params\RelationParam;
use Espo\ORM\Entity;
use Espo\ORM\Name\Attribute;
use Espo\ORM\Query\Select as SelectQuery;
use Espo\ORM\QueryComposer\BaseQueryComposer as QueryComposer;

use Espo\Entities\User;

use DateTime;
use DateTimeZone;
use DateInterval;

use Espo\ORM\Type\AttributeType;
use ReflectionMethod;

/**
 * @deprecated As of v7.0. Use Select framework and SelectBuilder instead.
 * @todo Remove in v10.0.
 */
class SelectManager
{
    protected $entityType;

    private $seed = null;

    private $userTimeZone = null;

    protected $additionalFilterTypeList = ['inCategory', 'isUserFromTeams'];

    protected $aclAttributeList = ['assignedUserId', 'createdById'];

    protected $aclPortalAttributeList = ['assignedUserId', 'createdById', 'contactId', 'accountId'];

    protected $textFilterUseContainsAttributeList = [];

    protected $selectAttributesDependancyMap = [];

    protected $fullTextOrderType = self::FT_ORDER_COMBINTED;

    protected $fullTextRelevanceThreshold = null;

    const FT_ORDER_COMBINTED = 0;

    const FT_ORDER_RELEVANCE = 1;

    const FT_ORDER_ORIGINAL = 3;

    const MIN_LENGTH_FOR_CONTENT_SEARCH = 4;

    const MIN_LENGTH_FOR_FULL_TEXT_SEARCH = 4;

    protected $fullTextOrderRelevanceDivider = 5;

    protected $fullTextSearchDataCacheHash = [];

    protected $entityManager;

    protected $user;

    protected $acl;

    protected $aclManager;

    protected $metadata;

    protected $config;

    protected $fieldUtil;

    protected $injectableFactory;

    private $selectBuilderFactory;

    public function __construct(
        EntityManager $entityManager,
        User $user,
        Acl $acl,
        AclManager $aclManager,
        Metadata $metadata,
        Config $config,
        FieldUtil $fieldUtil,
        InjectableFactory $injectableFactory,
        SelectBuilderFactory $selectBuilderFactory
    ) {
        $this->entityManager = $entityManager;
        $this->user = $user;
        $this->acl = $acl;
        $this->aclManager = $aclManager;
        $this->metadata = $metadata;
        $this->config = $config;
        $this->fieldUtil = $fieldUtil;
        $this->injectableFactory = $injectableFactory;
        $this->selectBuilderFactory = $selectBuilderFactory;
    }

    protected function getEntityManager() : EntityManager
    {
        return $this->entityManager;
    }

    protected function getMetadata() : Metadata
    {
        return $this->metadata;
    }

    protected function getUser() : User
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

    protected function getFieldUtil() : FieldUtil
    {
        return $this->fieldUtil;
    }

    public function setEntityType(string $entityType)
    {
        $this->entityType = $entityType;
    }

    protected function getEntityType() : string
    {
        return $this->entityType;
    }

    protected function limit(?int $offset, ?int $maxSize, array &$result)
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

        if ($sortBy) {
            $result['orderBy'] = $sortBy;
            $type = $this->getMetadata()->get(['entityDefs', $this->getEntityType(), 'fields', $sortBy, 'type']);

            if (in_array($type, [FieldType::LINK, FieldType::FILE, FieldType::IMAGE, FieldType::LINK_ONE])) {
                $result['orderBy'] .= 'Name';
            } else if ($type === FieldType::LINK_PARENT) {
                $result['orderBy'] .= 'Type';
            } else if ($type === FieldType::ADDRESS) {
                $result['orderBy'] = [
                    [$sortBy . 'Country', $desc],
                    [$sortBy . 'City', $desc],
                    [$sortBy . 'Street', $desc],
                ];
            } else if ($type === FieldType::ENUM) {
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

                    $result['orderBy'] = [['LIST:' . $sortBy . ':' . implode(',', $list)]];
                }
            } else {
                if (strpos($sortBy, '.') === false && strpos($sortBy, ':') === false) {
                    if (!$this->getSeed()->hasAttribute($sortBy)) {
                        throw new BadRequest("Order by non-existing field '{$sortBy}'.");
                    }
                }
            }

            $orderByAttribute = null;

            if (!is_array($result['orderBy'])) {
                $orderByAttribute = $result['orderBy'];
                $result['orderBy'] = [[$result['orderBy'], $desc]];
            }

            if (
                $sortBy != Attribute::ID &&
                (!$orderByAttribute || !$this->getSeed()->getAttributeParam($orderByAttribute, 'unique')) &&
                $this->getSeed()->hasAttribute(Attribute::ID)
            ) {
                $result['orderBy'][] = [Attribute::ID, $desc];
            }

            return;
        }

        if (!$desc) {
            $result['order'] = 'ASC';
        } else {
            $result['order'] = 'DESC';
        }
    }

    protected function getTextFilterFieldList() : array
    {
        return $this->getMetadata()
            ->get(['entityDefs', $this->entityType, 'collection', 'textFilterFields'], ['name']);
    }

    protected function getSeed(): Entity
    {
        if (empty($this->seed)) {
            $this->seed = $this->entityManager->getNewEntity($this->entityType);
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

        $boolFilterList = [];

        foreach ($where as $item) {
            if (!isset($item['type'])) continue;

            if ($item['type'] == 'bool' && !empty($item['value']) && is_array($item['value'])) {
                $boolOr = [];
                foreach ($item['value'] as $filter) {
                    $boolFilterList[] = $filter;
                }
            } else if ($item['type'] == 'textFilter') {
                if (isset($item['value']) || $item['value'] !== '') {
                    $this->textFilter($item['value'], $result);
                }
            } else if ($item['type'] == 'primary' && !empty($item['value'])) {
                $this->applyPrimaryFilter($item['value'], $result);
            }
        }

        if (count($boolFilterList)) {
            $this->applyBoolFilterList($boolFilterList, $result);
        }

        $whereClause = $this->convertWhere($where, false, $result);

        $result['whereClause'] = array_merge($result['whereClause'], $whereClause);

        $this->applyLeftJoinsFromWhere($where, $result);
    }

    /**
     * Convert 'where' parameters from the frontend format to the format needed by ORM.
     *
     * @return array Where clause for ORM.
     */
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

        if (!$seed->hasRelation($link)) {
            return;
        }

        $relDefs = $this->entityManager->getMetadata()->get($this->entityType, ['relations']);

        $relationType = $seed->getRelationType($link);

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

        $seed = $this->getSeed();

        if (!$seed->hasRelation($link)) {
            return;
        }

        $relationType = $seed->getRelationType($link);

        if ($relationType == 'belongsTo') {
            $key = $seed->getRelationParam($link, 'key');

            $aliasName = 'usersTeams' . ucfirst($link) . strval(rand(10000, 99999));

            $this->addLeftJoin([
                'TeamUser',
                $aliasName . 'Middle',
                [
                    $aliasName . 'Middle.userId:' => $key,
                    $aliasName . 'Middle.deleted' => false,
                ]
            ], $result);

            $result['whereClause'][] = [
                $aliasName . 'Middle.teamId' => $idsValue
            ];
        } else {
            throw new BadRequest("Can't apply isUserFromTeams for link {$link}.");
        }

        $this->setDistinct(true, $result);
    }

    public function applyInCategory(string $link, $value, array &$result)
    {
        $relDefs = $this->entityManager->getMetadata()->get($this->entityType, ['relations']);

        if (empty($relDefs[$link])) {
            throw new BadRequest("Can't apply inCategory for link {$link}.");
        }

        $defs = $relDefs[$link];

        $foreignEntity = $defs['entity'] ?? null;

        if (empty($foreignEntity)) {
            throw new BadRequest("Can't apply inCategory for link {$link}.");
        }

        $pathName = lcfirst($foreignEntity) . 'Path';

        if ($defs['type'] == 'manyMany') {
            if (empty($defs['midKeys'])) {
                throw new BadRequest("Can't apply inCategory for link {$link}.");
            }

            $this->setDistinct(true, $result);
            $this->addJoin($link, $result);

            $key = $defs['midKeys'][1];
            $middleName = $link . 'Middle';

            $this->addJoin([
                ucfirst($pathName),
                $pathName,
                [
                    "{$pathName}.descendorId:" => "{$middleName}.{$key}",
                ]
            ], $result);

            $result['whereClause'][$pathName . '.ascendorId'] = $value;

            return;
        }

        if ($defs['type'] == 'belongsTo') {
            if (empty($defs['key'])) {
                throw new BadRequest("Can't apply inCategory for link {$link}.");
            }

            $key = $defs['key'];

            $this->addJoin([
                ucfirst($pathName),
                $pathName,
                [
                    "{$pathName}.descendorId:" => "{$key}",
                ]
            ], $result);

            $result['whereClause'][$pathName . '.ascendorId'] = $value;

            return;
        }

        throw new BadRequest("Can't apply inCategory for link {$link}.");
    }

    protected function q(array $params, array &$result)
    {
        if (isset($params['q']) && $params['q'] !== '') {
            $textFilter = $params['q'];
            $this->textFilter($textFilter, $result, true);
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

    /**
     * Get empty select parameters.
     */
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
        if (empty($result['from'])) {
            $result['from'] = $this->entityType;
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
                'assignedUsersAccess.id' => $this->getUser()->getId()
            ];
            return;
        }

        if ($this->hasAssignedUserField()) {
            $result['whereClause'][] = [
                'assignedUserId' => $this->getUser()->getId()
            ];
            return;
        }

        if ($this->hasCreatedByField()) {
            $result['whereClause'][] = [
                'createdById' => $this->getUser()->getId()
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
                    'assignedUsersAccess.id' => $this->getUser()->getId()
                ]
            ];
            return;
        }

        $or = [
            'teamsAccess.id' => $this->getUser()->getLinkMultipleIdList('teams')
        ];
        if ($this->hasAssignedUserField()) {
            $or['assignedUserId'] = $this->getUser()->getId();
        } else if ($this->hasCreatedByField()) {
            $or['createdById'] = $this->getUser()->getId();
        }
        $result['whereClause'][] = [
            'OR' => $or
        ];
    }

    protected function accessPortalOnlyOwn(&$result)
    {
        if ($this->getSeed()->hasAttribute('createdById')) {
            $result['whereClause'][] = [
                'createdById' => $this->getUser()->getId()
            ];
        } else {
            $result['whereClause'][] = [
                'id' => null
            ];
        }
    }

    protected function accessPortalOnlyContact(&$result)
    {
        $or = [];

        $contactId = $this->getUser()->get('contactId');

        if ($contactId) {
            if (
                $this->getSeed()->hasAttribute('contactId') &&
                $this->getSeed()->getRelationParam('contact', RelationParam::ENTITY) === 'Contact'
            ) {
                $or['contactId'] = $contactId;
            }

            if (
                $this->getSeed()->hasRelation('contacts') &&
                $this->getSeed()->getRelationParam('contacts', RelationParam::ENTITY) === 'Contact'
            ) {
                $this->addLeftJoin(['contacts', 'contactsAccess'], $result);
                $this->setDistinct(true, $result);
                $or['contactsAccess.id'] = $contactId;
            }
        }

        if ($this->getSeed()->hasAttribute('createdById')) {
            $or['createdById'] = $this->getUser()->getId();
        }

        if ($this->getSeed()->hasAttribute('parentId') && $this->getSeed()->hasRelation('parent')) {
            $contactId = $this->getUser()->get('contactId');
            if ($contactId) {
                $or[] = [
                    'parentType' => 'Contact',
                    'parentId' => $contactId
                ];
            }
        }

        if (!empty($or)) {
            $result['whereClause'][] = [
                'OR' => $or
            ];
        } else {
            $result['whereClause'][] = [
                'id' => null
            ];
        }
    }

    protected function accessPortalOnlyAccount(&$result)
    {
        $or = [];

        $accountIdList = $this->getUser()->getLinkMultipleIdList('accounts');
        $contactId = $this->getUser()->get('contactId');

        if (count($accountIdList)) {
            if (
                $this->getSeed()->hasAttribute('accountId') &&
                $this->getSeed()->getRelationParam('account', RelationParam::ENTITY) === 'Account'
            ) {
                $or['accountId'] = $accountIdList;
            }
            if (
                $this->getSeed()->hasRelation('accounts') &&
                $this->getSeed()->getRelationParam('accounts', RelationParam::ENTITY) === 'Account'
            ) {
                $this->addLeftJoin(['accounts', 'accountsAccess'], $result);
                $this->setDistinct(true, $result);
                $or['accountsAccess.id'] = $accountIdList;
            }
            if ($this->getSeed()->hasAttribute('parentId') && $this->getSeed()->hasRelation('parent')) {
                $or[] = [
                    'parentType' => 'Account',
                    'parentId' => $accountIdList
                ];
                if ($contactId) {
                    $or[] = [
                        'parentType' => 'Contact',
                        'parentId' => $contactId
                    ];
                }
            }
        }

        if ($contactId) {
            if (
                $this->getSeed()->hasAttribute('contactId') &&
                $this->getSeed()->getRelationParam('contact', RelationParam::ENTITY) === 'Contact'
            ) {
                $or['contactId'] = $contactId;
            }
            if (
                $this->getSeed()->hasRelation('contacts') &&
                $this->getSeed()->getRelationParam('contacts', RelationParam::ENTITY) === 'Contact'
            ) {
                $this->addLeftJoin(['contacts', 'contactsAccess'], $result);
                $this->setDistinct(true, $result);
                $or['contactsAccess.id'] = $contactId;
            }
        }

        if ($this->getSeed()->hasAttribute('createdById')) {
            $or['createdById'] = $this->getUser()->getId();
        }

        if (!empty($or)) {
            $result['whereClause'][] = [
                'OR' => $or
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

    public function buildSelectParams(
        array $params,
        bool $withAcl = false,
        bool $checkWherePermission = false,
        bool $forbidComplexExpressions = false
    ) : array {

        return $this->getSelectParams($params, $withAcl, $checkWherePermission, $forbidComplexExpressions);
    }

    /**
     * The same as buildSelectParams.
     */
    public function getSelectParams(
        array $params,
        bool $withAcl = false,
        bool $checkWherePermission = false,
        bool $forbidComplexExpressions = false
    ) : array {

        $builder = $this->selectBuilderFactory
            ->create()
            ->forUser($this->user)
            ->from($this->entityType);

        if ($withAcl) {
            $builder->withAccessControlFilter();
        }

        if ($checkWherePermission) {
            $builder->withWherePermissionCheck();
        }

        if ($forbidComplexExpressions) {
            $builder->withComplexExpressionsForbidden();
        }

        $builder->withSearchParams(SearchParams::fromRaw($params));

        $raw = $builder->build()->getRaw();

        $this->prepareResult($raw);

        return $raw;
    }

    /**
     * Apply default order to select parameters.
     */
    public function applyDefaultOrder(array &$result)
    {
        $orderBy = $this->getMetadata()->get(['entityDefs', $this->entityType, 'collection', 'orderBy']);
        $order = $result['order'] ?? null;

        if (!$order && !is_array($orderBy)) {
            $order = $this->getMetadata()->get(['entityDefs', $this->entityType, 'collection', 'order']) ?? null;
        }

        if ($orderBy) {
            $this->applyOrder($orderBy, $order, $result);
        } else {
            $result['order'] = $order;
        }
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
                $argumentList = QueryComposer::getAllAttributesFromComplexExpression($attribute);
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
            $entityType = $this->getSeed()->getRelationParam($link, RelationParam::ENTITY);
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
            $preferences = $this->getEntityManager()->getEntity('Preferences', $this->getUser()->getId());
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

    public function transformDateTimeWhereItem(array $item) : array
    {
        $format = 'Y-m-d H:i:s';

        $attribute = $item['attribute'] ?? null;
        $value = $item['value'] ?? null;
        $timeZone = $item['timeZone'] ?? 'UTC';
        $type = $item['type'] ?? null;

        // for backward compatibility
        if (!$attribute && isset($item['field'])) {
            $attribute = $item['field'];
        }

        if (!$attribute) {
            throw new BadRequest("Bad datetime where item, empty 'attribute'.");
        }

        if (!$type) {
            throw new BadRequest("Bad datetime where item, empty 'type'.");
        }

        if (empty($value) && in_array($type, ['on', 'before', 'after'])) {
            return [];
        }

        $where = [
            'attribute' => $attribute,
        ];

        $dt = new DateTime('now', new DateTimeZone($timeZone));

        switch ($type) {
            case 'today':
                $where['type'] = 'between';
                $dt->setTime(0, 0, 0);
                $dtTo = clone $dt;
                $dtTo->modify('+1 day -1 second');
                $dt->setTimezone(new DateTimeZone('UTC'));
                $dtTo->setTimezone(new DateTimeZone('UTC'));
                $from = $dt->format($format);
                $to = $dtTo->format($format);
                $where['value'] = [$from, $to];
                break;

            case 'past':
                $where['type'] = 'before';
                $dt->setTimezone(new DateTimeZone('UTC'));
                $where['value'] = $dt->format($format);
                break;

            case 'future':
                $where['type'] = 'after';
                $dt->setTimezone(new DateTimeZone('UTC'));
                $where['value'] = $dt->format($format);
                break;

            case 'lastSevenDays':
                $where['type'] = 'between';

                $dtFrom = clone $dt;

                $dt->setTimezone(new DateTimeZone('UTC'));
                $to = $dt->format($format);

                $dtFrom->modify('-7 day');
                $dtFrom->setTime(0, 0, 0);
                $dtFrom->setTimezone(new DateTimeZone('UTC'));

                $from = $dtFrom->format($format);

                $where['value'] = [$from, $to];
                break;

            case 'lastXDays':
                $where['type'] = 'between';

                $dtFrom = clone $dt;

                $dt->setTimezone(new DateTimeZone('UTC'));
                $to = $dt->format($format);

                $number = strval(intval($item['value']));
                $dtFrom->modify('-'.$number.' day');
                $dtFrom->setTime(0, 0, 0);
                $dtFrom->setTimezone(new DateTimeZone('UTC'));

                $from = $dtFrom->format($format);

                $where['value'] = [$from, $to];
                break;

            case 'nextXDays':
                $where['type'] = 'between';

                $dtTo = clone $dt;

                $dt->setTimezone(new DateTimeZone('UTC'));
                $from = $dt->format($format);

                $number = strval(intval($item['value']));
                $dtTo->modify('+'.$number.' day');
                $dtTo->setTime(24, 59, 59);
                $dtTo->setTimezone(new DateTimeZone('UTC'));

                $to = $dtTo->format($format);

                $where['value'] = [$from, $to];
                break;

            case 'olderThanXDays':
                $where['type'] = 'before';
                $number = strval(intval($item['value']));
                $dt->modify('-'.$number.' day');
                $dt->setTime(0, 0, 0);
                $dt->setTimezone(new DateTimeZone('UTC'));
                $where['value'] = $dt->format($format);
                break;

            case 'afterXDays':
                $where['type'] = 'after';
                $number = strval(intval($item['value']));
                $dt->modify('+'.$number.' day');
                $dt->setTime(0, 0, 0);
                $dt->setTimezone(new DateTimeZone('UTC'));
                $where['value'] = $dt->format($format);
                break;

            case 'on':
                $where['type'] = 'between';
                $dt = new DateTime($value, new DateTimeZone($timeZone));
                $dtTo = clone $dt;
                if (strlen($value) <= 10) {
                    $dtTo->modify('+1 day -1 second');
                }
                $dt->setTimezone(new DateTimeZone('UTC'));
                $dtTo->setTimezone(new DateTimeZone('UTC'));
                $from = $dt->format($format);
                $to = $dtTo->format($format);
                $where['value'] = [$from, $to];
                break;

            case 'before':
                $where['type'] = 'before';
                $dt = new DateTime($value, new DateTimeZone($timeZone));
                $dt->setTimezone(new DateTimeZone('UTC'));
                $where['value'] = $dt->format($format);
                break;

            case 'after':
                $where['type'] = 'after';
                $dt = new DateTime($value, new DateTimeZone($timeZone));
                if (strlen($value) <= 10)
                    $dt->modify('+1 day -1 second');

                $dt->setTimezone(new DateTimeZone('UTC'));
                $where['value'] = $dt->format($format);
                break;

            case 'between':
                $where['type'] = 'between';
                if (is_array($value)) {
                    $dt = new DateTime($value[0], new DateTimeZone($timeZone));
                    $dt->setTimezone(new DateTimeZone('UTC'));
                    $from = $dt->format($format);

                    $dt = new DateTime($value[1], new DateTimeZone($timeZone));
                    $dt->setTimezone(new DateTimeZone('UTC'));
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
                $dtFrom = new DateTime('now', new DateTimeZone($timeZone));
                $dtFrom = $dt->modify('first day of this month')->setTime(0, 0, 0);

                if ($type == 'lastMonth') {
                    $dtFrom->modify('-1 month');
                } else if ($type == 'nextMonth') {
                    $dtFrom->modify('+1 month');
                }

                $dtTo = clone $dtFrom;
                $dtTo->modify('+1 month');

                $dtFrom->setTimezone(new DateTimeZone('UTC'));
                $dtTo->setTimezone(new DateTimeZone('UTC'));

                $where['value'] = [$dtFrom->format($format), $dtTo->format($format)];
                break;

            case 'currentQuarter':
            case 'lastQuarter':
                $where['type'] = 'between';
                $dt = new DateTime('now', new DateTimeZone($timeZone));
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

                $dtFrom->add(new DateInterval('P'.(($quarter - 1) * 3).'M'));
                $dtTo = clone $dtFrom;
                $dtTo->add(new DateInterval('P3M'));
                $dtFrom->setTimezone(new DateTimeZone('UTC'));
                $dtTo->setTimezone(new DateTimeZone('UTC'));
                $where['value'] = [
                    $dtFrom->format($format),
                    $dtTo->format($format)
                ];
                break;

            case 'currentYear':
            case 'lastYear':
                $where['type'] = 'between';
                $dtFrom = new DateTime('now', new DateTimeZone($timeZone));
                $dtFrom->modify('first day of January this year')->setTime(0, 0, 0);
                if ($type == 'lastYear') {
                    $dtFrom->modify('-1 year');
                }
                $dtTo = clone $dtFrom;
                $dtTo = $dtTo->modify('+1 year');
                $dtFrom->setTimezone(new DateTimeZone('UTC'));
                $dtTo->setTimezone(new DateTimeZone('UTC'));
                $where['value'] = [
                    $dtFrom->format($format),
                    $dtTo->format($format)
                ];
                break;

            case 'currentFiscalYear':
            case 'lastFiscalYear':
                $where['type'] = 'between';
                $dtToday = new DateTime('now', new DateTimeZone($timeZone));
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
                $dtFrom->setTimezone(new DateTimeZone('UTC'));
                $dtTo->setTimezone(new DateTimeZone('UTC'));
                $where['value'] = [
                    $dtFrom->format($format),
                    $dtTo->format($format)
                ];
                break;

            case 'currentFiscalQuarter':
            case 'lastFiscalQuarter':
                $where['type'] = 'between';
                $dtToday = new DateTime('now', new DateTimeZone($timeZone));
                $dt = clone $dtToday;
                $fiscalYearShift = $this->getConfig()->get('fiscalYearShift', 0);
                $dt->modify('first day of January this year')->modify('+' . $fiscalYearShift . ' months')->setTime(0, 0, 0);
                $month = intval($dtToday->format('m'));
                $quarterShift = floor(($month - $fiscalYearShift - 1) / 3);
                if ($quarterShift) {
                    if ($quarterShift >= 0) {
                        $dt->add(new DateInterval('P'.($quarterShift * 3).'M'));
                    } else {
                        $quarterShift *= -1;
                        $dt->sub(new DateInterval('P'.($quarterShift * 3).'M'));
                    }
                }
                if ($type === 'lastFiscalQuarter') {
                    $dt->modify('-3 months');
                }
                $dtFrom = clone $dt;
                $dtTo = clone $dt;
                $dtTo = $dtTo->modify('+3 months');
                $dtFrom->setTimezone(new DateTimeZone('UTC'));
                $dtTo->setTimezone(new DateTimeZone('UTC'));
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

    protected function getWherePart(array $item, array &$result = []) : array
    {
        $type = $item['type'] ?? null;
        $value = $item['value'] ?? null;
        $attribute = $item['attribute'] ?? null;

        // for backward compatibility
        if (!$attribute && !empty($item['field'])) {
            $attribute = $item['field'];
        }

        if ($attribute && !is_string($attribute)) {
            throw new BadRequest("Bad 'attribute' in where item.");
        }

        if (!empty($item['dateTime'])) {
            return $this->convertDateTimeWhere($item);
        }

        if (!$type) {
            throw new BadRequest("No 'type' in where item.");
        }

        if ($attribute && $type) {
            $methodName = 'getWherePart' . ucfirst($attribute) . ucfirst($type);
            if (method_exists($this, $methodName)) {
                return $this->$methodName($value, $result);
            }
        }

        $part = [];

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
                $dt1 = new DateTime();
                $dt2 = clone $dt1;
                $dt2->modify('-7 days');
                $part['AND'] = [
                    $attribute . '>=' => $dt2->format('Y-m-d'),
                    $attribute . '<=' => $dt1->format('Y-m-d'),
                ];
                break;

            case 'lastXDays':
                $dt1 = new DateTime();
                $dt2 = clone $dt1;
                $number = strval(intval($value));

                $dt2->modify('-'.$number.' days');
                $part['AND'] = [
                    $attribute . '>=' => $dt2->format('Y-m-d'),
                    $attribute . '<=' => $dt1->format('Y-m-d'),
                ];
                break;

            case 'nextXDays':
                $dt1 = new DateTime();
                $dt2 = clone $dt1;
                $number = strval(intval($value));
                $dt2->modify('+'.$number.' days');
                $part['AND'] = [
                    $attribute . '>=' => $dt1->format('Y-m-d'),
                    $attribute . '<=' => $dt2->format('Y-m-d'),
                ];
                break;

            case 'olderThanXDays':
                $dt1 = new DateTime();
                $number = strval(intval($value));
                $dt1->modify('-'.$number.' days');
                $part[$attribute . '<'] = $dt1->format('Y-m-d');
                break;

            case 'afterXDays':
                $dt1 = new DateTime();
                $number = strval(intval($value));
                $dt1->modify('+'.$number.' days');
                $part[$attribute . '>'] = $dt1->format('Y-m-d');
                break;

            case 'currentMonth':
                $dt = new DateTime();
                $part['AND'] = [
                    $attribute . '>=' => $dt->modify('first day of this month')->format('Y-m-d'),
                    $attribute . '<' => $dt->add(new DateInterval('P1M'))->format('Y-m-d'),
                ];
                break;

            case 'lastMonth':
                $dt = new DateTime();
                $part['AND'] = [
                    $attribute . '>=' => $dt->modify('first day of last month')->format('Y-m-d'),
                    $attribute . '<' => $dt->add(new DateInterval('P1M'))->format('Y-m-d'),
                ];
                break;

            case 'nextMonth':
                $dt = new DateTime();
                $part['AND'] = [
                    $attribute . '>=' => $dt->modify('first day of next month')->format('Y-m-d'),
                    $attribute . '<' => $dt->add(new DateInterval('P1M'))->format('Y-m-d'),
                ];
                break;

            case 'currentQuarter':
                $dt = new DateTime();
                $quarter = ceil($dt->format('m') / 3);
                $dt->modify('first day of January this year');
                $part['AND'] = [
                    $attribute . '>=' => $dt->add(new DateInterval('P'.(($quarter - 1) * 3).'M'))->format('Y-m-d'),
                    $attribute . '<' => $dt->add(new DateInterval('P3M'))->format('Y-m-d'),
                ];
                break;

            case 'lastQuarter':
                $dt = new DateTime();
                $quarter = ceil($dt->format('m') / 3);
                $dt->modify('first day of January this year');
                $quarter--;
                if ($quarter == 0) {
                    $quarter = 4;
                    $dt->modify('-1 year');
                }
                $part['AND'] = [
                    $attribute . '>=' => $dt->add(new DateInterval('P'.(($quarter - 1) * 3).'M'))->format('Y-m-d'),
                    $attribute . '<' => $dt->add(new DateInterval('P3M'))->format('Y-m-d'),
                ];
                break;

            case 'currentYear':
                $dt = new DateTime();
                $part['AND'] = [
                    $attribute . '>=' => $dt->modify('first day of January this year')->format('Y-m-d'),
                    $attribute . '<' => $dt->add(new DateInterval('P1Y'))->format('Y-m-d'),
                ];
                break;

            case 'lastYear':
                $dt = new DateTime();
                $part['AND'] = [
                    $attribute . '>=' => $dt->modify('first day of January last year')->format('Y-m-d'),
                    $attribute . '<' => $dt->add(new DateInterval('P1Y'))->format('Y-m-d'),
                ];
                break;

            case 'currentFiscalYear':
            case 'lastFiscalYear':
                $dtToday = new DateTime();
                $dt = new DateTime();
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
                    $attribute . '<' => $dt->add(new DateInterval('P1Y'))->format('Y-m-d')
                ];
                break;

            case 'currentFiscalQuarter':
            case 'lastFiscalQuarter':
                $dtToday = new DateTime();
                $dt = new DateTime();
                $fiscalYearShift = $this->getConfig()->get('fiscalYearShift', 0);
                $dt->modify('first day of January this year')->modify('+' . $fiscalYearShift . ' months');
                $month = intval($dtToday->format('m'));
                $quarterShift = floor(($month - $fiscalYearShift - 1) / 3);
                if ($quarterShift) {
                    if ($quarterShift >= 0) {
                        $dt->add(new DateInterval('P'.($quarterShift * 3).'M'));
                    } else {
                        $quarterShift *= -1;
                        $dt->sub(new DateInterval('P'.($quarterShift * 3).'M'));
                    }
                }
                if ($type === 'lastFiscalQuarter') {
                    $dt->modify('-3 months');
                }
                $part['AND'] = [
                    $attribute . '>=' => $dt->format('Y-m-d'),
                    $attribute . '<' => $dt->add(new DateInterval('P3M'))->format('Y-m-d')
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
                $part['id!=s'] = [
                    'selectParams' =>  [
                        'select' => ['id'],
                        'joins' => [$attribute],
                    ]
                ];
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
                    break;
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
                    $key = $seed->getRelationParam($link, 'midKeys')[1];

                    $this->addLeftJoin(
                        [
                            $link, $alias, [$key => $value],
                        ],
                        $result
                    );

                    $part[$alias . 'Middle.' . $key] = null;

                } else if ($relationType == 'hasMany') {
                    $this->addLeftJoin(
                        [
                            $link, $alias, ['id' => $value]
                        ],
                        $result
                    );

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
            case 'arrayAllOf':
                if (!$result) break;

                $arrayValueAlias = 'arrayFilter' . rand(10000, 99999);
                $arrayAttribute = $attribute;
                $arrayEntityType = $this->getEntityType();
                $idPart = 'id';

                $seed = $this->getSeed();

                if (strpos($attribute, '.') > 0 || $seed->getAttributeType($attribute) === AttributeType::FOREIGN) {
                    if ($seed->getAttributeType($attribute) === AttributeType::FOREIGN) {
                        $arrayAttributeLink = $seed->getAttributeParam($attribute, 'relation');
                        $arrayAttribute = $seed->getAttributeParam($attribute, 'foreign');
                    } else {
                        list($arrayAttributeLink, $arrayAttribute) = explode('.', $attribute);
                    }

                    $arrayEntityType = $seed->getRelationParam($arrayAttributeLink, RelationParam::ENTITY);

                    $arrayLinkAlias = $arrayAttributeLink . 'Filter' . rand(10000, 99999);
                    $idPart = $arrayLinkAlias . '.id';

                    $this->addLeftJoin([$arrayAttributeLink, $arrayLinkAlias], $result);

                    $relationType = $seed->getRelationType($arrayAttributeLink);
                    if ($relationType === 'manyMany' || $relationType === 'hasMany') {
                        $this->setDistinct(true, $result);
                    }
                }

                if ($type === 'arrayAnyOf') {
                    if (is_null($value) || !$value && !is_array($value)) break;
                    $this->addLeftJoin(['ArrayValue', $arrayValueAlias, [
                        $arrayValueAlias . '.entityId:' => $idPart,
                        $arrayValueAlias . '.entityType' => $arrayEntityType,
                        $arrayValueAlias . '.attribute' => $arrayAttribute
                    ]], $result);
                    $part[$arrayValueAlias . '.value'] = $value;

                    $this->setDistinct(true, $result);
                } else if ($type === 'arrayNoneOf') {
                    if (is_null($value) || !$value && !is_array($value)) break;
                    $this->addLeftJoin(['ArrayValue', $arrayValueAlias, [
                        $arrayValueAlias . '.entityId:' => $idPart,
                        $arrayValueAlias . '.entityType' => $arrayEntityType,
                        $arrayValueAlias . '.attribute' => $arrayAttribute,
                        $arrayValueAlias . '.value=' => $value
                    ]], $result);
                    $part[$arrayValueAlias . '.id'] = null;

                    $this->setDistinct(true, $result);
                } else if ($type === 'arrayIsEmpty') {
                    $this->addLeftJoin(['ArrayValue', $arrayValueAlias, [
                        $arrayValueAlias . '.entityId:' => $idPart,
                        $arrayValueAlias . '.entityType' => $arrayEntityType,
                        $arrayValueAlias . '.attribute' => $arrayAttribute
                    ]], $result);
                    $part[$arrayValueAlias . '.id'] = null;

                    $this->setDistinct(true, $result);
                } else if ($type === 'arrayIsNotEmpty') {
                    $this->addLeftJoin(['ArrayValue', $arrayValueAlias, [
                        $arrayValueAlias . '.entityId:' => $idPart,
                        $arrayValueAlias . '.entityType' => $arrayEntityType,
                        $arrayValueAlias . '.attribute' => $arrayAttribute
                    ]], $result);
                    $part[$arrayValueAlias . '.id!='] = null;

                    $this->setDistinct(true, $result);
                } else if ($type === 'arrayAllOf') {
                    if (is_null($value) || !$value && !is_array($value)) break;

                    if (!is_array($value)) {
                        $value = [$value];
                    }

                    foreach ($value as $arrayValue) {
                        $part[] = [
                            $idPart .'=s' => [
                                'entityType' => 'ArrayValue',
                                'selectParams' => [
                                    'select' => ['entityId'],
                                    'whereClause' => [
                                        'value' => $arrayValue,
                                        'attribute' => $arrayAttribute,
                                        'entityType' => $arrayEntityType,
                                    ],
                                ],
                            ]
                        ];
                    }
                }
        }

        return $part;
    }

    /**
     * Apply an order to select parameters.
     */
    public function applyOrder(string $sortBy, $desc, array &$result)
    {
        $this->prepareResult($result);
        $this->order($sortBy, $desc, $result);
    }

    /**
     * Apply a limit to select parameters.
     */
    public function applyLimit(?int $offset, ?int $maxSize, array &$result)
    {
        $this->prepareResult($result);
        $this->limit($offset, $maxSize, $result);
    }

    /**
     * Fallback for backward compatibility.
     */
    public function hasInheritedAccessMethod() : bool
    {
        $method = new ReflectionMethod($this, 'access');

        return $method->getDeclaringClass()->getName() !== SelectManager::class;
    }

    /**
     * Fallback for backward compatibility.
     */
    public function applyAccessToQueryBuilder(OrmSelectBuilder $queryBuilder)
    {
        $result = $queryBuilder->build()->getRaw();

        $this->access($result);

        $queryBuilder->setRawParams($result);
    }

    /**
     * Fallback for backward compatibility.
     */
    public function hasInheritedAccessFilterMethod(string $filterName) : bool
    {
        if (
            $this->metadata->get(
                ['selectDefs', $this->entityType, 'accessControlFilterClassNameMap', $filterName]
            )
        ) {
            return false;
        }

        $methodName = 'access' . ucfirst($filterName);

        if (!method_exists($this, $methodName)) {
            return false;
        }

        $method = new ReflectionMethod($this, $methodName);

        return $method->getDeclaringClass()->getName() !== SelectManager::class;
    }

    /**
     * Fallback for backward compatibility.
     */
    public function applyAccessFilterToQueryBuilder(OrmSelectBuilder $queryBuilder, string $filterName)
    {
        $methodName = 'access' . ucfirst($filterName);

        $result = $queryBuilder->build()->getRaw();

        $this->$methodName($result);

        $queryBuilder->setRawParams($result);
    }

    /**
     * Fallback for backward compatibility.
     */
    public function hasBoolFilter(string $filter) : bool
    {
        $method = 'boolFilter' . ucfirst($filter);

        return method_exists($this, $method);
    }

    /**
     * Fallback for backward compatibility.
     */
    public function applyBoolFilterToQueryBuilder(OrmSelectBuilder $queryBuilder, string $filter) : array
    {
        $result = $queryBuilder->build()->getRaw();

        $method = 'boolFilter' . ucfirst($filter);

        if (!method_exists($this, $method)) {
            throw new BadRequest("Bool filter '{$filter}' does not exist.");
        }

        $rawWhereClause = $this->$method($result) ?? [];

        $queryBuilder->setRawParams($result);

        return $rawWhereClause;
    }

    /**
     * Fallback for backward compatibility.
     */
    public function hasPrimaryFilter(string $filter) : bool
    {
        if (
            method_exists($this, 'filter' . ucfirst($filter))
        ) {
            return true;
        }

        if (
            $this->getMetadata()->get(
                ['entityDefs', $this->entityType, 'collection', 'filters', $filter, 'className']
            )
        ) {
            return true;
        }

        return false;
    }

    /**
     * Fallback for backward compatibility.
     */
    public function applyPrimaryFilterToQueryBuilder(OrmSelectBuilder $queryBuilder, string $filter)
    {
        $result = $queryBuilder->build()->getRaw();

        $this->applyPrimaryFilter($filter, $result);

        $queryBuilder->setRawParams($result);
    }

    /**
     * Apply a primary filter to select parameters.
     */
    public function applyPrimaryFilter(string $filter, array &$result)
    {
        $this->prepareResult($result);

        $method = 'filter' . ucfirst($filter);

        if (method_exists($this, $method)) {
            $this->$method($result);

            return;
        }

        $className = $this->getMetadata()
            ->get(['entityDefs', $this->entityType, 'collection', 'filters', $filter, 'className']);

        if ($className) {
            $impl = $this->getInjectableFactory()->create($className);

            $impl->applyFilter($this->entityType, $filter, $result, $this);

            return;
        }

        $result['whereClause'][] = ['id' => null];
    }

    public function applyFilter(string $filter, array &$result)
    {
        $this->applyPrimaryFilter($filter, $result);
    }

    /**
     * Apply a bool filter to select parameters.
     */
    public function applyBoolFilter(string $filter, array &$result)
    {
        $this->prepareResult($result);

        $method = 'boolFilter' . ucfirst($filter);
        if (method_exists($this, $method)) {
            $wherePart = $this->$method($result);
            if ($wherePart) {
                $result['whereClause'][] = $wherePart;
            }
        }
    }

    /**
     * Apply a list of bool filters to select parameters.
     */
    public function applyBoolFilterList(array $filterList, array &$result)
    {
        $this->prepareResult($result);

        $wherePartList = [];

        foreach ($filterList as $filter) {
            $method = 'boolFilter' . ucfirst($filter);
            if (method_exists($this, $method)) {
                $wherePart = $this->$method($result);
                if ($wherePart) {
                    $wherePartList[] = $wherePart;
                }
            }
        }

        if (count($wherePartList)) {
            if (count($wherePartList) === 1) {
                $result['whereClause'][] = $wherePartList;
            } else {
                $result['whereClause'][] = ['OR' => $wherePartList];
            }
        }
    }

    /**
     * Apply a text filter to select parameters.
     */
    public function applyTextFilter(string $textFilter, array &$result)
    {
        $this->prepareResult($result);
        $this->textFilter($textFilter, $result);
    }

    public function applyAdditional(array $params, array &$result)
    {

    }

    /**
     * Check whether a link is already in JOINs. If an existing join has alias, then the alias is checked, the link is ignored.
     */
    public function hasJoin($join, array &$result)
    {
        $list = $result['joins'] ?? [];

        if (in_array($join, $list)) {
            return true;
        }

        foreach ($list as $item) {
            if (is_array($item) && count($item) > 1) {
                if ($item[1] == $join) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check whether a link is already in LEFT JOINs. If an existing join has alias, then the alias is checked,
     * the link is ignored.
     */
    public function hasLeftJoin($leftJoin, array &$result)
    {
        $list = $result['leftJoins'] ?? [];

        if (in_array($leftJoin, $list)) {
            return true;
        }

        foreach ($list as $item) {
            if (is_array($item) && count($item) > 1) {
                if ($item[1] == $leftJoin) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check whether a link is already joined. If an existing join has alias, then the alias is checked, the link is ignored.
     */
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

    /**
     * Add JOIN.
     *
     * @param string|array $join Format used for array: [link, alias, conditions].
     */
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

    /**
     * Add LEFT JOIN.
     *
     * @param string|array $join Format used for array: [link, alias, conditions].
     */
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

    /**
     * Set DISTINCT.
     */
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

        $fullTextSearchColumnList = $this->getEntityManager()->getMetadata()->get(
            $this->getEntityType(), ['fullTextSearchColumnList']
        );

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

            if (substr_count($textFilter, '\'') % 2 != 0) {
                $useFullTextSearch = false;
            }
            if (substr_count($textFilter, '"') % 2 != 0) {
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

            $textFilter = str_replace('\'', '\'\'', $textFilter);

            while (strpos($textFilter, '**')) {
                $textFilter = str_replace('**', '*', $textFilter);
                $textFilter = trim($textFilter);
            }

            while (mb_substr($textFilter, -2)  === ' *') {
                $textFilter = mb_substr($textFilter, 0, mb_strlen($textFilter) - 2);
                $textFilter = trim($textFilter);
            }

            $where = $function . ':(' . implode(', ', $fullTextSearchColumnList) . ', ' . "'{$textFilter}'" . ')';

            $result = [
                'where' => $where,
                'fieldList' => $fullTextSearchFieldList,
                'columnList' => $fullTextSearchColumnList,
            ];
        }

        $this->fullTextSearchDataCacheHash[$textFilter] = $result;

        return $result;
    }

    protected function textFilter($textFilter, array &$result, $noFullText = false)
    {
        $fieldList = $this->getTextFilterFieldList();

        $group = [];

        $textFilterContainsMinLength = $this->getConfig()
            ->get('textFilterContainsMinLength', self::MIN_LENGTH_FOR_CONTENT_SEARCH);

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

        if ($noFullText) {
            $skipFullTextSearch = true;
        }

        $fullTextSearchData = null;

        if (!$skipFullTextSearch) {
            $fullTextSearchData = $this->getFullTextSearchDataForTextFilter($textFilterForFullTextSearch, !$useFullTextSearch);
        }

        $fullTextGroup = [];

        $fullTextSearchFieldList = [];

        if ($fullTextSearchData) {
            if ($this->fullTextRelevanceThreshold) {
                $fullTextGroup[] = [$fullTextSearchData['where'] . '>=' => $this->fullTextRelevanceThreshold];
            } else {
                $fullTextGroup[] = $fullTextSearchData['where'];
            }

            $fullTextSearchFieldList = $fullTextSearchData['fieldList'];

            $relevanceExpression = $fullTextSearchData['where'];

            $fullTextOrderType = $this->fullTextOrderType;

            $orderTypeMap = [
                'combined' => self::FT_ORDER_COMBINTED,
                'relevance' => self::FT_ORDER_RELEVANCE,
                'original' => self::FT_ORDER_ORIGINAL,
            ];

            $mOrderType = $this->getMetadata()->get(['entityDefs', $this->entityType, 'collection', 'fullTextSearchOrderType']);

            if ($mOrderType) {
                $fullTextOrderType = $orderTypeMap[$mOrderType];
            }

            if (!isset($result['orderBy']) || $fullTextOrderType === self::FT_ORDER_RELEVANCE) {
                $result['orderBy'] = [[$relevanceExpression, 'desc']];
                $result['order'] = null;
            } else {
                if ($fullTextOrderType === self::FT_ORDER_COMBINTED) {
                    $relevanceExpression =
                        'ROUND:(DIV:(' . $fullTextSearchData['where'] . ','.$this->fullTextOrderRelevanceDivider.'))';

                    if (is_string($result['orderBy'])) {
                        $result['orderBy'] = [
                            [$relevanceExpression, 'desc'],
                            [$result['orderBy'], $result['order'] ?? 'asc'],
                        ];
                    } else if (is_array($result['orderBy'])) {
                        $result['orderBy'] = array_merge(
                            [[$relevanceExpression, 'desc']],
                            $result['orderBy']
                        );
                    }
                }
            }

            $result['hasFullTextSearch'] = true;
        }

        foreach ($fieldList as $field) {
            if ($useFullTextSearch) {
                if (in_array($field, $fullTextSearchFieldList)) {
                    continue;
                }
            }

            if ($forceFullTextSearch) {
                continue;
            }

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

                if ($attributeType === AttributeType::FOREIGN) {
                    $link = $seed->getAttributeParam($field, 'relation');

                    if ($link) {
                        $this->addLeftJoin($link, $result);
                    }
                }
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
                        $attributeType == AttributeType::TEXT
                        ||
                        in_array($field, $this->textFilterUseContainsAttributeList)
                        ||
                        $attributeType == AttributeType::VARCHAR && $this->getConfig()->get('textFilterUseContainsForVarchar')
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
        $result['from'] = $this->entityType;

        $query = SelectQuery::fromRaw($result);

        $result = $this->selectBuilderFactory
            ->create()
            ->clone($query)
            ->forUser($this->user)
            ->withAccessControlFilter()
            ->build()
            ->getRaw();
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

        return null;
    }

    protected function boolFilterOnlyMy(&$result)
    {
        if (!$this->checkIsPortal()) {
            if ($this->hasAssignedUsersField()) {
                $this->setDistinct(true, $result);
                $this->addLeftJoin(['assignedUsers', 'assignedUsersOnlyMyFilter'], $result);
                $wherePart = [
                    'assignedUsersOnlyMyFilter.id' => $this->getUser()->getId()
                ];
            } else if ($this->hasAssignedUserField()) {
                $wherePart = [
                    'assignedUserId' => $this->getUser()->getId()
                ];
            } else {
                $wherePart = [
                    'createdById' => $this->getUser()->getId()
                ];
            }
        } else {
            $wherePart = [
                'createdById' => $this->getUser()->getId()
            ];
        }

        return $wherePart;
    }

    protected function boolFilterOnlyMyTeam(&$result)
    {
        $teamIdList = $this->getUser()->getLinkMultipleIdList('teams');

        if (count($teamIdList) === 0) {
            return [
                'id' => null
            ];
        }

        $this->addLeftJoin(['teams', 'teamsOnlyMyFilter'], $result);
        $this->setDistinct(true, $result);
        return [
            'teamsOnlyMyFilterMiddle.teamId' => $teamIdList
        ];
    }

    protected function filterFollowed(&$result)
    {
        $this->addJoin([
            StreamSubscription::ENTITY_TYPE,
            'subscription',
            [
                'subscription.entityType' => $this->getEntityType(),
                'subscription.entityId=:' => 'id',
                'subscription.userId' => $this->getUser()->getId(),
            ]
        ], $result);
    }

    protected function boolFilterFollowed(&$result)
    {
        $this->addLeftJoin([
            StreamSubscription::ENTITY_TYPE,
            'subscription',
            [
                'subscription.entityType' => $this->getEntityType(),
                'subscription.entityId=:' => 'id',
                'subscription.userId' => $this->getUser()->getId(),
            ]
        ], $result);

        return ['subscription.id!=' => null];
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


        if (!empty($selectParams2['joins'])) {
            foreach ($selectParams2['joins'] as $item) {
                $this->addJoin($item, $selectParams1);
            }
        }

        if (!empty($selectParams2['leftJoins'])) {
            foreach ($selectParams2['leftJoins'] as $item) {
                if ($this->hasJoin($item, $selectParams1)) {
                    continue;
                }

                $this->addLeftJoin($item, $selectParams1);
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
            $argumentList = QueryComposer::getAllAttributesFromComplexExpression($attribute);
            foreach ($argumentList as $argument) {
                $this->applyLeftJoinsFromAttribute($argument, $result);
            }
            return;
        }

        if (strpos($attribute, '.') !== false) {
            list($link, $attribute) = explode('.', $attribute);
            if ($this->getSeed()->hasRelation($link) && !$this->hasLeftJoin($link, $result)) {
                $this->addLeftJoin($link, $result);
                if ($this->getSeed()->getRelationType($link) === Entity::HAS_MANY) {
                    $result['distinct'] = true;
                }
            }
            return;
        }

        $attributeType = $this->getSeed()->getAttributeType($attribute);
        if ($attributeType === AttributeType::FOREIGN) {
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
        if (!in_array(Attribute::ID, $passedAttributeList)) {
            $attributeList[] = Attribute::ID;
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

        $sortByField = $params['orderBy'] ?? $this->getMetadata()->get(['entityDefs', $this->entityType, 'collection', 'orderBy']);

        if ($sortByField) {
            $sortByAttributeList = $this->getFieldUtil()->getAttributeList($this->getEntityType(), $sortByField);
            foreach ($sortByAttributeList as $attribute) {
                if (!in_array($attribute, $attributeList) && $seed->hasAttribute($attribute)) {
                    $attributeList[] = $attribute;
                }
            }
        }

        $map = $this->selectAttributesDependancyMap;

        $map = array_merge(
            $map,
            $this->metadata->get(['selectDefs', $this->entityType, 'selectAttributesDependencyMap']) ?? []
        );

        foreach ($map as $attribute => $dependantAttributeList) {
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

    protected function hasInOrderBy(string $attribute, array &$result)
    {
        $orderBy = $result['orderBy'] ?? null;

        if (!$orderBy) return false;

        if (is_string($orderBy)) {
            return $attribute === $orderBy;
        }

        if (is_array($orderBy)) {
            foreach ($orderBy as $item) {
                if (is_array($item) && count($item)) {
                    if ($item[0] === $attribute) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
