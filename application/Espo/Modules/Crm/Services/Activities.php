<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2020 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

namespace Espo\Modules\Crm\Services;

use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Exceptions\Forbidden;

use Espo\ORM\{
    Entity,
    QueryParams\Select,
};

use PDO;

use Espo\Core\Di;

class Activities implements

    Di\ConfigAware,
    Di\MetadataAware,
    Di\AclAware,
    Di\SelectManagerFactoryAware,
    Di\ServiceFactoryAware,
    Di\EntityManagerAware,
    Di\UserAware
{

    use Di\ConfigSetter;
    use Di\MetadataSetter;
    use Di\AclSetter;
    use Di\SelectManagerFactorySetter;
    use Di\ServiceFactorySetter;
    use Di\EntityManagerSetter;
    use Di\UserSetter;

    const UPCOMING_ACTIVITIES_FUTURE_DAYS = 1;

    const UPCOMING_ACTIVITIES_TASK_FUTURE_DAYS = 7;

    const REMINDER_PAST_HOURS = 24;

    const BUSY_RANGES_MAX_RANGE_DAYS = 10;

    protected function getPDO()
    {
        return $this->getEntityManager()->getPDO();
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

    protected function getConfig()
    {
        return $this->config;
    }

    protected function getMetadata()
    {
        return $this->metadata;
    }

    protected function getSelectManagerFactory()
    {
        return $this->selectManagerFactory;
    }

    protected function getServiceFactory()
    {
        return $this->serviceFactory;
    }

    protected function isPerson($scope)
    {
        return in_array($scope, ['Contact', 'Lead', 'User']) ||
            $this->getMetadata()->get(['scopes', $scope, 'type']) === 'Person';
    }

    protected function isCompany($scope)
    {
        return in_array($scope, ['Account']) || $this->getMetadata()->get(['scopes', $scope, 'type']) === 'Company';
    }

    protected function getActivitiesUserMeetingQuery(
        Entity $entity, array $statusList = [], $isHistory = false, $additinalSelectParams = null
    ) {
        $selectManager = $this->getSelectManagerFactory()->create('Meeting');

        $selectParams = [
            'from' => 'Meeting',
            'select' => [
                'id',
                'name',
                ['dateStart', 'dateStart'],
                ['dateEnd', 'dateEnd'],
                ['dateStartDate', 'dateStartDate'],
                ['dateEndDate', 'dateEndDate'],
                ['VALUE:Meeting', '_scope'],
                'assignedUserId',
                'assignedUserName',
                'parentType',
                'parentId',
                'status',
                'createdAt',
                ['VALUE:', 'hasAttachment'],
            ],
            'leftJoins' => [['MeetingUser', 'usersLeftMiddle', ['usersLeftMiddle.meetingId:' => 'meeting.id']]],
            'whereClause' => [],
        ];

        $where = [
            'usersLeftMiddle.userId' => $entity->id,
        ];

        if ($entity->isPortal() && $entity->get('contactId')) {
            $selectParams['leftJoins'][] = ['contacts', 'contactsLeft'];
            $selectParams['distinct'] = true;
            $where['contactsLeftMiddle.contactId'] = $entity->get('contactId');
            $selectParams['whereClause'][] = [
                'OR' => $where
            ];
        } else {
            $selectParams['whereClause'][] = $where;
        }

        if (!empty($statusList)) {
            $selectParams['whereClause'][] = [
                'status' => $statusList
            ];
        }

        $selectManager->applyAccess($selectParams);

        $selectParams = $selectManager->mergeSelectParams($selectParams, $additinalSelectParams);

        $query = Select::fromRaw($selectParams);

        return $query;
    }

    protected function getActivitiesUserCallQuery(
        Entity $entity, array $statusList = [], $isHistory = false, $additinalSelectParams = null
    ) {
        $selectManager = $this->getSelectManagerFactory()->create('Call');

        $selectParams = [
            'from' => 'Call',
            'select' => [
                'id',
                'name',
                ['dateStart', 'dateStart'],
                ['dateEnd', 'dateEnd'],
                ['VALUE:', 'dateStartDate'],
                ['VALUE:', 'dateEndDate'],
                ['VALUE:Call', '_scope'],
                'assignedUserId',
                'assignedUserName',
                'parentType',
                'parentId',
                'status',
                'createdAt',
                ['VALUE:', 'hasAttachment'],
            ],
            'leftJoins' => [['CallUser', 'usersLeftMiddle', ['usersLeftMiddle.callId:' => 'call.id']]],
            'whereClause' => [],
        ];

        $where = [
            'usersLeftMiddle.userId' => $entity->id
        ];

        if ($entity->isPortal() && $entity->get('contactId')) {
            $selectParams['leftJoins'][] = ['contacts', 'contactsLeft'];
            $selectParams['distinct'] = true;
            $where['contactsLeftMiddle.contactId'] = $entity->get('contactId');
            $selectParams['whereClause'][] = [
                'OR' => $where
            ];
        } else {
            $selectParams['whereClause'][] = $where;
        }

        if (!empty($statusList)) {
            $selectParams['whereClause'][] = [
                'status' => $statusList
            ];
        }

        $selectManager->applyAccess($selectParams);

        $selectParams = $selectManager->mergeSelectParams($selectParams, $additinalSelectParams);

        $query = Select::fromRaw($selectParams);

        return $query;
    }

    protected function getActivitiesUserEmailQuery(
        Entity $entity, array $statusList = [], $isHistory = false, $additinalSelectParams = null
    ) {
        if ($entity->isPortal() && $entity->get('contactId')) {
            $contact = $this->getEntityManager()->getEntity('Contact', $entity->get('contactId'));
            if ($contact) {
                return $this->getActivitiesEmailQuery($contact, $statusList, $isHistory, $additinalSelectParams);
            }
        }

        $selectManager = $this->getSelectManagerFactory()->create('Email');

        $selectParams = [
            'from' => 'Email',
            'select' => [
                'id',
                'name',
                ['dateSent', 'dateStart'],
                ['VALUE:', 'dateEnd'],
                ['VALUE:', 'dateStartDate'],
                ['VALUE:', 'dateEndDate'],
                ['VALUE:Email', '_scope'],
                'assignedUserId',
                'assignedUserName',
                'parentType',
                'parentId',
                'status',
                'createdAt',
                'hasAttachment',
            ],
            'leftJoins' => [['EmailUser', 'usersLeftMiddle', ['usersLeftMiddle.emailId:' => 'email.id']]],
            'whereClause' => [
                'usersLeftMiddle.userId' => $entity->id,
            ],
        ];

        if (!empty($statusList)) {
            $selectParams['whereClause'][] = [
                'status' => $statusList
            ];
        }

        $selectManager->applyAccess($selectParams);

        $selectParams = $selectManager->mergeSelectParams($selectParams, $additinalSelectParams);

        $query = Select::fromRaw($selectParams);

        return $query;
    }

    protected function getActivitiesMeetingQuery(
        Entity $entity, array $statusList = [], $isHistory = false, $additinalSelectParams = null
    ) {
        $scope = $entity->getEntityType();
        $id = $entity->id;

        $methodName = 'getActivities' . $scope . 'MeetingQuery';

        if (method_exists($this, $methodName)) {
            return $this->$methodName($entity, $statusList, $isHistory, $additinalSelectParams);
        }

        $selectManager = $this->getSelectManagerFactory()->create('Meeting');

        $baseSelectParams = [
            'from' => 'Meeting',
            'select' => [
                'id',
                'name',
                ['dateStart', 'dateStart'],
                ['dateEnd', 'dateEnd'],
                ['dateStartDate', 'dateStartDate'],
                ['dateEndDate', 'dateEndDate'],
                ['VALUE:Meeting', '_scope'],
                'assignedUserId',
                'assignedUserName',
                'parentType',
                'parentId',
                'status',
                'createdAt',
                ['VALUE:', 'hasAttachment'],
            ],
            'whereClause' => [],
        ];

        if (!empty($statusList)) {
            $baseSelectParams['whereClause'][] = [
                'status' => $statusList
            ];
        }

        $selectParams = $baseSelectParams;

        if ($scope == 'Account') {
            $selectParams['whereClause'][] = [
                'OR' => [
                    [
                        'parentId' => $id,
                        'parentType' => 'Account'
                    ],
                    [
                        'accountId' => $id
                    ]
                ]
            ];
        } else if ($scope == 'Lead' && $entity->get('createdAccountId')) {
            $selectParams['whereClause'][] = [
                'OR' => [
                    [
                        'parentId' => $id,
                        'parentType' => 'Lead'
                    ],
                    [
                        'accountId' => $entity->get('createdAccountId')
                    ]
                ]
            ];
        } else {
            $selectParams['whereClause']['parentId'] = $id;
            $selectParams['whereClause']['parentType'] = $scope;
        }

        $selectManager->applyAccess($selectParams);

        $selectParams = $selectManager->mergeSelectParams($selectParams, $additinalSelectParams);

        $query = Select::fromRaw($selectParams);

        if (!$this->isPerson($scope)) {
            return $query;
        }

        $queryList = [$query];

        $link = null;

        switch ($scope) {
            case 'Contact':
                $link = 'contacts';
                break;
            case 'Lead':
                $link = 'leads';
                break;
            case 'User':
                $link = 'users';
                break;
        }

        if (!$link) {
            return $queryList;
        }

        $selectParams = $baseSelectParams;

        $selectManager->addJoin($link, $selectParams);

        $selectParams['whereClause'][$link .'.id'] = $id;
        $selectParams['whereClause'][] = [
            'OR' => [
                'parentType!=' => $scope,
                'parentId!=' => $id,
                'parentType' => null,
                'parentId' => null,
            ]
        ];

        $selectManager->applyAccess($selectParams);

        $selectParams = $selectManager->mergeSelectParams($selectParams, $additinalSelectParams);

        $query = Select::fromRaw($selectParams);

        $queryList[] = $query;

        return $queryList;
    }

    protected function getActivitiesCallQuery(
        Entity $entity, array $statusList = [], $isHistory = false, $additinalSelectParams = null
    ) {
        $scope = $entity->getEntityType();
        $id = $entity->id;

        $methodName = 'getActivities' .$scope . 'CallQuery';

        if (method_exists($this, $methodName)) {
            return $this->$methodName($entity, $statusList, $isHistory, $additinalSelectParams);
        }

        $selectManager = $this->getSelectManagerFactory()->create('Call');

        $baseSelectParams = [
            'from' => 'Call',
            'select' => [
                'id',
                'name',
                ['dateStart', 'dateStart'],
                ['dateEnd', 'dateEnd'],
                ['VALUE:', 'dateStartDate'],
                ['VALUE:', 'dateEndDate'],
                ['VALUE:Call', '_scope'],
                'assignedUserId',
                'assignedUserName',
                'parentType',
                'parentId',
                'status',
                'createdAt',
                ['VALUE:', 'hasAttachment'],
            ],
            'whereClause' => [],
        ];

        if (!empty($statusList)) {
            $baseSelectParams['whereClause'][] = [
                'status' => $statusList
            ];
        }

        $selectParams = $baseSelectParams;

        if ($scope == 'Account') {
            $selectParams['whereClause'][] = [
                'OR' => [
                    [
                        'parentId' => $id,
                        'parentType' => 'Account'
                    ],
                    [
                        'accountId' => $id
                    ]
                ]
            ];
        } else if ($scope == 'Lead' && $entity->get('createdAccountId')) {
            $selectParams['whereClause'][] = [
                'OR' => [
                    [
                        'parentId' => $id,
                        'parentType' => 'Lead'
                    ],
                    [
                        'accountId' => $entity->get('createdAccountId')
                    ]
                ]
            ];
        } else {
            $selectParams['whereClause']['parentId'] = $id;
            $selectParams['whereClause']['parentType'] = $scope;
        }

        $selectManager->applyAccess($selectParams);

        $selectParams = $selectManager->mergeSelectParams($selectParams, $additinalSelectParams);

        $query = Select::fromRaw($selectParams);

        if (!$this->isPerson($scope)) {
            return $query;
        }

        $queryList = [$query];

        $link = null;

        switch ($scope) {
            case 'Contact':
                $link = 'contacts';
                break;
            case 'Lead':
                $link = 'leads';
                break;
            case 'User':
                $link = 'users';
                break;
        }

        if (!$link) {
            return $queryList;
        }

        $selectParams = $baseSelectParams;
        $selectManager->addJoin($link, $selectParams);
        $selectParams['whereClause'][$link .'.id'] = $id;
        $selectParams['whereClause'][] = [
            'OR' => [
                'parentType!=' => $scope,
                'parentId!=' => $id,
                'parentType' => null,
                'parentId' => null,
            ]
        ];

        $selectManager->applyAccess($selectParams);

        $selectParams = $selectManager->mergeSelectParams($selectParams, $additinalSelectParams);

        $query = Select::fromRaw($selectParams);

        $queryList[] = $query;

        return $queryList;
    }

    protected function getActivitiesEmailQuery(
        Entity $entity, array $statusList = [], $isHistory = false, $additinalSelectParams = null
    ) {
        $scope = $entity->getEntityType();
        $id = $entity->id;

        $methodName = 'getActivities' .$scope . 'EmailQuery';
        if (method_exists($this, $methodName)) {
            return $this->$methodName($entity, $statusList, $isHistory, $additinalSelectParams);
        }

        $selectManager = $this->getSelectManagerFactory()->create('Email');

        $baseSelectParams = [
            'from' => 'Email',
            'select' => [
                'id',
                'name',
                ['dateSent', 'dateStart'],
                ['VALUE:', 'dateEnd'],
                ['VALUE:', 'dateStartDate'],
                ['VALUE:', 'dateEndDate'],
                ['VALUE:Email', '_scope'],
                'assignedUserId',
                'assignedUserName',
                'parentType',
                'parentId',
                'status',
                'createdAt',
                'hasAttachment'
            ],
            'joins' => [],
            'leftJoins' => [],
            'whereClause' => [],
        ];

        if (!empty($statusList)) {
            $baseSelectParams['whereClause'][] = [
                'status' => $statusList
            ];
        }

        $selectParams = $baseSelectParams;

        if ($scope == 'Account') {
            $selectParams['whereClause'][] = [
                'OR' => [
                    [
                        'parentId' => $id,
                        'parentType' => 'Account'
                    ],
                    [
                        'accountId' => $id
                    ]
                ]
            ];
        } else if ($scope == 'Lead' && $entity->get('createdAccountId')) {
            $selectParams['whereClause'][] = [
                'OR' => [
                    [
                        'parentId' => $id,
                        'parentType' => 'Lead'
                    ],
                    [
                        'accountId' => $entity->get('createdAccountId')
                    ]
                ]
            ];
        } else {
            $selectParams['whereClause']['parentId'] = $id;
            $selectParams['whereClause']['parentType'] = $scope;
        }

        $selectManager->applyAccess($selectParams);

        $selectParams = $selectManager->mergeSelectParams($selectParams, $additinalSelectParams);

        $query = Select::fromRaw($selectParams);

        if (!$this->isPerson($scope) && !$this->isCompany($scope)) {
            return $query;
        }

        $queryList = [$query];

        $selectParams = $baseSelectParams;

        $selectParams['leftJoins'][] = [
            'EntityEmailAddress',
            'eea',
            [
                'eea.emailAddressId:' => 'fromEmailAddressId',
                'eea.entityType' => $scope,
                'eea.deleted' => false,
            ],
        ];

        $selectParams['whereClause'][] = [
            'OR' => [
                'parentType!=' => $scope,
                'parentId!=' => $id,
                'parentType' => null,
                'parentId' => null,
            ],
        ];

        $selectParams['whereClause']['eea.entityId'] = $id;

        $selectManager->applyAccess($selectParams);

        $selectParams = $selectManager->mergeSelectParams($selectParams, $additinalSelectParams);

        $queryList[] = Select::fromRaw($selectParams);

        $selectParams = $baseSelectParams;

        $selectParams['leftJoins'][] = [
            'EmailEmailAddress',
            'em',
            [
                'em.emailId:' => 'id',
                'em.deleted' => false,
            ],
        ];

        $selectParams['leftJoins'][] = [
            'EntityEmailAddress',
            'eea',
            [
                'eea.emailAddressId:' => 'em.emailAddressId',
                'eea.entityType' => $scope,
                'eea.deleted' => 0,
            ],
        ];

        $selectParams['whereClause'][] = [
            'OR' => [
                'parentType!=' => $scope,
                'parentId!=' => $id,
                'parentType' => null,
                'parentId' => null,
            ],
        ];

        $selectParams['whereClause']['eea.entityId'] = $id;
        $selectManager->applyAccess($selectParams);

        $selectParams = $selectManager->mergeSelectParams($selectParams, $additinalSelectParams);

        $queryList[] = Select::fromRaw($selectParams);

        return $queryList;
    }

    protected function getResultFromQueryParts($parts, $scope, $id, $params)
    {
        if (empty($parts)) {
            return [
                'list' => [],
                'total' => 0,
            ];
        }

        $pdo = $this->getEntityManager()->getPDO();

        $onlyScope = false;
        if (!empty($params['scope'])) {
            $onlyScope = $params['scope'];
        }

        $maxSize = $params['maxSize'] ?? null;


        $queryList = [];

        if (!$onlyScope) {
            foreach ($parts as $part) {
                if (is_array($part)) {
                    $queryList = array_merge($queryList, $part);
                } else {
                    $queryList[] = $part;
                }
            }
        } else {
            $part = $parts[$onlyScope];

            if (is_array($part)) {
                $queryList = array_merge($queryList, $part);
            } else {
                $queryList[] = $part;
            }
        }

        $maxSizeQ = $maxSize;

        $offset = $params['offset'] ?? 0;

        if (!$onlyScope && $scope === 'User') {
            // optimizing sub-queries

            $newQueryList = [];

            foreach ($queryList as $query) {
                $subBuilder = $this->entityManager->getQueryBuilder()->clone($query);

                if ($maxSize) {
                    $subBuilder->limit(0, $offset + $maxSize + 1);
                }

                // order by dateStart
                $subBuilder->order(3, 'DESC');

                $newQueryList[] = $subBuilder->build();
            }

            $queryList = $newQueryList;
        }

        $builder = $this->entityManager->getQueryBuilder()
            ->union();

        foreach ($queryList as $query) {
            $builder->query($query);
        }

        $sql = $this->entityManager->getQueryComposer()->compose($builder->build());

        if ($scope !== 'User') {
            $sqlCount = "SELECT COUNT(*) AS 'count' FROM ({$sql}) AS c";

            $sth = $pdo->prepare($sqlCount);
            $sth->execute();

            $row = $sth->fetch(PDO::FETCH_ASSOC);

            $totalCount = $row['count'];
        }


        $builder->order('dateStart', 'DESC');

        if ($scope === 'User') {
            $maxSizeQ++;
        } else {
            $builder->order('createdAt', 'DESC');
        }

        if ($maxSize) {
            $builder->limit($offset, $maxSizeQ);
        }


        $unionQuery = $builder->build();

        $sth = $this->entityManager->getQueryExecutor()->run($unionQuery);

        $rowList = $sth->fetchAll(PDO::FETCH_ASSOC);

        $boolAttributeList = ['hasAttachment'];

        $list = [];

        foreach ($rowList as $row) {
            foreach ($boolAttributeList as $attribute) {
                if (!array_key_exists($attribute, $row)) continue;
                $row[$attribute] = $row[$attribute] == '1' ? true : false;
            }

            $list[] = $row;
        }

        if ($scope === 'User') {
            if ($maxSize && count($list) > $maxSize) {
                $totalCount = -1;
                unset($list[count($list) - 1]);
            } else {
                $totalCount = -2;
            }
        }

        return [
            'list' => $list,
            'total' => $totalCount,
        ];
    }

    protected function accessCheck($entity)
    {
        if ($entity->getEntityType() == 'User') {
            if (!$this->getAcl()->checkUserPermission($entity, 'user')) {
                throw new Forbidden();
            }

        } else {
            if (!$this->getAcl()->check($entity, 'read')) {
                throw new Forbidden();
            }
        }
    }

    public function findActivitiyEntityType($scope, $id, $entityType, $isHistory = false, $params = [])
    {
        if (!$this->getAcl()->checkScope($entityType)) {
            throw new Forbidden();
        }

        $entity = $this->getEntityManager()->getEntity($scope, $id);
        if (!$entity) throw new NotFound();
        $this->accessCheck($entity);

        if (!$this->getMetadata()->get(['scopes', $entityType, 'activity'])) {
            throw new Error('Entity \'' . $entityType . '\' is not an activity');
        }

        if (!$isHistory) {
            $statusList = $this->getMetadata()->get(['scopes', $entityType, 'activityStatusList'], ['Planned']);
        } else {
            $statusList = $this->getMetadata()->get(['scopes', $entityType, 'historyStatusList'], ['Held', 'Not Held']);
        }

        $service = $this->getServiceFactory()->create($entityType);
        $selectManager = $this->getSelectManagerFactory()->create($entityType);


        if ($entityType === 'Email') {
            if ($params['orderBy'] ?? null === 'dateStart') {
                $params['orderBy'] = 'dateSent';
            }
        }

        $selectParams = $selectManager->getSelectParams($params, false, true);

        $selectAttributeList = $service->getSelectAttributeList($params);
        if ($selectAttributeList) {
            $selectParams['select'] = $selectAttributeList;
        }

        $offset = $selectParams['offset'];
        $limit = $selectParams['limit'];

        $orderBy = null;
        $order = null;

        if (!empty($selectParams['orderBy'])) {
            $order = $selectParams['order'] ?? null;
            $orderBy = $selectParams['orderBy'];
        }

        unset($selectParams['offset']);
        unset($selectParams['limit']);
        unset($selectParams['order']);
        unset($selectParams['orderBy']);

        $query = $this->getActivitiesQuery($entity, $entityType, $statusList, $isHistory, $selectParams);

        $seed = $this->getEntityManager()->getEntity($entityType);

        $sqlBase = $this->entityManager->getQueryComposer()->compose($query);

        $builder = $this->entityManager->getQueryBuilder()->clone($query);

        if ($orderBy) {
            $builder->order($orderBy, $order);
        }

        $builder->limit($offset, $limit);

        $sql = $this->entityManager->getQueryComposer()->compose($builder->build());

        $collection = $this->getEntityManager()->getRepository($entityType)->findBySql($sql);

        foreach ($collection as $e) {
            $service->loadAdditionalFieldsForList($e);
            if (!empty($params['loadAdditionalFields'])) {
                $service->loadAdditionalFields($e);
            }
            if (!empty($selectAttributeList)) {
                $service->loadLinkMultipleFieldsForList($e, $selectAttributeList);
            }
            $service->prepareEntityForOutput($e);
        }

        $pdo = $this->getEntityManager()->getPDO();

        $sqlTotal = "SELECT COUNT(*) AS 'count' FROM ({$sqlBase}) AS c";

        $sth = $pdo->prepare($sqlTotal);

        $sth->execute();
        $row = $sth->fetch(\PDO::FETCH_ASSOC);
        $total = $row['count'];

        return (object) [
            'total' => $total,
            'collection' => $collection,
        ];
    }

    public function getActivities(string $scope, string $id, array $params = [])
    {
        $entity = $this->getEntityManager()->getEntity($scope, $id);
        if (!$entity) {
            throw new NotFound();
        }

        $this->accessCheck($entity);

        $fetchAll = empty($params['scope']);

        if (!$fetchAll) {
            if (!$this->getMetadata()->get(['scopes', $params['scope'], 'activity'])) {
                throw new Error('Entity \'' . $params['scope'] . '\' is not an activity');
            }
        }

        $parts = [];

        $entityTypeList = $this->getConfig()->get('activitiesEntityList', ['Meeting', 'Call']);

        foreach ($entityTypeList as $entityType) {
            if (!$fetchAll && $params['scope'] !== $entityType) continue;
            if (!$this->getAcl()->checkScope($entityType)) continue;
            if (!$this->getMetadata()->get('scopes.' . $entityType . '.activity')) continue;

            $statusList = $this->getMetadata()->get(['scopes', $entityType, 'activityStatusList'], ['Planned']);
            $parts[$entityType] = $this->getActivitiesQuery($entity, $entityType, $statusList, false);
        }

        return $this->getResultFromQueryParts($parts, $scope, $id, $params);
    }

    public function getHistory($scope, $id, $params = [])
    {
        $entity = $this->getEntityManager()->getEntity($scope, $id);
        if (!$entity) {
            throw new NotFound();
        }

        $this->accessCheck($entity);

        $fetchAll = empty($params['scope']);

        if (!$fetchAll) {
            if (!$this->getMetadata()->get(['scopes', $params['scope'], 'activity'])) {
                throw new Error('Entity \'' . $params['scope'] . '\' is not an activity');
            }
        }

        $parts = [];
        $entityTypeList = $this->getConfig()->get('historyEntityList', ['Meeting', 'Call', 'Email']);

        foreach ($entityTypeList as $entityType) {
            if (!$fetchAll && $params['scope'] !== $entityType) continue;
            if (!$this->getAcl()->checkScope($entityType)) continue;
            if (!$this->getMetadata()->get('scopes.' . $entityType . '.activity')) continue;

            $statusList = $this->getMetadata()->get(['scopes', $entityType, 'historyStatusList'], ['Held', 'Not Held']);
            $parts[$entityType] = $this->getActivitiesQuery($entity, $entityType, $statusList, true);
        }

        $result = $this->getResultFromQueryParts($parts, $scope, $id, $params);

        foreach ($result['list'] as &$item) {
            if ($item['_scope'] == 'Email') {
                $item['dateSent'] = $item['dateStart'];
            }
        }

        return $result;
    }

    protected function getCalendarMeetingQuery($userId, $from, $to, $skipAcl)
    {
        $selectManager = $this->getSelectManagerFactory()->create('Meeting');

        $select = [
            ['VALUE:Meeting', 'scope'],
            'id',
            'name',
            ['dateStart', 'dateStart'],
            ['dateEnd', 'dateEnd'],
            'status',
            ['dateStartDate', 'dateStartDate'],
            ['dateEndDate', 'dateEndDate'],
            'parentType',
            'parentId',
            'createdAt',
        ];

        $seed = $this->getEntityManager()->getEntity('Meeting');

        foreach ($this->getMetadata()->get(['app', 'calendar', 'additionalAttributeList']) ?? [] as $attribute) {
            $select[] = $seed->hasAttribute($attribute) ? [$attribute, $attribute] : ['VALUE:', $attribute];
        }

        $selectParams = [
            'from' => 'Meeting',
            'select' => $select,
            'leftJoins' => ['users'],
            'whereClause' => [
                'usersMiddle.userId' => $userId,
                [
                    'OR' => [
                        [
                            'dateStart>=' => $from,
                            'dateStart<' => $to,
                        ],
                        [
                            'dateEnd>=' => $from,
                            'dateEnd<' => $to,
                        ],
                        [
                            'dateStart<=' => $from,
                            'dateEnd>=' => $to,
                        ],
                    ],
                ],
                'usersMiddle.status!=' => 'Declined',
            ],
        ];

        if (!$skipAcl) {
            $selectManager->applyAccess($selectParams);
        }

        return Select::fromRaw($selectParams);
    }

    protected function getCalendarCallQuery($userId, $from, $to, $skipAcl)
    {
        $selectManager = $this->getSelectManagerFactory()->create('Call');

        $select = [
            ['VALUE:Call', 'scope'],
            'id',
            'name',
            ['dateStart', 'dateStart'],
            ['dateEnd', 'dateEnd'],
            'status',
            ['VALUE:', 'dateStartDate'],
            ['VALUE:', 'dateEndDate'],
            'parentType',
            'parentId',
            'createdAt',
        ];

        $seed = $this->getEntityManager()->getEntity('Call');

        foreach ($this->getMetadata()->get(['app', 'calendar', 'additionalAttributeList']) ?? [] as $attribute) {
            $select[] = $seed->hasAttribute($attribute) ? [$attribute, $attribute] : ['VALUE:', $attribute];
        }

        $selectParams = [
            'from' => 'Call',
            'select' => $select,
            'leftJoins' => ['users'],
            'whereClause' => [
                'usersMiddle.userId' => $userId,
                [
                    'OR' => [
                        [
                            'dateStart>=' => $from,
                            'dateStart<' => $to,
                        ],
                        [
                            'dateEnd>=' => $from,
                            'dateEnd<' => $to,
                        ],
                        [
                            'dateStart<=' => $from,
                            'dateEnd>=' => $to,
                        ],
                    ]
                ],
                'usersMiddle.status!=' => 'Declined',
            ],
        ];

        if (!$skipAcl) {
            $selectManager->applyAccess($selectParams);
        }

        return Select::fromRaw($selectParams);
    }

    protected function getCalendarTaskQuery($userId, $from, $to, $skipAcl = false)
    {
        $selectManager = $this->getSelectManagerFactory()->create('Task');

        $select = [
                ['VALUE:Task', 'scope'],
                'id',
                'name',
                ['dateStart', 'dateStart'],
                ['dateEnd', 'dateEnd'],
                'status',
                ['dateStartDate', 'dateStartDate'],
                ['dateEndDate', 'dateEndDate'],
                'parentType',
                'parentId',
                'createdAt',
        ];

        $seed = $this->getEntityManager()->getEntity('Task');

        foreach ($this->getMetadata()->get(['app', 'calendar', 'additionalAttributeList']) ?? [] as $attribute) {
            $select[] = $seed->hasAttribute($attribute) ? [$attribute, $attribute] : ['VALUE:', $attribute];
        }

        $selectParams = [
            'from' => 'Task',
            'select' => $select,
            'whereClause' => [
                [
                    'OR' => [
                        [
                            'dateEnd' => null,
                            'dateStart>=' => $from,
                            'dateStart<' => $to,
                        ],
                        [
                            'dateEnd>=' => $from,
                            'dateEnd<' => $to,
                        ],
                        [
                            'dateEndDate!=' => null,
                            'dateEndDate>=' => $from,
                            'dateEndDate<' => $to,
                        ]
                    ]
                ]
            ]
        ];

        if ($this->getMetadata()->get(['entityDefs', 'Task', 'fields', 'assignedUsers', 'type']) === 'linkMultiple') {
            $selectManager->setDistinct(true, $selectParams);
            $selectManager->addLeftJoin(['assignedUsers', 'assignedUsers'], $selectParams);
            $selectParams['whereClause'][] = ['assignedUsers.id' => $userId];
        } else {
            $selectParams['whereClause'][] = ['assignedUserId' => $userId];
        }

        if (!$skipAcl) {
            $selectManager->applyAccess($selectParams);
        }

        return Select::fromRaw($selectParams);
    }

    protected function getCalendarSelectParams($scope, $userId, $from, $to, $skipAcl = false)
    {
        $selectManager = $this->getSelectManagerFactory()->create($scope);

        $seed = $this->getEntityManager()->getEntity($scope);

        $select = [
            ['VALUE:' . $scope, 'scope'],
            'id',
            'name',
            ['dateStart', 'dateStart'],
            ['dateEnd', 'dateEnd'],
            ($seed->hasAttribute('status') ? ['status', 'status'] : ['VALUE:', 'status']),
            ($seed->hasAttribute('dateStartDate') ? ['dateStartDate', 'dateStartDate'] : ['VALUE:', 'dateStartDate']),
            ($seed->hasAttribute('dateEndDate') ? ['dateEndDate', 'dateEndDate'] : ['VALUE:', 'dateEndDate']),
            ($seed->hasAttribute('parentType') ? ['parentType', 'parentType'] : ['VALUE:', 'parentType']),
            ($seed->hasAttribute('parentId') ? ['parentId', 'parentId'] : ['VALUE:', 'parentId']),
            'createdAt',
        ];

        foreach ($this->getMetadata()->get(['app', 'calendar', 'additionalAttributeList']) ?? [] as $attribute) {
            $select[] = $seed->hasAttribute($attribute) ? [$attribute, $attribute] : ['VALUE:', $attribute];
        }

        $wherePart = [
            'assignedUserId' => $userId,
        ];

        if ($seed->hasRelation('users')) {
            $wherePart['usersMiddle.userId'] = $userId;
        }

        if ($seed->hasRelation('assignedUsers')) {
            $wherePart['assignedUsersMiddle.userId'] = $userId;
        }

        $selectParams = [
            'from' => $scope,
            'select' => $select,
            'leftJoins' => [],
            'whereClause' => [
                'OR' => $wherePart,
                [
                    'OR' => [
                        [
                            'dateEnd' => null,
                            'dateStart>=' => $from,
                            'dateStart<' => $to
                        ],
                        [
                            'dateStart>=' => $from,
                            'dateStart<' => $to
                        ],
                        [
                            'dateEnd>=' => $from,
                            'dateEnd<' => $to
                        ],
                        [
                            'dateStart<=' => $from,
                            'dateEnd>=' => $to
                        ],
                        [
                            'dateEndDate!=' => null,
                            'dateEndDate>=' => $from,
                            'dateEndDate<' => $to
                        ]
                    ]
                ]
            ]
        ];

        if ($seed->hasRelation('users')) {
            $selectParams['leftJoins'][] = 'users';
        }

        if ($seed->hasRelation('assignedUsers')) {
            $selectManager->setDistinct(true, $selectParams);
            $selectParams['leftJoins'][] = 'assignedUsers';
        }

        if (!$skipAcl) {
            $selectManager->applyAccess($selectParams);
        }

        return $selectParams;
    }

    protected function getCalendarQuery($scope, $userId, $from, $to, $skipAcl = false)
    {
        $selectManager = $this->getSelectManagerFactory()->create($scope);

        if (method_exists($selectManager, 'getCalendarSelectParams')) {
            $selectParams = $selectManager->getCalendarSelectParams($userId, $from, $to, $skipAcl);

            $selectParams['from'] = $scope;

            return Select::fromRaw($selectParams);
        }

        $methodName = 'getCalendar' . $scope . 'Query';

        if (method_exists($this, $methodName)) {
            return $this->$methodName($userId, $from, $to, $skipAcl);
        }

        $selectParams = $this->getCalendarSelectParams($scope, $userId, $from, $to, $skipAcl);

        return Select::fromRaw($selectParams);
    }

    protected function getActivitiesQuery(
        Entity $entity, $scope, array $statusList = [], $isHistory = false, $additinalSelectParams = null
    ) {
        $serviceName = 'Activities' . $entity->getEntityType();
        if ($this->getServiceFactory()->checkExists($serviceName)) {
            $service = $this->getServiceFactory()->create($serviceName);

            $methodName = 'getActivities' . $scope . 'Query';

            if (method_exists($service, $methodName)) {
                return $service->$methodName($entity, $statusList, $isHistory, $additinalSelectParams);
            }
        }

        $selectManager = $this->getSelectManagerFactory()->create($scope);

        if (method_exists($selectManager, 'getActivitiesSelectParams')) {
            $selectParams = $selectManager->getActivitiesSelectParams($entity, $statusList, $isHistory);

            $selectParams = $selectManager->mergeSelectParams($selectParams, $additinalSelectParams);

            $selectParams['from'] = $scope;

            return Select::fromRaw($selectParams);
        }

        $methodName = 'getActivities' . $scope . 'Query';

        if (method_exists($this, $methodName)) {
            return $this->$methodName($entity, $statusList, $isHistory, $additinalSelectParams);
        }

        $selectParams = $this->getActivitiesSelectParams($entity, $scope, $statusList, $isHistory);

        $selectParams = $selectManager->mergeSelectParams($selectParams, $additinalSelectParams);

        return Select::fromRaw($selectParams);
    }

    protected function getActivitiesSelectParams(Entity $entity, $scope, array $statusList = [], $isHistory)
    {
        $selectManager = $this->getSelectManagerFactory()->create($scope);

        $seed = $this->getEntityManager()->getEntity($scope);

        $select = [
            'id',
            'name',
            ($seed->hasAttribute('dateStart') ? ['dateStart', 'dateStart'] : ['VALUE:', 'dateStart']),
            ($seed->hasAttribute('dateEnd') ? ['dateEnd', 'dateEnd'] : ['VALUE:', 'dateEnd']),
            ($seed->hasAttribute('dateStartDate') ? ['dateStartDate', 'dateStartDate'] : ['VALUE:', 'dateStartDate']),
            ($seed->hasAttribute('dateEndDate') ? ['dateEndDate', 'dateEndDate'] : ['VALUE:', 'dateEndDate']),
            ['VALUE:' . $scope, '_scope'],
            ($seed->hasAttribute('assignedUserId') ? ['assignedUserId', 'assignedUserId'] : ['VALUE:', 'assignedUserId']),
            ($seed->hasAttribute('assignedUserName') ? ['assignedUserName', 'assignedUserName'] :
                ['VALUE:', 'assignedUserName']),
            ($seed->hasAttribute('parentType') ? ['parentType', 'parentType'] : ['VALUE:', 'parentType']),
            ($seed->hasAttribute('parentId') ? ['parentId', 'parentId'] : ['VALUE:', 'parentId']),
            'status',
            'createdAt',
            ['VALUE:', 'hasAttachment']
        ];

        $selectParams = $selectManager->getEmptySelectParams();

        $selectParams['select'] = $select;

        if ($entity->getEntityType() === 'User') {
            $selectParams['whereClause'][] = [
                'assignedUserId' => $entity->id
            ];
        } else {
            $selectParams['whereClause'][] = [
                'parentId' => $entity->id,
                'parentType' => $entity->getEntityType()
            ];
        }

        $selectParams['whereClause'][] = [
            'status' => $statusList
        ];

        $selectManager->applyAccess($selectParams);

        return $selectParams;
    }

    public function getUsersTimeline($userIdList, $from, $to, $scopeList = null)
    {
        $brScopeList = $this->getConfig()->get('busyRangesEntityList') ?? ['Meeting', 'Call'];
        if ($scopeList) {
            foreach ($scopeList as $s) {
                if (!in_array($s, $brScopeList)) {
                    $brScopeList[] = $s;
                }
            }
        }

        $resultData = (object) [];
        foreach ($userIdList as $userId) {
            $userData = (object) [
                'eventList' => [],
                'busyRangeList' => []
            ];
            try {
                $userData->eventList = $this->getEventList($userId, $from, $to, $scopeList);
                $userData->busyRangeList = $this->getBusyRangeList($userId, $from, $to, $brScopeList, $userData->eventList);
            } catch (\Exception $e) {
                if ($e instanceof Forbidden) {
                    continue;
                }
                throw new \Exception($e->getMessage(), $e->getCode(), $e);
            }

            $resultData->$userId = $userData;
        }

        return $resultData;
    }

    public function getBusyRanges(
        array $userIdList, string $from, string $to,
        ?string $entityType = null, ?string $ignoreId = null, ?array $scopeList = null)
    {
        $scopeList = $this->getConfig()->get('busyRangesEntityList') ?? ['Meeting', 'Call'];

        if ($entityType) {
            if (!$this->getAcl()->check($entityType)) throw new Forbidden();
            if (!in_array($entityType, $scopeList)) {
                $scopeList[] = $entityType;
            }
        }

        try {
            $dtFrom = new \DateTime($from);
            $dtTo = new \DateTime($to);
            $diff = $dtTo->diff($dtFrom, true);
            if ($diff->days > $this->getConfig()->get('busyRangesMaxRange', self::BUSY_RANGES_MAX_RANGE_DAYS)) {
                return [];
            }

        } catch (\Exception $e) {
            throw new Error("BusyRanges: Bad date range.");
        }

        $ignoreList = null;

        if ($entityType && $ignoreId) {
            $ignoreList = [
                [
                    'id' => $ignoreId,
                    'scope' => $entityType,
                ]
            ];
        }

        $resultData = (object) [];

        foreach ($userIdList as $userId) {
            try {
                $busyRangeList = $this->getBusyRangeList($userId, $from, $to, $scopeList, $ignoreList);
            } catch (\Exception $e) {
                if ($e instanceof Forbidden) {
                    continue;
                }
                throw new \Exception($e->getMessage(), $e->getCode(), $e);
            }

            $resultData->$userId = $busyRangeList;
        }
        return $resultData;
    }

    public function getEventsForUsers($userIdList, $from, $to, $scopeList = null)
    {
        return $this->getUsersEventList($userIdList, $from, $to, $scopeList);
    }

    public function getUsersEventList($userIdList, $from, $to, $scopeList = null)
    {
        $resultList = [];
        foreach ($userIdList as $userId) {
            try {
                $userResultList = $this->getEvents($userId, $from, $to, $scopeList);
            } catch (\Exception $e) {
                if ($e instanceof Forbidden) {
                    continue;
                }
                throw new \Exception($e->getMessage(), $e->getCode(), $e);
            }
            foreach ($userResultList as $item) {
                $item['userId'] = $userId;
                $resultList[] = $item;
            }
        }
        return $resultList;
    }

    public function getEventsForTeams($teamIdList, $from, $to, $scopeList = null)
    {
        return $this->getTeamsEventList($teamIdList, $from, $to, $scopeList);
    }

    public function getTeamsEventList($teamIdList, $from, $to, $scopeList = null)
    {
        if ($this->getAcl()->get('userPermission') === 'no') {
            throw new Forbidden("User Permission not allowing to view calendars of other users.");
        }
        if ($this->getAcl()->get('userPermission') === 'team') {
            $userTeamIdList = $this->getUser()->getLinkMultipleIdList('teams');
            foreach ($teamIdList as $teamId) {
                if (!in_array($teamId, $userTeamIdList)) {
                    throw new Forbidden("User Permission not allowing to view calendars of other teams.");
                }
            }
        }

        $userIdList = [];

        $userList = $this->getEntityManager()->getRepository('User')
            ->select(['id', 'name'])
            ->leftJoin('teams')
            ->where([
                'isActive' => true,
                'teamsMiddle.teamId' => $teamIdList
            ])
            ->distinct()
            ->find();

        $userNames = (object) [];

        foreach ($userList as $user) {
            $userIdList[] = $user->id;
            $userNames->{$user->id} = $user->get('name');
        }

        $eventList = [];
        foreach ($userIdList as $userId) {
            $userEventList = $this->getEventList($userId, $from, $to, $scopeList);
            foreach ($userEventList as $event) {
                foreach ($eventList as &$e) {
                    if ($e['scope'] == $event['scope'] && $e['id'] == $event['id']) {
                        $e['userIdList'][] = $userId;
                        continue 2;
                    }
                }

                $event['userIdList'] = [$userId];
                $eventList[] = $event;
            }
        }

        foreach ($eventList as &$event) {
            $eventUserNames = (object) [];
            foreach ($event['userIdList'] as $userId) {
                $eventUserNames->$userId = $userNames->$userId;
            }
            $event['userNameMap'] = $eventUserNames;
        }


        return $eventList;
    }

    public function getBusyRangeList($userId, $from, $to, $scopeList = null, ?array $ignoreEventList = null)
    {
        $rangeList = [];

        $eventList = $this->getEventList($userId, $from, $to, $scopeList, true);

        $ignoreHash = (object) [];
        if ($ignoreEventList) {
            foreach ($ignoreEventList as $item) {
                $ignoreHash->{$item['id']} = true;
            }
        }

        $canceledStatusList = $this->getMetadata()->get('app.calendar.canceledStatusList') ?? [];

        foreach ($eventList as $i => $item) {
            $eventList[$i] = (object) $item;
        }
        foreach ($eventList as $event) {
            if (empty($event->dateStart) || empty($event->dateEnd)) continue;
            if (in_array($event->status ?? null, $canceledStatusList)) continue;

            if (isset($ignoreHash->{$event->id})) continue;

            try {
                $start = new \DateTime($event->dateStart);
                $end = new \DateTime($event->dateEnd);

                foreach ($rangeList as &$range) {
                    if (
                        $start->getTimestamp() < $range->start->getTimestamp()
                        &&
                        $end->getTimestamp() > $range->end->getTimestamp()
                    ) {
                        $range->dateStart = $event->dateStart;
                        $range->start = $start;
                        $range->dateEnd = $event->dateEnd;
                        $range->end = $end;
                        continue 2;
                    }

                    if (
                        $start->getTimestamp() < $range->start->getTimestamp()
                        &&
                        $end->getTimestamp() > $range->start->getTimestamp()
                    ) {
                        $range->dateStart = $event->dateStart;
                        $range->start = $start;
                        if ($end->getTimestamp() > $range->end->getTimestamp()) {
                            $range->dateEnd = $event->dateEnd;
                            $range->end = $end;
                        }
                        continue 2;
                    }

                    if (
                        $start->getTimestamp() < $range->end->getTimestamp()
                        &&
                        $end->getTimestamp() > $range->end->getTimestamp()
                    ) {
                        $range->dateEnd = $event->dateEnd;
                        $range->end = $end;
                        if ($start->getTimestamp() < $range->start->getTimestamp()) {
                            $range->dateStart = $event->dateStart;
                            $range->start = $start;
                        }
                        continue 2;
                    }
                }

                $busyItem = (object) [
                    'dateStart' => $event->dateStart,
                    'dateEnd' => $event->dateEnd,
                    'start' => $start,
                    'end' => $end
                ];

                $rangeList[] = $busyItem;
            } catch (\Exception $e) {}
        }

        foreach ($rangeList as &$item) {
            unset($item->start);
            unset($item->end);
        }

        return $rangeList;
    }

    public function getEventList($userId, $from, $to, $scopeList = null, $skipAcl = false)
    {
        $user = $this->getEntityManager()->getEntity('User', $userId);

        if (!$user) {
            throw new NotFound();
        }

        $this->accessCheck($user);

        $pdo = $this->getPDO();

        $calendarEntityList = $this->getConfig()->get('calendarEntityList', []);

        if (is_null($scopeList)) {
            $scopeList = $calendarEntityList;
        }

        $queryList = [];

        foreach ($scopeList as $scope) {
            if (!in_array($scope, $calendarEntityList)) {
                continue;
            }
            if (!$this->getAcl()->checkScope($scope)) {
                continue;
            }
            if (!$this->getMetadata()->get(['scopes', $scope, 'calendar'])) {
                continue;
            }

            $subItem = $this->getCalendarQuery($scope, $userId, $from, $to, $skipAcl);

            if (!is_array($subItem)) {
                $subItem = [$subItem];
            }

            $queryList = array_merge($queryList, $subItem);
        }

        if (empty($queryList)) {
            return [];
        }

        $builder = $this->entityManager->getQueryBuilder()
            ->union();

        foreach ($queryList as $query) {
            $builder->query($query);
        }

        $unionQuery = $builder->build();

        $sth = $this->entityManager->getQueryExecutor()->run($unionQuery);

        $rowList = $sth->fetchAll(PDO::FETCH_ASSOC);

        return $rowList;
    }

    public function getEvents($userId, $from, $to, $scopeList = null, $skipAcl = false)
    {
        return $this->getEventList($userId, $from, $to, $scopeList, $skipAcl);
    }

    public function removeReminder(string $id)
    {
        $builder = $this->getEntityManager()->getQueryBuilder()
            ->delete()
            ->from('Reminder')
            ->where([
                'id' => $id,
            ]);

        if (!$this->getUser()->isAdmin()) {
            $builder->where([
                'userId' => $this->getUser()->id,
            ]);
        }

        $deleteQuery = $builder->build();

        $this->getEntityManager()->getQueryExecutor()->run($deleteQuery);

        return true;
    }

    public function getPopupNotifications($userId)
    {
        $pdo = $this->getPDO();

        $dt = new \DateTime();

        $pastHours = $this->getConfig()->get('reminderPastHours', self::REMINDER_PAST_HOURS);

        $now = $dt->format('Y-m-d H:i:s');
        $nowShifted = $dt->sub(new \DateInterval('PT'.strval($pastHours).'H'))->format('Y-m-d H:i:s');

        $sql = "
            SELECT id, entity_type AS 'entityType', entity_id AS 'entityId'
            FROM `reminder`
            WHERE
                `type` = 'Popup' AND
                `user_id` = ".$pdo->quote($userId)." AND
                `remind_at` <= '{$now}' AND
                `start_at` > '{$nowShifted}' AND
                `deleted` = 0
        ";

        $sth = $pdo->prepare($sql);
        $sth->execute();
        $rowList = $sth->fetchAll(PDO::FETCH_ASSOC);

        $resultList = [];
        foreach ($rowList as $row) {
            $reminderId = $row['id'];
            $entityType = $row['entityType'];
            $entityId = $row['entityId'];

            $entity = $this->getEntityManager()->getEntity($entityType, $entityId);
            $data = null;

            if ($entity) {
                if ($entity->hasLinkMultipleField('users')) {
                    $entity->loadLinkMultipleField('users', ['status' => 'acceptanceStatus']);
                    $status = $entity->getLinkMultipleColumn('users', 'status', $userId);
                    if ($status === 'Declined') {
                        $this->removeReminder($reminderId);
                        continue;
                    }
                }

                $dateAttribute = 'dateStart';
                if ($entityType === 'Task') {
                    $dateAttribute = 'dateEnd';
                }

                $data = [
                    'id' => $entity->id,
                    'entityType' => $entityType,
                    $dateAttribute => $entity->get($dateAttribute),
                    'name' => $entity->get('name')
                ];
            } else {
                continue;
            }
            $resultList[] = [
                'id' => $reminderId,
                'data' => $data
            ];

        }
        return $resultList;
    }

    public function getUpcomingActivities($userId, $params = [], $entityTypeList = null, $futureDays = null)
    {
        $user = $this->getEntityManager()->getEntity('User', $userId);

        $this->accessCheck($user);

        if (!$entityTypeList) {
            $entityTypeList = $this->getConfig()->get('activitiesEntityList', []);
        }

        if (is_null($futureDays)) {
            $futureDays = $this->getConfig()->get('activitiesUpcomingFutureDays', self::UPCOMING_ACTIVITIES_FUTURE_DAYS);
        }
        $beforeString = (new \DateTime())->modify('+' . $futureDays . ' days')->format('Y-m-d H:i:s');

        $upcomingTaskFutureDays = $this->getConfig()->get(
            'activitiesUpcomingTaskFutureDays', self::UPCOMING_ACTIVITIES_TASK_FUTURE_DAYS
        );

        $taskBeforeString = (new \DateTime())->modify('+' . $upcomingTaskFutureDays . ' days')->format('Y-m-d H:i:s');

        $unionPartList = [];

        $queryList = [];

        foreach ($entityTypeList as $entityType) {
            if (!$this->getMetadata()->get(['scopes', $entityType, 'activity']) && $entityType !== 'Task') continue;
            if (!$this->getAcl()->checkScope($entityType, 'read')) continue;

            $selectParams = [
                'from' => $entityType,
                'select' => [
                    'id',
                    'name',
                    'dateStart',
                    'dateEnd',
                    ['VALUE:' . $entityType, 'entityType']
                ]
            ];

            $selectManager = $this->getSelectManagerFactory()->create($entityType);

            $selectManager->applyAccess($selectParams);

            if (!empty($params['textFilter'])) {
                $selectManager->applyTextFilter($params['textFilter'], $selectParams);
            }

            if (!$this->getMetadata()->get(['entityDefs', $entityType, 'fields', 'dateStart'])) continue;
            if (!$this->getMetadata()->get(['entityDefs', $entityType, 'fields', 'dateEnd'])) continue;

            $selectManager->applyBoolFilter('onlyMy', $selectParams);

            if ($entityType === 'Task') {
                $selectManager->applyPrimaryFilter('actual', $selectParams);

                $selectManager->addOrWhere([
                    [
                        'dateStart' => null,
                        'OR' => [
                            'dateEnd' => null,
                            $selectManager->convertDateTimeWhere([
                                'type' => 'before',
                                'attribute' => 'dateEnd',
                                'value' => $taskBeforeString,
                                'timeZone' => $selectManager->getUserTimeZone()
                            ])
                        ]
                    ],
                    [
                        'dateStart!=' => null,
                        'OR' => [
                            $selectManager->convertDateTimeWhere([
                                'type' => 'past',
                                'attribute' => 'dateStart',
                                'timeZone' => $selectManager->getUserTimeZone()
                            ]),
                            $selectManager->convertDateTimeWhere([
                                'type' => 'today',
                                'attribute' => 'dateStart',
                                'timeZone' => $selectManager->getUserTimeZone()
                            ]),
                            $selectManager->convertDateTimeWhere([
                                'type' => 'before',
                                'attribute' => 'dateStart',
                                'value' => $beforeString,
                                'timeZone' => $selectManager->getUserTimeZone()
                            ])
                        ]
                    ]
                ], $selectParams);
            } else {
                $selectManager->applyPrimaryFilter('planned', $selectParams);

                $selectManager->addOrWhere([
                    $selectManager->convertDateTimeWhere([
                        'type' => 'today',
                        'field' => 'dateStart',
                        'timeZone' => $selectManager->getUserTimeZone()
                    ]),
                    [
                        $selectManager->convertDateTimeWhere([
                            'type' => 'future',
                            'field' => 'dateEnd',
                            'timeZone' => $selectManager->getUserTimeZone()
                        ]),
                        $selectManager->convertDateTimeWhere([
                            'type' => 'before',
                            'field' => 'dateStart',
                            'value' => $beforeString,
                            'timeZone' => $selectManager->getUserTimeZone()
                        ])
                    ]
                ], $selectParams);
            }

            $queryList[] = Select::fromRaw($selectParams);
        }

        if (empty($queryList)) {
            return [
                'total' => 0,
                'list' => [],
            ];
        }

        $builder = $this->entityManager->getQueryBuilder()
            ->union();

        foreach ($queryList as $query) {
            $builder->query($query);
        }

        $unionSql = $this->entityManager->getQueryComposer()->compose($builder->build());

        $countSql = "SELECT COUNT(*) AS 'COUNT' FROM ({$unionSql}) AS c";

        $pdo = $this->getEntityManager()->getPDO();

        $sth = $pdo->prepare($countSql);
        $sth->execute();

        $row = $sth->fetch(PDO::FETCH_ASSOC);

        $totalCount = $row['COUNT'];

        $offset = intval($params['offset']);
        $maxSize = intval($params['maxSize']);

        $unionQuery = $builder
            ->order('dateStart')
            ->order('dateEnd')
            ->order('name')
            ->limit($offset, $maxSize)
            ->build();

        $sth = $this->entityManager->getQueryExecutor()->run($unionQuery);

        $rows = $sth->fetchAll(PDO::FETCH_ASSOC);

        $entityDataList = [];

        foreach ($rows as $row) {
            $entity = $this->getEntityManager()->getEntity($row['entityType'], $row['id']);
            $entityData = $entity->toArray();
            $entityData['_scope'] = $entity->getEntityType();

            $entityDataList[] = $entityData;
        }

        return [
            'total' => $totalCount,
            'list' => $entityDataList,
        ];
    }
}
