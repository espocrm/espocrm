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

namespace Espo\Modules\Crm\Services;

use \Espo\Core\Exceptions\Error;
use \Espo\Core\Exceptions\NotFound;
use \Espo\Core\Exceptions\Forbidden;

use \PDO;

class Activities extends \Espo\Core\Services\Base
{
    protected $dependencies = array(
        'entityManager',
        'user',
        'metadata',
        'acl',
        'selectManagerFactory'
    );

    protected function getPDO()
    {
        return $this->getEntityManager()->getPDO();
    }

    protected function getEntityManager()
    {
        return $this->injections['entityManager'];
    }

    protected function getUser()
    {
        return $this->injections['user'];
    }

    protected function getAcl()
    {
        return $this->injections['acl'];
    }

    protected function getMetadata()
    {
        return $this->injections['metadata'];
    }

    protected function getSelectManagerFactory()
    {
        return $this->getInjection('selectManagerFactory');
    }

    protected function isPerson($scope)
    {
        return in_array($scope, ['Contact', 'Lead', 'User']);
    }

    protected function getUserMeetingQuery($id, $op = 'IN', $statusList = null)
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
            'leftJoins' => ['users'],
            'whereClause' => array(
                'usersMiddle.userId' => $id
            )
        );

        if (!empty($statusList)) {
            $statusOpKey = 'status';
            if ($op == 'NOT IN') {
                $statusOpKey .= '!=';
            }
            $selectParams['whereClause'][$statusOpKey] = $statusList;
        }

        $selectManager->applyAccess($selectParams);

        $sql = $this->getEntityManager()->getQuery()->createSelectQuery('Meeting', $selectParams);

        return $sql;
    }

    protected function getUserCallQuery($id, $op = 'IN', $statusList = null)
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
            'leftJoins' => ['users'],
            'whereClause' => array(
                'usersMiddle.userId' => $id
            )
        );

        if (!empty($statusList)) {
            $statusOpKey = 'status';
            if ($op == 'NOT IN') {
                $statusOpKey .= '!=';
            }
            $selectParams['whereClause'][$statusOpKey] = $statusList;
        }

        $selectManager->applyAccess($selectParams);

        $sql = $this->getEntityManager()->getQuery()->createSelectQuery('Call', $selectParams);

        return $sql;
    }

    protected function getUserEmailQuery($id, $op = 'IN', $statusList = null)
    {
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
            'leftJoins' => ['users'],
            'whereClause' => array(
                'usersMiddle.userId' => $id
            )
        );

        if (!empty($statusList)) {
            $statusOpKey = 'status';
            if ($op == 'NOT IN') {
                $statusOpKey .= '!=';
            }
            $selectParams['whereClause'][$statusOpKey] = $statusList;
        }

        $selectManager->applyAccess($selectParams);

        $sql = $this->getEntityManager()->getQuery()->createSelectQuery('Email', $selectParams);

        return $sql;
    }

    protected function getMeetingQuery($scope, $id, $op = 'IN', $statusList = null)
    {
        $methodName = 'get' .$scope . 'MeetingQuery';
        if (method_exists($this, $methodName)) {
            return $this->$methodName($id, $op, $statusList);
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
            'whereClause' => array()
        );

        if (!empty($statusList)) {
            $statusOpKey = 'status';
            if ($op == 'NOT IN') {
                $statusOpKey .= '!=';
            }
            $baseSelectParams['whereClause'][$statusOpKey] = $statusList;
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

    protected function getCallQuery($scope, $id, $op = 'IN', $statusList = null)
    {
        $methodName = 'get' .$scope . 'CallQuery';
        if (method_exists($this, $methodName)) {
            return $this->$methodName($id, $op, $notIn);
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
            $statusOpKey = 'status';
            if ($op == 'NOT IN') {
                $statusOpKey .= '!=';
            }
            $baseSelectParams['whereClause'][$statusOpKey] = $statusList;
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

    protected function getEmailQuery($scope, $id, $op = 'IN', $statusList = null)
    {
        $methodName = 'get' .$scope . 'EmailQuery';
        if (method_exists($this, $methodName)) {
            return $this->$methodName($id, $op, $statusList);
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
            'whereClause' => array()
        );

        if (!empty($statusList)) {
            $statusOpKey = 'status';
            if ($op == 'NOT IN') {
                $statusOpKey .= '!=';
            }
            $baseSelectParams['whereClause'][$statusOpKey] = $statusList;
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
        } else {
            $selectParams['whereClause']['parentId'] = $id;
            $selectParams['whereClause']['parentType'] = $scope;
        }

        $selectManager->applyAccess($selectParams);

        $sql = $this->getEntityManager()->getQuery()->createSelectQuery('Email', $selectParams);

        if ($this->isPerson($scope) || $scope == 'Account') {
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

    protected function getResult($parts, $scope, $id, $params)
    {
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
            if (!$this->getAcl()->checkPermission('userPermission', $entity)) {
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

        $parts = array();
        if ($this->getAcl()->checkScope('Meeting')) {
            $parts['Meeting'] = ($fetchAll || $params['scope'] == 'Meeting') ? $this->getMeetingQuery($scope, $id, 'NOT IN', ['Held', 'Not Held']) : [];
        }
        if ($this->getAcl()->checkScope('Call')) {
            $parts['Call'] = ($fetchAll || $params['scope'] == 'Call') ? $this->getCallQuery($scope, $id, 'NOT IN', ['Held', 'Not Held']) : [];
        }
        return $this->getResult($parts, $scope, $id, $params);
    }

    public function getHistory($scope, $id, $params)
    {
        $entity = $this->getEntityManager()->getEntity($scope, $id);

        $this->accessCheck($entity);

        $fetchAll = empty($params['scope']);

        $parts = array();
        if ($this->getAcl()->checkScope('Meeting')) {
            $parts['Meeting'] = ($fetchAll || $params['scope'] == 'Meeting') ? $this->getMeetingQuery($scope, $id, 'IN', ['Held', 'Not Held']) : [];
        }
        if ($this->getAcl()->checkScope('Call')) {
            $parts['Call'] = ($fetchAll || $params['scope'] == 'Call') ? $this->getCallQuery($scope, $id, 'IN', ['Held', 'Not Held']) : [];
        }
        if ($this->getAcl()->checkScope('Email')) {
            $parts['Email'] = ($fetchAll || $params['scope'] == 'Email') ? $this->getEmailQuery($scope, $id, 'IN', ['Archived', 'Sent']) : [];
        }
        $result = $this->getResult($parts, $scope, $id, $params);

        foreach ($result['list'] as &$item) {
            if ($item['_scope'] == 'Email') {
                $item['dateSent'] = $item['dateStart'];
            }
        }

        return $result;
    }

    public function getEvents($userId, $from, $to, $scopeList = null)
    {
        $user = $this->getEntityManager()->getEntity('User', $userId);
        if (!$user) {
            throw new NotFound();
        }
        $this->accessCheck($user);

        $pdo = $this->getPDO();

        if (is_null($scopeList)) {
            $scopeList = ['Meeting', 'Call', 'Task'];
        }

        $sqlPartList = [];

        if (in_array('Meeting', $scopeList) && $this->getAcl()->checkScope('Meeting')) {
            $sqlPartList[] = "
                SELECT
                    'Meeting' AS scope,
                    meeting.id AS id, meeting.name AS name,
                    meeting.date_start AS dateStart,
                    meeting.date_end AS dateEnd,
                    meeting.status AS status,
                    '' AS dateStartDate,
                    '' AS dateEndDate
                FROM `meeting`
                JOIN meeting_user ON meeting_user.meeting_id = meeting.id AND meeting_user.deleted = 0 AND meeting_user.status <> 'Declined'
                WHERE
                    meeting.deleted = 0 AND
                    meeting.date_start >= ".$pdo->quote($from)." AND
                    meeting.date_start < ".$pdo->quote($to)." AND
                    meeting_user.user_id =".$pdo->quote($userId)."
            ";
        };
        if (in_array('Call', $scopeList) && $this->getAcl()->checkScope('Call')) {
            $sqlPartList[] = "
                SELECT
                    'Call' AS scope,
                    call.id AS id,
                    call.name AS name,
                    call.date_start AS dateStart,
                    call.date_end AS dateEnd,
                    call.status AS status,
                    '' AS dateStartDate,
                    '' AS dateEndDate
                FROM `call`
                JOIN call_user ON call_user.call_id = call.id AND call_user.deleted = 0 AND call_user.status <> 'Declined'
                WHERE
                    call.deleted = 0 AND
                    call.date_start >= ".$pdo->quote($from)." AND
                    call.date_start < ".$pdo->quote($to)." AND
                    call_user.user_id = ".$pdo->quote($userId)."
            ";
        }
        if (in_array('Task', $scopeList) && $this->getAcl()->checkScope('Task')) {
            $sqlPartList[] = "
                SELECT
                    'Task' AS scope,
                    task.id AS id,
                    task.name AS name,
                    task.date_start AS dateStart,
                    task.date_end AS dateEnd,
                    task.status AS status,
                    task.date_start_date AS dateStartDate,
                    task.date_end_date AS dateEndDate
                FROM `task`
                WHERE
                    task.deleted = 0 AND
                    (
                        (
                            task.date_end IS NULL AND
                            task.date_start >= ".$pdo->quote($from)." AND
                            task.date_start < ".$pdo->quote($to)."
                        ) OR (
                            task.date_end >= ".$pdo->quote($from)." AND
                            task.date_end < ".$pdo->quote($to)."
                        ) OR (
                            task.date_end_date IS NOT NULL AND
                            task.date_end_date >= ".$pdo->quote($from)." AND
                            task.date_end_date < ".$pdo->quote($to)."
                        )
                    ) AND
                    task.assigned_user_id = ".$pdo->quote($userId)."
            ";
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

        $now = $dt->format('Y-m-d H:i:s');
        $nowShifted = $dt->sub(new \DateInterval('PT1H'))->format('Y-m-d H:i:s');

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
        $rows = $sth->fetchAll(PDO::FETCH_ASSOC);

        $result = array();
        foreach ($rows as $row) {
            $entity = $this->getEntityManager()->getEntity($row['entityType'], $row['entityId']);
            $data = null;
            if ($entity) {
                $data = array(
                    'id' => $entity->id,
                    'entityType' => $row['entityType'],
                    'dateStart' => $entity->get('dateStart'),
                    'name' => $entity->get('name')
                );
            }
            $result[] = array(
                'id' => $row['id'],
                'data' => $data
            );

        }
        return $result;
    }

    public function getUpcomingActivities($userId, $params = array())
    {
        $user = $this->getEntityManager()->getEntity('User', $userId);
        $this->accessCheck($user);

        $entityTypeList = ['Meeting', 'Call'];

        $unionPartList = [];
        foreach ($entityTypeList as $entityType) {
            if (!$this->getAcl()->checkScope($entityType, 'read')) {
                continue;
            }


            $selectParams = array(
                'select' => ['id', 'name', 'dateStart', ['VALUE:' . $entityType, 'entityType']],
            );

            $selectManager = $this->getSelectManagerFactory()->create($entityType);


            $selectManager->applyAccess($selectParams);

            if (!empty($prams['textFilter'])) {
                $selectManager->applyTextFilter($prams['textFilter'], $selectParams);
            }

            $selectManager->applyPrimaryFilter('planned', $selectParams);
            $selectManager->applyBoolFilter('onlyMy', $selectParams);
            $selectManager->applyWhere(array(
                '1' =>  array(
                    'type' => 'or',
                    'value' => array(
                        '1' => array(
                            'type' => 'today',
                            'field' => 'dateStart',
                            'dateTime' => true
                        ),
                        '2' => array(
                            'type' => 'future',
                            'field' => 'dateEnd',
                            'dateTime' => true
                        )
                    )
                )
            ), $selectParams);

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

        $unionSql .= " ORDER BY dateStart ASC";
        $unionSql .= " LIMIT :offset, :maxSize";

        $sth = $pdo->prepare($unionSql);

        $sth->bindParam(':offset', intval($params['offset']), \PDO::PARAM_INT);
        $sth->bindParam(':maxSize', intval($params['maxSize']), \PDO::PARAM_INT);
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

