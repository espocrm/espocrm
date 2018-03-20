<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

    protected function getActivitiesUserMeetingQuery(Entity $entity, array $statusList = [], $isHistory = false)
    {
        $selectManager = $this->getSelectManagerFactory()->create('Meeting');

        $selectParams = array(
            'select' => [
                'id',
                'name',
                ['dateStart', 'dateStart'],
                ['dateEnd', 'dateEnd'],
                ['VALUE:Meeting', '_scope'],
                'assignedUserId',
                'assignedUserName',
                'parentType',
                'parentId',
                'status',
                'createdAt'
            ],
            'leftJoins' => [['users', 'usersLeft']],
            'whereClause' => array(
            ),
            'customJoin' => ''
        );

        $where = array(
            'usersLeftMiddle.userId' => $entity->id
        );

        if ($entity->get('isPortalUser') && $entity->get('contactId')) {
            $selectParams['leftJoins'][] = ['contacts', 'contactsLeft'];
            $where['contactsLeftMiddle.contactId'] = $entity->get('contactId');
            $selectParams['whereClause'][] = array(
                'OR' => $where
            );
        } else {
            $selectParams['whereClause'][] = $where;
        }

        if (!empty($statusList)) {
            $selectParams['whereClause'][] = array(
                'status' => $statusList
            );
        }

        $selectManager->applyAccess($selectParams);

        $sql = $this->getEntityManager()->getQuery()->createSelectQuery('Meeting', $selectParams);

        return $sql;
    }

    protected function getActivitiesUserCallQuery(Entity $entity, array $statusList = [], $isHistory = false)
    {
        $selectManager = $this->getSelectManagerFactory()->create('Call');

        $selectParams = array(
            'select' => [
                'id',
                'name',
                ['dateStart', 'dateStart'],
                ['dateEnd', 'dateEnd'],
                ['VALUE:Call', '_scope'],
                'assignedUserId',
                'assignedUserName',
                'parentType',
                'parentId',
                'status',
                'createdAt'
            ],
            'leftJoins' => [['users', 'usersLeft']],
            'whereClause' => array(
            ),
            'customJoin' => ''
        );

        $where = array(
            'usersLeftMiddle.userId' => $entity->id
        );

        if ($entity->get('isPortalUser') && $entity->get('contactId')) {
            $selectParams['leftJoins'][] = ['contacts', 'contactsLeft'];
            $where['contactsLeftMiddle.contactId'] = $entity->get('contactId');
            $selectParams['whereClause'][] = array(
                'OR' => $where
            );
        } else {
            $selectParams['whereClause'][] = $where;
        }

        if (!empty($statusList)) {
            $selectParams['whereClause'][] = array(
                'status' => $statusList
            );
        }

        $selectManager->applyAccess($selectParams);

        $sql = $this->getEntityManager()->getQuery()->createSelectQuery('Call', $selectParams);

        return $sql;
    }

    protected function getActivitiesUserEmailQuery(Entity $entity, array $statusList = [], $isHistory = false)
    {
        if ($entity->get('isPortalUser') && $entity->get('contactId')) {
            $contact = $this->getEntityManager()->getEntity('Contact', $entity->get('contactId'));
            if ($contact) {
                return $this->getActivitiesEmailQuery($contact, $op, $statusList);
            }
        }

        $selectManager = $this->getSelectManagerFactory()->create('Email');

        $selectParams = array(
            'select' => [
                'id',
                'name',
                ['dateSent', 'dateStart'],
                ['VALUE:', 'dateEnd'],
                ['VALUE:Email', '_scope'],
                'assignedUserId',
                'assignedUserName',
                'parentType',
                'parentId',
                'status',
                'createdAt'
            ],
            'leftJoins' => [['users', 'usersLeft']],
            'whereClause' => array(
                'usersLeftMiddle.userId' => $entity->id
            ),
            'customJoin' => ''
        );

        if (!empty($statusList)) {
            $selectParams['whereClause'][] = array(
                'status' => $statusList
            );
        }

        $selectManager->applyAccess($selectParams);

        $sql = $this->getEntityManager()->getQuery()->createSelectQuery('Email', $selectParams);

        return $sql;
    }

    protected function getActivitiesMeetingQuery(Entity $entity, array $statusList = [], $isHistory = false)
    {
        $scope = $entity->getEntityType();
        $id = $entity->id;

        $methodName = 'getActivities' . $scope . 'MeetingQuery';
        if (method_exists($this, $methodName)) {
            return $this->$methodName($entity, $statusList, $isHistory);
        }

        $selectManager = $this->getSelectManagerFactory()->create('Meeting');

        $baseSelectParams = array(
            'select' => [
                'id',
                'name',
                ['dateStart', 'dateStart'],
                ['dateEnd', 'dateEnd'],
                ['VALUE:Meeting', '_scope'],
                'assignedUserId',
                'assignedUserName',
                'parentType',
                'parentId',
                'status',
                'createdAt'
            ],
            'whereClause' => array(),
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

                $sql .= ' UNION ' . $this->getEntityManager()->getQuery()->createSelectQuery('Meeting', $selectParams);
            }
        }

        return $sql;
    }

    protected function getActivitiesCallQuery(Entity $entity, array $statusList = [], $isHistory = false)
    {
        $scope = $entity->getEntityType();
        $id = $entity->id;

        $methodName = 'getActivities' .$scope . 'CallQuery';
        if (method_exists($this, $methodName)) {
            return $this->$methodName($entity, $statusList, $isHistory);
        }

        $selectManager = $this->getSelectManagerFactory()->create('Call');

        $baseSelectParams = array(
            'select' => [
                'id',
                'name',
                ['dateStart', 'dateStart'],
                ['dateEnd', 'dateEnd'],
                ['VALUE:Call', '_scope'],
                'assignedUserId',
                'assignedUserName',
                'parentType',
                'parentId',
                'status',
                'createdAt'
            ],
            'whereClause' => array()
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

                $sql .= ' UNION ' . $this->getEntityManager()->getQuery()->createSelectQuery('Call', $selectParams);
            }
        }

        return $sql;
    }

    protected function getActivitiesEmailQuery(Entity $entity, array $statusList = [], $isHistory = false)
    {
        $scope = $entity->getEntityType();
        $id = $entity->id;

        $methodName = 'get' .$scope . 'EmailQuery';
        if (method_exists($this, $methodName)) {
            return $this->$methodName($entity, $statusList, $isHistory);
        }

        $selectManager = $this->getSelectManagerFactory()->create('Email');

        $baseSelectParams = array(
            'select' => [
                'id',
                'name',
                ['dateSent', 'dateStart'],
                ['VALUE:', 'dateEnd'],
                ['VALUE:Email', '_scope'],
                'assignedUserId',
                'assignedUserName',
                'parentType',
                'parentId',
                'status',
                'createdAt'
            ],
            'whereClause' => array(),
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
            $sql .= "\n UNION \n" . $this->getEntityManager()->getQuery()->createSelectQuery('Email', $selectParams);
        }

        return $sql;
    }

    protected function getResultFromQueryParts($parts, $scope, $id, $params)
    {
        if (empty($parts)) {
            return array(
                'list' => [],
                'total' => 0
            );
        }

        $pdo = $this->getEntityManager()->getPDO();

        $onlyScope = false;
        if (!empty($params['scope'])) {
            $onlyScope = $params['scope'];
        }

        if (!$onlyScope) {
            $qu = implode(" UNION ", $parts);
        } else {
            $qu = $parts[$onlyScope];
        }

        $countQu = "SELECT COUNT(*) AS 'count' FROM ({$qu}) AS c";
        $sth = $pdo->prepare($countQu);
        $sth->execute();

        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $totalCount = $row['count'];

        $qu .= "
            ORDER BY dateStart DESC, createdAt DESC
        ";

        if (!empty($params['maxSize'])) {
            $qu .= "
                LIMIT :offset, :maxSize
            ";
        }

        $sth = $pdo->prepare($qu);

        if (!empty($params['maxSize'])) {
            $offset = 0;
            if (!empty($params['offset'])) {
                $offset = $params['offset'];
            }

            $sth->bindParam(':offset', $offset, PDO::PARAM_INT);
            $sth->bindParam(':maxSize', $params['maxSize'], PDO::PARAM_INT);
        }

        $sth->execute();

        $rows = $sth->fetchAll(PDO::FETCH_ASSOC);

        $list = array();
        foreach ($rows as $row) {
            $list[] = $row;
        }

        return array(
            'list' => $rows,
            'total' => $totalCount
        );
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

        $parts = array();

        $entityTypeList = $this->getConfig()->get('activitiesEntityList', ['Meeting', 'Call']);

        foreach ($entityTypeList as $entityType) {
            if (!$fetchAll && $params['scope'] !== $entityType) continue;
            if (!$this->getAcl()->checkScope($entityType)) continue;
            if (!$this->getMetadata()->get('scopes.' . $entityType . '.activity')) continue;

            $statusList = $this->getMetadata()->get(['scopes', $entityType, 'activityStatusList'], ['Planned']);
            $parts[$entityType] = $this->getActivitiesQuery($entity, $entityType, $statusList);
        }

        return $this->getResultFromQueryParts($parts, $scope, $id, $params);
    }

    public function getHistory($scope, $id, $params)
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

        $parts = array();
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

    protected function getCalendarMeetingQuery($userId, $from, $to)
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

        return $this->getEntityManager()->getQuery()->createSelectQuery('Meeting', $selectParams);
    }

    protected function getCalendarCallQuery($userId, $from, $to)
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

        return $this->getEntityManager()->getQuery()->createSelectQuery('Call', $selectParams);
    }

    protected function getCalendarTaskQuery($userId, $from, $to)
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
                'assignedUserId' => $userId,
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

        return $this->getEntityManager()->getQuery()->createSelectQuery('Task', $selectParams);
    }

    protected function getCalendarSelectParams($scope, $userId, $from, $to)
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

        $wherePart = array(
            'assignedUserId' => $userId,
        );

        if ($seed->hasRelation('users')) {
            $wherePart['usersMiddle.userId'] = $userId;
        }

        if ($seed->hasRelation('assignedUsers')) {
            $wherePart['assignedUsersMiddle.userId'] = $userId;
        }

        $selectParams = array(
            'select' => $select,
            'leftJoins' => [],
            'whereClause' => array(
                'OR' => $wherePart,
                array(
                    'OR' => array(
                        array(
                            'dateEnd' => null,
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
                        ),
                        array(
                            'dateEndDate!=' => null,
                            'dateEndDate>=' => $from,
                            'dateEndDate<' => $to
                        )
                    )
                )
            )
        );

        if ($seed->hasRelation('users')) {
            $selectParams['leftJoins'][] = 'users';
        }

        if ($seed->hasRelation('assignedUsers')) {
            $selectParams['leftJoins'][] = 'assignedUsers';
        }

        return $selectParams;
    }

    protected function getCalendarQuery($scope, $userId, $from, $to)
    {
        $selectManager = $this->getSelectManagerFactory()->create($scope);
        if (method_exists($selectManager, 'getCalendarSelectParams')) {
            $selectParams = $selectManager->getCalendarSelectParams($userId, $from, $to);
            return $this->getEntityManager()->getQuery()->createSelectQuery($scope, $selectParams);
        }

        $methodName = 'getCalendar' . $scope . 'Query';
        if (method_exists($this, $methodName)) {
            return $this->$methodName($userId, $from, $to);
        }

        $selectParams = $this->getCalendarSelectParams($scope, $userId, $from, $to);
        return $this->getEntityManager()->getQuery()->createSelectQuery($scope, $selectParams);
    }

    protected function getActivitiesQuery(Entity $entity, $scope, array $statusList = [], $isHistory = false)
    {
        $serviceName = 'Activities' . $entity->getEntityType();
        if ($this->getServiceFactory()->checkExists($serviceName)) {
            $service = $this->getServiceFactory()->create($serviceName);
            $methodName = 'getActivities' . $scope . 'Query';
            if (method_exists($service, $methodName)) {
                return $service->$methodName($entity, $statusList, $isHistory);
            }
        }

        $selectManager = $this->getSelectManagerFactory()->create($scope);
        if (method_exists($selectManager, 'getActivitiesSelectParams')) {
            $selectParams = $selectManager->getActivitiesSelectParams($entity, $statusList, $isHistory);
            return $this->getEntityManager()->getQuery()->createSelectQuery($scope, $selectParams);
        }

        $methodName = 'getActivities' . $scope . 'Query';
        if (method_exists($this, $methodName)) {
            return $this->$methodName($entity, $statusList, $isHistory);
        }

        $selectParams = $this->getActivitiesSelectParams($entity, $scope, $statusList, $isHistory);

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
            ['VALUE:' . $scope, '_scope'],
            ($seed->hasAttribute('assignedUserId') ? ['assignedUserId', 'assignedUserId'] : ['VALUE:', 'assignedUserId']),
            ($seed->hasAttribute('assignedUserName') ? ['assignedUserName', 'assignedUserName'] : ['VALUE:', 'assignedUserName']),
            ($seed->hasAttribute('parentType') ? ['parentType', 'parentType'] : ['VALUE:', 'parentType']),
            ($seed->hasAttribute('parentId') ? ['parentId', 'parentId'] : ['VALUE:', 'parentId']),
            'status',
            'createdAt'
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

    public function getEvents($userId, $from, $to, $scopeList = null)
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
                if ($this->getMetadata()->get('scopes.' . $scope . '.calendar')) {
                    $sqlPartList[] = $this->getCalendarQuery($scope, $userId, $from, $to);
                }
            }
        }

        if (empty($sqlPartList)) {
            return [];
        }

        $sql = implode(" UNION ", $sqlPartList);

        $sth = $pdo->prepare($sql);
        $sth->execute();
        $rows = $sth->fetchAll(PDO::FETCH_ASSOC);

        return $rows;
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

        $result = array();
        foreach ($rowList as $row) {
            $reminderId = $row['id'];
            $entityType = $row['entityType'];
            $entityId = $row['entityId'];

            $entity = $this->getEntityManager()->getEntity($entityType, $entityId);
            $data = null;

            if ($entity) {
                if ($entity->hasLinkMultipleField('users')) {
                    $entity->loadLinkMultipleField('users', array('status' => 'acceptanceStatus'));
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

                $data = array(
                    'id' => $entity->id,
                    'entityType' => $entityType,
                    $dateAttribute => $entity->get($dateAttribute),
                    'name' => $entity->get('name')
                );
            } else {
                continue;
            }
            $result[] = array(
                'id' => $reminderId,
                'data' => $data
            );

        }
        return $result;
    }

    public function getUpcomingActivities($userId, $params = array(), $entityTypeList = null, $futureDays = null)
    {
        $user = $this->getEntityManager()->getEntity('User', $userId);
        $this->accessCheck($user);

        if (!$entityTypeList) {
            $entityTypeList = $this->getConfig()->get('activitiesEntityList', []);
        }

        if (is_null($futureDays)) {
            $futureDays = self::UPCOMING_ACTIVITIES_FUTURE_DAYS;
        }
        $beforeString = (new \DateTime())->modify('+' . $futureDays . ' days')->format('Y-m-d H:i:s');

        $unionPartList = [];
        foreach ($entityTypeList as $entityType) {
            if (!$this->getMetadata()->get(['scopes', $entityType, 'activity']) && $entityType !== 'Task') continue;
            if (!$this->getAcl()->checkScope($entityType, 'read')) continue;

            $selectParams = array(
                'select' => [
                    'id',
                    'name',
                    'dateStart',
                    'dateEnd',
                    ['VALUE:' . $entityType, 'entityType']
                ]
            );

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
                        'dateStart' => null
                    ],
                    [
                        'dateStart!=' => null,
                        'OR' => array(
                            $selectManager->convertDateTimeWhere(array(
                                'type' => 'past',
                                'attribute' => 'dateStart',
                                'timeZone' => $selectManager->getUserTimeZone()
                            )),
                            $selectManager->convertDateTimeWhere(array(
                                'type' => 'today',
                                'attribute' => 'dateStart',
                                'timeZone' => $selectManager->getUserTimeZone()
                            )),
                            $selectManager->convertDateTimeWhere(array(
                                'type' => 'before',
                                'attribute' => 'dateStart',
                                'value' => $beforeString,
                                'timeZone' => $selectManager->getUserTimeZone()
                            ))
                        )
                    ]
                ], $selectParams);
            } else {
                $selectManager->applyPrimaryFilter('planned', $selectParams);

                $selectManager->addOrWhere([
                    $selectManager->convertDateTimeWhere(array(
                        'type' => 'today',
                        'field' => 'dateStart',
                        'timeZone' => $selectManager->getUserTimeZone()
                    )),
                    [
                        $selectManager->convertDateTimeWhere(array(
                            'type' => 'future',
                            'field' => 'dateEnd',
                            'timeZone' => $selectManager->getUserTimeZone()
                        )),
                        $selectManager->convertDateTimeWhere(array(
                            'type' => 'before',
                            'field' => 'dateStart',
                            'value' => $beforeString,
                            'timeZone' => $selectManager->getUserTimeZone()
                        ))
                    ]
                ], $selectParams);
            }

            $sql = $this->getEntityManager()->getQuery()->createSelectQuery($entityType, $selectParams);

            $unionPartList[] = '' . $sql . '';
        }
        if (empty($unionPartList)) {
            return array(
                'total' => 0,
                'list' => []
            );
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

        return array(
            'total' => $totalCount,
            'list' => $entityDataList
        );
    }
}

