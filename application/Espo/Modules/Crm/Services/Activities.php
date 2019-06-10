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

namespace Espo\Modules\Crm\Services;

use \Espo\Core\Exceptions\Error;
use \Espo\Core\Exceptions\NotFound;
use \Espo\Core\Exceptions\Forbidden;

use \Espo\ORM\Entity;

use \PDO;

class Activities extends \Espo\Core\Services\Base
{
    protected function init()
    {
        $this->addDependencyList([
            'metadata',
            'acl',
            'selectManagerFactory',
            'serviceFactory'
        ]);
    }

    const UPCOMING_ACTIVITIES_FUTURE_DAYS = 1;

    const UPCOMING_ACTIVITIES_TASK_FUTURE_DAYS = 7;

    const REMINDER_PAST_HOURS = 24;

    protected function getPDO()
    {
        return $this->getEntityManager()->getPDO();
    }

    protected function getEntityManager()
    {
        return $this->getInjection('entityManager');
    }

    protected function getUser()
    {
        return $this->getInjection('user');
    }

    protected function getAcl()
    {
        return $this->getInjection('acl');
    }

    protected function getMetadata()
    {
        return $this->getInjection('metadata');
    }

    protected function getSelectManagerFactory()
    {
        return $this->getInjection('selectManagerFactory');
    }

    protected function getServiceFactory()
    {
        return $this->getInjection('serviceFactory');
    }

    protected function isPerson($scope)
    {
        return in_array($scope, ['Contact', 'Lead', 'User']) || $this->getMetadata()->get(['scopes', $scope, 'type']) === 'Person';
    }

    protected function isCompany($scope)
    {
        return in_array($scope, ['Account']) || $this->getMetadata()->get(['scopes', $scope, 'type']) === 'Company';
    }

    protected function getActivitiesUserMeetingQuery(Entity $entity, array $statusList = [], $isHistory = false, $additinalSelectParams = null)
    {
        $selectManager = $this->getSelectManagerFactory()->create('Meeting');

        $selectParams = [
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

        $sql = $this->getEntityManager()->getQuery()->createSelectQuery('Meeting', $selectParams);

        return $sql;
    }

    protected function getActivitiesUserCallQuery(Entity $entity, array $statusList = [], $isHistory = false, $additinalSelectParams = null)
    {
        $selectManager = $this->getSelectManagerFactory()->create('Call');

        $selectParams = [
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

        $sql = $this->getEntityManager()->getQuery()->createSelectQuery('Call', $selectParams);

        return $sql;
    }

    protected function getActivitiesUserEmailQuery(Entity $entity, array $statusList = [], $isHistory = false, $additinalSelectParams = null)
    {
        if ($entity->isPortal() && $entity->get('contactId')) {
            $contact = $this->getEntityManager()->getEntity('Contact', $entity->get('contactId'));
            if ($contact) {
                return $this->getActivitiesEmailQuery($contact, $statusList, $isHistory, $additinalSelectParams);
            }
        }

        $selectManager = $this->getSelectManagerFactory()->create('Email');

        $selectParams = [
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
            'leftJoins' => [['EmailUser', 'usersLeftMiddle', ['usersLeftMiddle.emailId:' => 'email.id']]],
            'whereClause' => [
                'usersLeftMiddle.userId' => $entity->id
            ],
            'customJoin' => '',
        ];

        if (!empty($statusList)) {
            $selectParams['whereClause'][] = [
                'status' => $statusList
            ];
        }

        $selectManager->applyAccess($selectParams);

        $selectParams = $selectManager->mergeSelectParams($selectParams, $additinalSelectParams);

        $sql = $this->getEntityManager()->getQuery()->createSelectQuery('Email', $selectParams);

        return $sql;
    }

    protected function getActivitiesMeetingQuery(Entity $entity, array $statusList = [], $isHistory = false, $additinalSelectParams = null)
    {
        $scope = $entity->getEntityType();
        $id = $entity->id;

        $methodName = 'getActivities' . $scope . 'MeetingQuery';
        if (method_exists($this, $methodName)) {
            return $this->$methodName($entity, $statusList, $isHistory, $additinalSelectParams);
        }

        $selectManager = $this->getSelectManagerFactory()->create('Meeting');

        $baseSelectParams = array(
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
                ['VALUE:', 'hasAttachment']
            ],
            'whereClause' => [],
            'customJoin' => ''
        );

        if (!empty($statusList)) {
            $baseSelectParams['whereClause'][] = array(
                'status' => $statusList
            );
        }

        $selectParams = $baseSelectParams;

        if ($scope == 'Account') {
            $selectParams['whereClause'][] = array(
                'OR' => array(
                    array(
                        'parentId' => $id,
                        'parentType' => 'Account'
                    ),
                    array(
                        'accountId' => $id
                    )
                )
            );
        } else if ($scope == 'Lead' && $entity->get('createdAccountId')) {
            $selectParams['whereClause'][] = array(
                'OR' => array(
                    array(
                        'parentId' => $id,
                        'parentType' => 'Lead'
                    ),
                    array(
                        'accountId' => $entity->get('createdAccountId')
                    )
                )
            );
        } else {
            $selectParams['whereClause']['parentId'] = $id;
            $selectParams['whereClause']['parentType'] = $scope;
        }

        $selectManager->applyAccess($selectParams);

        $selectParams = $selectManager->mergeSelectParams($selectParams, $additinalSelectParams);

        $sql = $this->getEntityManager()->getQuery()->createSelectQuery('Meeting', $selectParams);

        if ($this->isPerson($scope)) {
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
            if ($link) {
                $selectParams = $baseSelectParams;
                $selectManager->addJoin($link, $selectParams);
                $selectParams['whereClause'][$link .'.id'] = $id;
                $selectParams['whereClause'][] = array(
                    'OR' => array(
                        'parentType!=' => $scope,
                        'parentId!=' => $id,
                        'parentType' => null,
                        'parentId' => null
                    )
                );

                $selectManager->applyAccess($selectParams);

                $selectParams = $selectManager->mergeSelectParams($selectParams, $additinalSelectParams);

                $sql .= ' UNION ' . $this->getEntityManager()->getQuery()->createSelectQuery('Meeting', $selectParams);
            }
        }

        return $sql;
    }

    protected function getActivitiesCallQuery(Entity $entity, array $statusList = [], $isHistory = false, $additinalSelectParams = null)
    {
        $scope = $entity->getEntityType();
        $id = $entity->id;

        $methodName = 'getActivities' .$scope . 'CallQuery';
        if (method_exists($this, $methodName)) {
            return $this->$methodName($entity, $statusList, $isHistory, $additinalSelectParams);
        }

        $selectManager = $this->getSelectManagerFactory()->create('Call');

        $baseSelectParams = array(
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
                ['VALUE:', 'hasAttachment']
            ],
            'whereClause' => []
        );

        if (!empty($statusList)) {
            $baseSelectParams['whereClause'][] = array(
                'status' => $statusList
            );
        }

        $selectParams = $baseSelectParams;

        if ($scope == 'Account') {
            $selectParams['whereClause'][] = array(
                'OR' => array(
                    array(
                        'parentId' => $id,
                        'parentType' => 'Account'
                    ),
                    array(
                        'accountId' => $id
                    )
                )
            );
        } else if ($scope == 'Lead' && $entity->get('createdAccountId')) {
            $selectParams['whereClause'][] = array(
                'OR' => array(
                    array(
                        'parentId' => $id,
                        'parentType' => 'Lead'
                    ),
                    array(
                        'accountId' => $entity->get('createdAccountId')
                    )
                )
            );
        } else {
            $selectParams['whereClause']['parentId'] = $id;
            $selectParams['whereClause']['parentType'] = $scope;
        }

        $selectManager->applyAccess($selectParams);

        $selectParams = $selectManager->mergeSelectParams($selectParams, $additinalSelectParams);

        $sql = $this->getEntityManager()->getQuery()->createSelectQuery('Call', $selectParams);

        if ($this->isPerson($scope)) {
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
            if ($link) {
                $selectParams = $baseSelectParams;
                $selectManager->addJoin($link, $selectParams);
                $selectParams['whereClause'][$link .'.id'] = $id;
                $selectParams['whereClause'][] = array(
                    'OR' => array(
                        'parentType!=' => $scope,
                        'parentId!=' => $id,
                        'parentType' => null,
                        'parentId' => null
                    )
                );

                $selectManager->applyAccess($selectParams);

                $selectParams = $selectManager->mergeSelectParams($selectParams, $additinalSelectParams);

                $sql .= ' UNION ' . $this->getEntityManager()->getQuery()->createSelectQuery('Call', $selectParams);
            }
        }

        return $sql;
    }

    protected function getActivitiesEmailQuery(Entity $entity, array $statusList = [], $isHistory = false, $additinalSelectParams = null)
    {
        $scope = $entity->getEntityType();
        $id = $entity->id;

        $methodName = 'getActivities' .$scope . 'EmailQuery';
        if (method_exists($this, $methodName)) {
            return $this->$methodName($entity, $statusList, $isHistory, $additinalSelectParams);
        }

        $selectManager = $this->getSelectManagerFactory()->create('Email');

        $baseSelectParams = array(
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
            'whereClause' => [],
            'customJoin' => ''
        );

        if (!empty($statusList)) {
            $baseSelectParams['whereClause'][] = array(
                'status' => $statusList
            );
        }

        $selectParams = $baseSelectParams;

        if ($scope == 'Account') {
            $selectParams['whereClause'][] = array(
                'OR' => array(
                    array(
                        'parentId' => $id,
                        'parentType' => 'Account'
                    ),
                    array(
                        'accountId' => $id
                    )
                )
            );
        } else if ($scope == 'Lead' && $entity->get('createdAccountId')) {
            $selectParams['whereClause'][] = array(
                'OR' => array(
                    array(
                        'parentId' => $id,
                        'parentType' => 'Lead'
                    ),
                    array(
                        'accountId' => $entity->get('createdAccountId')
                    )
                )
            );
        } else {
            $selectParams['whereClause']['parentId'] = $id;
            $selectParams['whereClause']['parentType'] = $scope;
        }

        $selectManager->applyAccess($selectParams);

        $selectParams = $selectManager->mergeSelectParams($selectParams, $additinalSelectParams);

        $sql = $this->getEntityManager()->getQuery()->createSelectQuery('Email', $selectParams);

        if ($this->isPerson($scope) || $this->isCompany($scope)) {
            $selectParams = $baseSelectParams;
            $selectParams['customJoin'] .= "
                LEFT JOIN entity_email_address AS entityEmailAddress2 ON
                    entityEmailAddress2.email_address_id = email.from_email_address_id AND
                    entityEmailAddress2.entity_type = " . $this->getPDO()->quote($scope) . " AND
                    entityEmailAddress2.deleted = 0
            ";
            $selectParams['whereClause'][] = array(
                'OR' => array(
                    'parentType!=' => $scope,
                    'parentId!=' => $id,
                    'parentType' => null,
                    'parentId' => null
                )
            );
            $selectParams['whereClause']['entityEmailAddress2.entityId'] = $id;

            $selectManager->applyAccess($selectParams);

            $selectParams = $selectManager->mergeSelectParams($selectParams, $additinalSelectParams);

            $sql .= "\n UNION \n" . $this->getEntityManager()->getQuery()->createSelectQuery('Email', $selectParams);

            $selectParams = $baseSelectParams;
            $selectParams['customJoin'] .= "
                LEFT JOIN email_email_address ON
                    email_email_address.email_id = email.id AND
                    email_email_address.deleted = 0
                LEFT JOIN entity_email_address AS entityEmailAddress1 ON
                    entityEmailAddress1.email_address_id = email_email_address.email_address_id AND

                    entityEmailAddress1.entity_type = " . $this->getPDO()->quote($scope) . " AND
                    entityEmailAddress1.deleted = 0
            ";
            $selectParams['whereClause'][] = array(
                'OR' => array(
                    'parentType!=' => $scope,
                    'parentId!=' => $id,
                    'parentType' => null,
                    'parentId' => null
                )
            );
            $selectParams['whereClause']['entityEmailAddress1.entityId'] = $id;
            $selectManager->applyAccess($selectParams);

            $selectParams = $selectManager->mergeSelectParams($selectParams, $additinalSelectParams);

            $sql .= "\n UNION \n" . $this->getEntityManager()->getQuery()->createSelectQuery('Email', $selectParams);
        }

        return $sql;
    }

    protected function getResultFromQueryParts($parts, $scope, $id, $params)
    {
        if (empty($parts)) {
            return [
                'list' => [],
                'total' => 0
            ];
        }

        $pdo = $this->getEntityManager()->getPDO();

        $onlyScope = false;
        if (!empty($params['scope'])) {
            $onlyScope = $params['scope'];
        }

        if (!$onlyScope) {
            $sql = implode(" UNION ", $parts);
        } else {
            $sql = $parts[$onlyScope];
        }

        $sqlCount = "SELECT COUNT(*) AS 'count' FROM ({$sql}) AS c";
        $sth = $pdo->prepare($sqlCount);
        $sth->execute();

        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $totalCount = $row['count'];

        $sql .= "
            ORDER BY dateStart DESC, createdAt DESC
        ";

        if (!empty($params['maxSize'])) {
            $sql .= "
                LIMIT :offset, :maxSize
            ";
        }

        $sth = $pdo->prepare($sql);

        if (!empty($params['maxSize'])) {
            $offset = 0;
            if (!empty($params['offset'])) {
                $offset = $params['offset'];
            }

            $sth->bindParam(':offset', $offset, PDO::PARAM_INT);
            $sth->bindParam(':maxSize', $params['maxSize'], PDO::PARAM_INT);
        }

        $sth->execute();

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

        return [
            'list' => $list,
            'total' => $totalCount
        ];
    }

    protected function accessCheck($entity)
    {
        if ($entity->getEntityType() == 'User') {
            if (!$this->getAcl()->checkUser('userPermission', $entity)) {
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

        $selectParams = $selectManager->getSelectParams($params, false, true);

        $selectAttributeList = $service->getSelectAttributeList($params);
        if ($selectAttributeList) {
            $selectParams['select'] = $selectAttributeList;
        }

        $this->getEntityManager()->getRepository($entityType)->handleSelectParams($selectParams);

        $offset = $selectParams['offset'];
        $limit = $selectParams['limit'];

        $orderBy = null;
        $order = null;
        if (!empty($selectParams['orderBy'])) {
            $order = $selectParams['order'];
            $orderBy = $selectParams['orderBy'];
        }

        unset($selectParams['offset']);
        unset($selectParams['limit']);
        unset($selectParams['order']);
        unset($selectParams['orderBy']);

        if ($entityType === 'Email') {
            if ($orderBy === 'dateStart') {
                $orderBy = 'dateSent';
                $order = 'desc';
            }
        }

        $sql = $this->getActivitiesQuery($entity, $entityType, $statusList, $isHistory, $selectParams);

        $query = $this->getEntityManager()->getQuery();

        $seed = $this->getEntityManager()->getEntity($entityType);

        $sqlBase = $sql;

        if ($orderBy) {
            $sql = $query->order($sql, $seed, $orderBy, $order, true);
        }

        $sql = $query->limit($sql, $offset, $limit);


        $collection = $this->getEntityManager()->getRepository($entityType)->findByQuery($sql);

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
            'collection' => $collection
        ];
    }

    public function getActivities($scope, $id, $params = [])
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

        $selectParams = array(
            'select' => [
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
                'createdAt'
            ],
            'leftJoins' => ['users'],
            'whereClause' => array(
                'usersMiddle.userId' => $userId,
                array(
                    'OR' => array(
                        array(
                            'dateStart>=' => $from,
                            'dateStart<' => $to
                        ),
                        array(
                            'dateEnd>=' => $from,
                            'dateEnd<' => $to
                        ),
                        array(
                            'dateStart<=' => $from,
                            'dateEnd>=' => $to
                        )
                    )
                ),
                'usersMiddle.status!=' => 'Declined'
            ),
            'customJoin' => ''
        );

        if (!$skipAcl) {
            $selectManager->applyAccess($selectParams);
        }

        return $this->getEntityManager()->getQuery()->createSelectQuery('Meeting', $selectParams);
    }

    protected function getCalendarCallQuery($userId, $from, $to, $skipAcl)
    {
        $selectManager = $this->getSelectManagerFactory()->create('Call');

        $selectParams = array(
            'select' => [
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
                'createdAt'
            ],
            'leftJoins' => ['users'],
            'whereClause' => array(
                'usersMiddle.userId' => $userId,
                array(
                    'OR' => array(
                        array(
                            'dateStart>=' => $from,
                            'dateStart<' => $to
                        ),
                        array(
                            'dateEnd>=' => $from,
                            'dateEnd<' => $to
                        ),
                        array(
                            'dateStart<=' => $from,
                            'dateEnd>=' => $to
                        )
                    )
                ),
                'usersMiddle.status!=' => 'Declined'
            ),
            'customJoin' => ''
        );

        if (!$skipAcl) {
            $selectManager->applyAccess($selectParams);
        }

        return $this->getEntityManager()->getQuery()->createSelectQuery('Call', $selectParams);
    }

    protected function getCalendarTaskQuery($userId, $from, $to, $skipAcl = false)
    {
        $selectManager = $this->getSelectManagerFactory()->create('Task');

        $selectParams = array(
            'select' => [
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
                'createdAt'
            ],
            'whereClause' => array(
                array(
                    'OR' => array(
                        array(
                            'dateEnd' => null,
                            'dateStart>=' => $from,
                            'dateStart<' => $to,
                        ),
                        array(
                            'dateEnd>=' => $from,
                            'dateEnd<' => $to,
                        ),
                        array(
                            'dateEndDate!=' => null,
                            'dateEndDate>=' => $from,
                            'dateEndDate<' => $to,
                        )
                    )
                )
            )
        );

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

        return $this->getEntityManager()->getQuery()->createSelectQuery('Task', $selectParams);
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
            'createdAt'
        ];

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
            return $this->getEntityManager()->getQuery()->createSelectQuery($scope, $selectParams);
        }

        $methodName = 'getCalendar' . $scope . 'Query';
        if (method_exists($this, $methodName)) {
            return $this->$methodName($userId, $from, $to, $skipAcl);
        }

        $selectParams = $this->getCalendarSelectParams($scope, $userId, $from, $to, $skipAcl);
        return $this->getEntityManager()->getQuery()->createSelectQuery($scope, $selectParams);
    }

    protected function getActivitiesQuery(Entity $entity, $scope, array $statusList = [], $isHistory = false, $additinalSelectParams = null)
    {
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

            return $this->getEntityManager()->getQuery()->createSelectQuery($scope, $selectParams);
        }

        $methodName = 'getActivities' . $scope . 'Query';
        if (method_exists($this, $methodName)) {
            return $this->$methodName($entity, $statusList, $isHistory, $additinalSelectParams);
        }

        $selectParams = $this->getActivitiesSelectParams($entity, $scope, $statusList, $isHistory);

        $selectParams = $selectManager->mergeSelectParams($selectParams, $additinalSelectParams);

        return $this->getEntityManager()->getQuery()->createSelectQuery($scope, $selectParams);
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
            ($seed->hasAttribute('assignedUserName') ? ['assignedUserName', 'assignedUserName'] : ['VALUE:', 'assignedUserName']),
            ($seed->hasAttribute('parentType') ? ['parentType', 'parentType'] : ['VALUE:', 'parentType']),
            ($seed->hasAttribute('parentId') ? ['parentId', 'parentId'] : ['VALUE:', 'parentId']),
            'status',
            'createdAt',
            ['VALUE:', 'hasAttachment']
        ];

        $selectParams = $selectManager->getEmptySelectParams();

        $selectParams['select'] = $select;

        if ($entity->getEntityType() === 'User') {
            $selectParams['whereClause'][] = array(
                'assignedUserId' => $entity->id
            );
        } else {
            $selectParams['whereClause'][] = array(
                'parentId' => $entity->id,
                'parentType' => $entity->getEntityType()
            );
        }

        $selectParams['whereClause'][]  = array(
            'status' => $statusList
        );

        $selectManager->applyAccess($selectParams);

        return $selectParams;
    }

    public function getUsersTimeline($userIdList, $from, $to, $scopeList = null)
    {
        $resultData = (object) [];
        foreach ($userIdList as $userId) {
            $userData = (object) [
                'eventList' => [],
                'busyRangeList' => []
            ];
            try {
                $userData->eventList = $this->getEventList($userId, $from, $to, $scopeList);
                $userData->busyRangeList = $this->getBusyRangeList($userId, $from, $to, $scopeList, $userData->eventList);
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

        $userList = $this->getEntityManager()->getRepository('User')->select(['id', 'name'])->leftJoin([['teams', 'teams']])->where([
            'isActive' => true,
            'teamsMiddle.teamId' => $teamIdList
        ])->distinct()->find([], true);

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

        foreach ($eventList as $i => $item) {
            $eventList[$i] = (object) $item;
        }
        foreach ($eventList as $event) {
            if (empty($event->dateStart) || empty($event->dateEnd)) continue;
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

        $sqlPartList = [];

        foreach ($scopeList as $scope) {
            if (!in_array($scope, $calendarEntityList)) {
                continue;
            }
            if ($this->getAcl()->checkScope($scope)) {
                if ($this->getMetadata()->get(['scopes', $scope, 'calendar'])) {
                    $sqlPartList[] = $this->getCalendarQuery($scope, $userId, $from, $to, $skipAcl);
                }
            }
        }

        if (empty($sqlPartList)) {
            return [];
        }

        $sql = implode(" UNION ", $sqlPartList);

        $sth = $pdo->prepare($sql);
        $sth->execute();
        $rowList = $sth->fetchAll(PDO::FETCH_ASSOC);

        return $rowList;
    }

    public function getEvents($userId, $from, $to, $scopeList = null, $skipAcl = false)
    {
        return $this->getEventList($userId, $from, $to, $scopeList, $skipAcl);
    }

    public function removeReminder($id)
    {

        $pdo = $this->getPDO();
        $sql = "
            DELETE FROM `reminder`
            WHERE id = ".$pdo->quote($id)."
        ";
        if (!$this->getUser()->isAdmin()) {
            $sql .= " AND user_id = " . $pdo->quote($this->getUser()->id);
        }

        $pdo->query($sql);
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

        $upcomingTaskFutureDays = $this->getConfig()->get('activitiesUpcomingTaskFutureDays', self::UPCOMING_ACTIVITIES_TASK_FUTURE_DAYS);
        $taskBeforeString = (new \DateTime())->modify('+' . $upcomingTaskFutureDays . ' days')->format('Y-m-d H:i:s');

        $unionPartList = [];
        foreach ($entityTypeList as $entityType) {
            if (!$this->getMetadata()->get(['scopes', $entityType, 'activity']) && $entityType !== 'Task') continue;
            if (!$this->getAcl()->checkScope($entityType, 'read')) continue;

            $selectParams = [
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

            $sql = $this->getEntityManager()->getQuery()->createSelectQuery($entityType, $selectParams);

            $unionPartList[] = '' . $sql . '';
        }
        if (empty($unionPartList)) {
            return [
                'total' => 0,
                'list' => []
            ];
        }

        $pdo = $this->getEntityManager()->getPDO();

        $unionSql = implode(' UNION ', $unionPartList);

        $countSql = "SELECT COUNT(*) AS 'COUNT' FROM ({$unionSql}) AS c";
        $sth = $pdo->prepare($countSql);
        $sth->execute();
        $row = $sth->fetch(\PDO::FETCH_ASSOC);
        $totalCount = $row['COUNT'];

        $unionSql .= " ORDER BY dateStart ASC, dateEnd ASC, name ASC";
        $unionSql .= " LIMIT :offset, :maxSize";

        $sth = $pdo->prepare($unionSql);

        $offset = intval($params['offset']);
        $maxSize = intval($params['maxSize']);

        $sth->bindParam(':offset', $offset, \PDO::PARAM_INT);
        $sth->bindParam(':maxSize', $maxSize, \PDO::PARAM_INT);
        $sth->execute();
        $rows = $sth->fetchAll(\PDO::FETCH_ASSOC);

        $entityDataList = [];

        foreach ($rows as $row) {
            $entity = $this->getEntityManager()->getEntity($row['entityType'], $row['id']);
            $entityData = $entity->toArray();
            $entityData['_scope'] = $entity->getEntityType();
            $entityDataList[] = $entityData;
        }

        return [
            'total' => $totalCount,
            'list' => $entityDataList
        ];
    }
}
