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

namespace Espo\Services;

use \Espo\Core\Exceptions\Forbidden;
use \Espo\Core\Exceptions\NotFound;

use Espo\ORM\Entity;

class Stream extends \Espo\Core\Services\Base
{
    protected $statusStyles = null;

    protected $statusFields = null;

    protected $dependencies = array(
        'entityManager',
        'config',
        'user',
        'metadata',
        'acl',
        'aclManager',
        'container',
    );

    protected $emailsWithContentEntityList = ['Case'];

    protected $auditedFieldsCache = array();

    private $notificationService = null;

    protected function getServiceFactory()
    {
        return $this->injections['container']->get('serviceFactory');
    }

    protected function getAcl()
    {
        return $this->injections['acl'];
    }

    protected function getAclManager()
    {
        return $this->injections['aclManager'];
    }

    protected function getMetadata()
    {
        return $this->injections['metadata'];
    }

    protected function getNotificationService()
    {
        if (empty($this->notificationService)) {
            $this->notificationService = $this->getServiceFactory()->create('Notification');
        }
        return $this->notificationService;
    }

    protected function getStatusStyles()
    {
        if (empty($this->statusStyles)) {
            $this->statusStyles = $this->getMetadata()->get('entityDefs.Note.statusStyles', array());
        }
        return $this->statusStyles;
    }

    protected function getStatusFields()
    {
        if (empty($this->statusFields)) {
            $this->statusFields = $this->getMetadata()->get('entityDefs.Note.statusFields', array());
        }
        return $this->statusFields;
    }

    public function afterRecordCreatedJob($data)
    {
        if (empty($data)) {
            return;
        }
        if (empty($data['entityId']) || empty($data['entityType']) || empty($data['userIdList'])) {
            return;
        }
        $userIdList = $data['userIdList'];
        $entityType = $data['entityType'];
        $entityId = $data['entityId'];

        $entity = $this->getEntityManager()->getEntity($entityType, $entityId);
        if (!$entity) {
            return;
        }

        foreach ($userIdList as $i => $userId) {
            $user = $this->getEntityManager()->getEntity('User', $userId);
            if (!$user){
                continue;
            }
            if (!$this->getAclManager()->check($user, $entity, 'stream')) {
                unset($userIdList[$i]);
            }
        }
        $userIdList = array_values($userIdList);

        foreach ($userIdList as $i => $userId) {
            if ($this->checkIsFollowed($entity, $userId)) {
                unset($userIdList[$i]);
            }
        }
        $userIdList = array_values($userIdList);

        if (empty($userIdList)) {
            return;
        }

        $this->followEntityMass($entity, $userIdList);

        $noteList = $this->getEntityManager()->getRepository('Note')->where(array(
            'parentType' => $entityType,
            'parentId' => $entityId
        ))->order('number', 'ASC')->find();

        foreach ($noteList as $note) {
            $this->getNotificationService()->notifyAboutNote($userIdList, $note);
        }
    }

    public function checkIsFollowed(Entity $entity, $userId = null)
    {
        if (empty($userId)) {
            $userId = $this->getUser()->id;
        }

        $pdo = $this->getEntityManager()->getPDO();
        $sql = "
            SELECT id FROM subscription
            WHERE
                entity_id = " . $pdo->quote($entity->id) . " AND entity_type = " . $pdo->quote($entity->getEntityName()) . " AND
                user_id = " . $pdo->quote($userId) . "
        ";

        $sth = $pdo->prepare($sql);
        $sth->execute();
        if ($sth->fetchAll()) {
            return true;
        }
        return false;
    }

    public function followEntityMass(Entity $entity, array $sourceUserIdList)
    {
        if (!$this->getMetadata()->get('scopes.' . $entity->getEntityName() . '.stream')) {
            return false;
        }

        $userIdList = [];
        foreach ($sourceUserIdList as $id) {
            if ($id == 'system') {
                continue;
            }
            $userIdList[] = $id;
        }

        $userIdList = array_unique($userIdList);

        if (empty($userIdList)) {
            return;
        }

        $pdo = $this->getEntityManager()->getPDO();

        $sql = "
            DELETE FROM subscription WHERE user_id IN ('".implode("', '", $userIdList)."') AND entity_id = ".$pdo->quote($entity->id) . "
        ";
        $pdo->query($sql);

        $sql = "
            INSERT INTO subscription
            (entity_id, entity_type, user_id)
            VALUES
        ";
        foreach ($userIdList as $userId) {
            $arr[] = "
                (".$pdo->quote($entity->id) . ", " . $pdo->quote($entity->getEntityName()) . ", " . $pdo->quote($userId).")
            ";
        }

        $sql .= implode(", ", $arr);

        $pdo->query($sql);
    }

    public function followEntity(Entity $entity, $userId)
    {
        if ($userId == 'system') {
            return;
        }
        if (!$this->getMetadata()->get('scopes.' . $entity->getEntityName() . '.stream')) {
            return false;
        }

        $pdo = $this->getEntityManager()->getPDO();

        if (!$this->checkIsFollowed($entity, $userId)) {
            $sql = "
                INSERT INTO subscription
                (entity_id, entity_type, user_id)
                VALUES
                (".$pdo->quote($entity->id) . ", " . $pdo->quote($entity->getEntityName()) . ", " . $pdo->quote($userId).")
            ";
            $sth = $pdo->prepare($sql)->execute();
        }
        return true;
    }

    public function unfollowEntity(Entity $entity, $userId)
    {
        if (!$this->getMetadata()->get('scopes.' . $entity->getEntityName() . '.stream')) {
            return false;
        }

        $pdo = $this->getEntityManager()->getPDO();

        $sql = "
            DELETE FROM subscription
            WHERE
                entity_id = " . $pdo->quote($entity->id) . " AND entity_type = " . $pdo->quote($entity->getEntityName()) . " AND
                user_id = " . $pdo->quote($userId) . "
        ";
        $sth = $pdo->prepare($sql)->execute();

        return true;
    }


    public function unfollowAllUsersFromEntity(Entity $entity)
    {
        if (empty($entity->id)) {
            return;
        }

        $pdo = $this->getEntityManager()->getPDO();
        $sql = "
            DELETE FROM subscription
            WHERE
                entity_id = " . $pdo->quote($entity->id) . " AND entity_type = " . $pdo->quote($entity->getEntityName()) . "
        ";
        $sth = $pdo->prepare($sql)->execute();
    }

    public function findUserStream($userId, $params = array())
    {
        $offset = intval($params['offset']);
        $maxSize = intval($params['maxSize']);

        if ($userId === $this->getUser()->id) {
            $user = $this->getUser();
        } else {
            $user = $this->getEntityManager()->getEntity('User', $userId);
            if (!$user) {
                throw new NotFound();
            }
            if (!$this->getAcl()->checkUser('userPermission', $user)) {
                throw new Forbidden();
            }
        }

        $pdo = $this->getEntityManager()->getPDO();

        $select = [
            'id', 'number', 'type', 'post', 'data', 'parentType', 'parentId', 'relatedType', 'relatedId',
            'targetType', 'createdAt', 'createdById', 'createdByName', 'isGlobal', 'isInternal'
        ];

        $selectParamsList = [];

        $selectParamsSubscription = array(
            'select' => $select,
            'leftJoins' => ['createdBy'],
            'customJoin' => "
                JOIN subscription AS `subscription` ON
                    (
                        (
                            note.parent_type = subscription.entity_type AND
                            note.parent_id = subscription.entity_id
                        )
                    ) AND
                    subscription.user_id = ".$pdo->quote($user->id) . "
            ",
            'whereClause' => array(),
            'orderBy' => 'number',
            'order' => 'DESC'
        );

        if ($user->get('isPortalUser')) {
            $selectParamsSubscription['whereClause'][] = array(
                'isInternal' => false
            );
        }

        $selectParamsList[] = $selectParamsSubscription;

        $selectParamsSubscriptionSuper = array(
            'select' => $select,
            'leftJoins' => ['createdBy'],
            'customJoin' => "
                JOIN subscription AS `subscription` ON
                    (
                        (
                            note.super_parent_type = subscription.entity_type AND
                            note.super_parent_id = subscription.entity_id
                        )
                    ) AND
                    subscription.user_id = ".$pdo->quote($user->id)."
            ",
            'customWhere' => ' AND (
                    note.parent_id <> note.super_parent_id
                    OR
                    note.parent_type <> note.super_parent_type
                )
            ',
            'whereClause' => array(),
            'orderBy' => 'number',
            'order' => 'DESC'
        );

        if ($user->get('isPortalUser')) {
            $selectParamsSubscriptionSuper['whereClause'][] = array(
                'isInternal' => false
            );
        }

        $selectParamsList[] = $selectParamsSubscriptionSuper;

        $selectParamsList[] = array(
            'select' => $select,
            'leftJoins' => ['createdBy'],
            'whereClause' => array(
                'createdById' => $user->id,
                'parentId' => null,
                'type' => 'Post',
                'isGlobal' => false
            ),
            'orderBy' => 'number',
            'order' => 'DESC'
        );

        $selectParamsList[] = array(
            'select' => $select,
            'leftJoins' => ['users', 'createdBy'],
            'whereClause' => array(
                'createdById!=' => $user->id,
                'usersMiddle.userId' => $user->id,
                'parentId' => null,
                'type' => 'Post',
                'isGlobal' => false
            ),
            'orderBy' => 'number',
            'order' => 'DESC'
        );

        if (!$user->get('isPortalUser') || $user->get('isAdmin')) {
            $selectParamsList[] = array(
                'select' => $select,
                'leftJoins' => ['createdBy'],
                'whereClause' => array(
                    'parentId' => null,
                    'type' => 'Post',
                    'isGlobal' => true
                ),
                'orderBy' => 'number',
                'order' => 'DESC'
            );
        }

        if ($user->get('isPortalUser')) {
            $portalIdList = $user->getLinkMultipleIdList('portals');
            $portalIdQuotedList = [];
            foreach ($portalIdList as $portalId) {
                $portalIdQuotedList[] = $pdo->quote($portalId);
            }
            if (!empty($portalIdQuotedList)) {
                $selectParamsList[] = array(
                    'select' => $select,
                    'leftJoins' => ['portals', 'createdBy'],
                    'whereClause' => array(
                        'parentId' => null,
                        'portalsMiddle.portalId' => $portalIdList,
                        'type' => 'Post',
                        'isGlobal' => false
                    ),
                    'orderBy' => 'number',
                    'order' => 'DESC'
                );
            }
        }

        $teamIdList = $user->getTeamIdList();
        $teamIdQuotedList = [];
        foreach ($teamIdList as $teamId) {
            $teamIdQuotedList[] = $pdo->quote($teamId);
        }
        if (!empty($teamIdList)) {
            $selectParamsList[] = array(
                'select' => $select,
                'leftJoins' => ['teams', 'createdBy'],
                'whereClause' => array(
                    'parentId' => null,
                    'teamsMiddle.teamId' => $teamIdList,
                    'type' => 'Post',
                    'isGlobal' => false
                ),
                'orderBy' => 'number',
                'order' => 'DESC'
            );
        }

        $whereClause = array();
        if (!empty($params['after'])) {
            $whereClause[]['createdAt>'] = $params['after'];
        }

        if (!empty($params['filter'])) {
            switch ($params['filter']) {
                case 'posts':
                    $whereClause[]['type'] = 'Post';
                    break;
                  case 'updates':
                    $whereClause[]['type'] = ['Update', 'Status'];
                    break;
            }
        }

        $ignoreScopeList = $this->getIgnoreScopeList();

        if (!empty($ignoreScopeList)) {
            $whereClause[] = array(
                'OR' => array(
                    'relatedType' => null,
                    'relatedType!=' => $ignoreScopeList
                )
            );
            $whereClause[] = array(
                'OR' => array(
                    'parentType' => null,
                    'parentType!=' => $ignoreScopeList
                )
            );
            if (in_array('Email', $ignoreScopeList)) {
                $whereClause[] = array(
                    'type!=' => ['EmailReceived', 'EmailSent']
                );
            }
        }

        $sqlPartList = [];
        foreach ($selectParamsList as $i => $selectParams) {
            if (empty($selectParams['whereClause'])) {
                $selectParams['whereClause'] = array();
            }
            $selectParams['whereClause'][] = $whereClause;
            $sqlPartList[] = "(\n" . $this->getEntityManager()->getQuery()->createSelectQuery('Note', $selectParams) . "\n)";
        }

        $sql = implode("\n UNION \n", $sqlPartList) . "
            ORDER BY number DESC
        ";

        $sql = $this->getEntityManager()->getQuery()->limit($sql, $offset, $maxSize + 1);

        $collection = $this->getEntityManager()->getRepository('Note')->findByQuery($sql);

        foreach ($collection as $e) {
            if ($e->get('type') == 'Post' || $e->get('type') == 'EmailReceived') {
                $e->loadAttachments();
            }
        }

        foreach ($collection as $e) {
            if ($e->get('parentId') && $e->get('parentType')) {
                $entity = $this->getEntityManager()->getEntity($e->get('parentType'), $e->get('parentId'));
                if ($entity) {
                    $e->set('parentName', $entity->get('name'));
                }
            }
            if ($e->get('relatedId') && $e->get('relatedType')) {
                $entity = $this->getEntityManager()->getEntity($e->get('relatedType'), $e->get('relatedId'));
                if ($entity) {
                    $e->set('relatedName', $entity->get('name'));
                }
            }
            if ($e->get('type') == 'Post' && $e->get('parentId') === null && !$e->get('isGlobal')) {
                $targetType = $e->get('targetType');
                if (!$targetType || $targetType === 'users' || $targetType === 'self') {
                    $e->loadLinkMultipleField('users');
                }
                if ($targetType !== 'users' && $targetType !== 'self') {
                    if (!$targetType || $targetType === 'teams') {
                        $e->loadLinkMultipleField('teams');
                    } else if ($targetType === 'portals') {
                        $e->loadLinkMultipleField('portals');
                    }
                }
            }
        }

        if (count($collection) > $maxSize) {
            $total = -1;
            unset($collection[count($collection) - 1]);
        } else {
            $total = -2;
        }

        return array(
            'total' => $total,
            'collection' => $collection,
        );
    }

    public function find($scope, $id, $params = array())
    {
        if ($scope === 'User') {
            if (empty($id)) {
                $id = $this->getUser()->id;
            }
            return $this->findUserStream($id, $params);
        }
        $entity = $this->getEntityManager()->getEntity($scope, $id);

        if (empty($entity)) {
            throw new NotFound();
        }

        if (!$this->getAcl()->checkEntity($entity, 'stream')) {
            throw new Forbidden();
        }

        $where = array(
            'OR' => array(
                array(
                    'parentType' => $scope,
                    'parentId' => $id
                ),
                array(
                    'superParentType' => $scope,
                    'superParentId' => $id
                )
            )
        );

        if (!empty($params['after'])) {
            $where['createdAt>'] = $params['after'];
        }

        if (!empty($params['filter'])) {
            switch ($params['filter']) {
                case 'posts':
                    $where['type'] = 'Post';
                    break;
                  case 'updates':
                    $where['type'] = ['Update', 'Status'];
                    break;
            }
        }

        $ignoreScopeList = $this->getIgnoreScopeList();
        if (!empty($ignoreScopeList)) {
            $where[] = array(
                'OR' => array(
                    'relatedType' => null,
                    'relatedType!=' => $ignoreScopeList
                )
            );
            $where[] = array(
                'OR' => array(
                    'parentType' => null,
                    'parentType!=' => $ignoreScopeList
                )
            );
            if (in_array('Email', $ignoreScopeList)) {
                $where[] = array(
                    'type!=' => ['EmailReceived', 'EmailSent']
                );
            }
        }

        if ($this->getUser()->get('isPortalUser')) {
            $where[] = array(
                'isInternal' => false
            );
        }

        $collection = $this->getEntityManager()->getRepository('Note')->find(array(
            'whereClause' => $where,
            'offset' => $params['offset'],
            'limit' => $params['maxSize'],
            'orderBy' => 'number',
            'order' => 'DESC'
        ));


        foreach ($collection as $e) {
            if ($e->get('type') == 'Post' || $e->get('type') == 'EmailReceived') {
                $e->loadAttachments();
            }

            if ($e->get('parentId') && $e->get('parentType')) {
                if (
                    ($e->get('parentId') != $id) ||
                    ($e->get('parentType') != $scope)
                ) {
                    $parent = $this->getEntityManager()->getEntity($e->get('parentType'), $e->get('parentId'));
                    if ($parent) {
                        $e->set('parentName', $parent->get('name'));
                    }
                }
            }
            if ($e->get('relatedId') && $e->get('relatedType')) {
                $entity = $this->getEntityManager()->getEntity($e->get('relatedType'), $e->get('relatedId'));
                if ($entity) {
                    $e->set('relatedName', $entity->get('name'));
                }
            }

        }

        unset($where['createdAt>']);
        $count = $this->getEntityManager()->getRepository('Note')->count(array(
            'whereClause' => $where,
        ));

        return array(
            'total' => $count,
            'collection' => $collection,
        );
    }

    protected function loadAssignedUserName(Entity $entity)
    {
        $user = $this->getEntityManager()->getEntity('User', $entity->get('assignedUserId'));
        if ($user) {
            $entity->set('assignedUserName', $user->get('name'));
        }
    }

    public function noteEmailReceived(Entity $entity, Entity $email, $isInitial = false)
    {
        $entityType = $entity->getEntityType();

        if ($this->getEntityManager()->getRepository('Note')->where(array(
            'type' => 'EmailReceived',
            'parentId' => $entity->id,
            'parentType' => $entityType,
            'relatedId' => $email->id,
            'relatedType' => 'Email'
        ))->findOne()) {
            return;
        }

        $note = $this->getEntityManager()->getEntity('Note');

        $note->set('type', 'EmailReceived');
        $note->set('parentId', $entity->id);
        $note->set('parentType', $entityType);
        $note->set('relatedId', $email->id);
        $note->set('relatedType', 'Email');

        if ($email->get('accountId')) {
            $note->set('superParentId', $email->get('accountId'));
            $note->set('superParentType', 'Account');
        }

        $withContent = in_array($entityType, $this->emailsWithContentEntityList);

        if ($withContent) {
            $note->set('post', $email->getBodyPlain());
        }

        $data = array();

        $data['emailId'] = $email->id;
        $data['emailName'] = $email->get('name');
        $data['isInitial'] = $isInitial;

        if ($withContent) {
            $data['attachmentsIds'] = $email->get('attachmentsIds');
        }

        $from = $email->get('from');
        if ($from) {
            $person = $this->getEntityManager()->getRepository('EmailAddress')->getEntityByAddress($from);
            if ($person) {
                $data['personEntityType'] = $person->getEntityName();
                $data['personEntityName'] = $person->get('name');
                $data['personEntityId'] = $person->id;
            }
        }

        $note->set('data', $data);


        $this->getEntityManager()->saveEntity($note);
    }

    public function noteEmailSent(Entity $entity, Entity $email)
    {
        $entityType = $entity->getEntityType();

        $note = $this->getEntityManager()->getEntity('Note');

        $note->set('type', 'EmailSent');
        $note->set('parentId', $entity->id);
        $note->set('parentType', $entityType);
        $note->set('relatedId', $email->id);
        $note->set('relatedType', 'Email');

        if ($email->get('accountId')) {
            $note->set('superParentId', $email->get('accountId'));
            $note->set('superParentType', 'Account');
        }

        $withContent = in_array($entityType, $this->emailsWithContentEntityList);

        if ($withContent) {
            $note->set('post', $email->getBodyPlain());
        }

        $data = array();
        $data['emailId'] = $email->id;
        $data['emailName'] = $email->get('name');

        if ($withContent) {
            $data['attachmentsIds'] = $email->get('attachmentsIds');
        }

        $user = $this->getUser();

        if ($user->id != 'system') {
            $person = $user;
        } else {
            $from = $email->get('from');
            if ($from) {
                $person = $this->getEntityManager()->getRepository('EmailAddress')->getEntityByAddress($from);
            }
        }

        if ($person) {
            $data['personEntityType'] = $person->getEntityName();
            $data['personEntityName'] = $person->get('name');
            $data['personEntityId'] = $person->id;
        }

        $note->set('data', $data);

        $this->getEntityManager()->saveEntity($note);
    }

    public function noteCreate(Entity $entity)
    {
        $entityType = $entity->getEntityType();

        $note = $this->getEntityManager()->getEntity('Note');

        $note->set('type', 'Create');
        $note->set('parentId', $entity->id);
        $note->set('parentType', $entityType);

        if ($entity->has('accountId') && $entity->get('accountId')) {
            $note->set('superParentId', $entity->get('accountId'));
            $note->set('superParentType', 'Account');
        }

        $data = array();

        if ($entity->get('assignedUserId') != $entity->get('createdById')) {
            if (!$entity->has('assignedUserName')) {
                $this->loadAssignedUserName($entity);
            }
            $data['assignedUserId'] = $entity->get('assignedUserId');
            $data['assignedUserName'] = $entity->get('assignedUserName');
        }

        $statusFields = $this->getStatusFields();

        if (!empty($statusFields[$entityType])) {
            $field = $statusFields[$entityType];
            $value = $entity->get($field);
            if (!empty($value)) {
                $statusStyles = $this->getStatusStyles();
                $style = 'default';
                if (!empty($statusStyles[$entityType]) && !empty($statusStyles[$entityType][$value])) {
                    $style = $statusStyles[$entityType][$value];
                }
                $data['statusValue'] = $value;
                $data['statusField'] = $field;
                $data['statusStyle'] = $style;
            }
        }

        $note->set('data', $data);

        $this->getEntityManager()->saveEntity($note);
    }

    public function noteCreateRelated(Entity $entity, $parentType, $parentId)
    {
        $note = $this->getEntityManager()->getEntity('Note');

        $entityType = $entity->getEntityType();

        $note->set('type', 'CreateRelated');
        $note->set('parentId', $parentId);
        $note->set('parentType', $parentType);
        $note->set(array(
            'relatedType' => $entityType,
            'relatedId' => $entity->id,
        ));

        if ($entity->has('accountId') && $entity->get('accountId')) {
            $note->set('superParentId', $entity->get('accountId'));
            $note->set('superParentType', 'Account');
        }

        $this->getEntityManager()->saveEntity($note);
    }

    public function noteAssign(Entity $entity)
    {
        $note = $this->getEntityManager()->getEntity('Note');

        $note->set('type', 'Assign');
        $note->set('parentId', $entity->id);
        $note->set('parentType', $entity->getEntityType());

        if ($entity->has('accountId') && $entity->get('accountId')) {
            $note->set('superParentId', $entity->get('accountId'));
            $note->set('superParentType', 'Account');
        }

        if (!$entity->has('assignedUserName')) {
            $this->loadAssignedUserName($entity);
        }
        $note->set('data', array(
            'assignedUserId' => $entity->get('assignedUserId'),
            'assignedUserName' => $entity->get('assignedUserName'),
        ));

        $this->getEntityManager()->saveEntity($note);
    }

    public function noteStatus(Entity $entity, $field)
    {
        $note = $this->getEntityManager()->getEntity('Note');

        $note->set('type', 'Status');
        $note->set('parentId', $entity->id);
        $note->set('parentType', $entity->getEntityType());

        if ($entity->has('accountId') && $entity->get('accountId')) {
            $note->set('superParentId', $entity->get('accountId'));
            $note->set('superParentType', 'Account');
        }

        $style = 'default';
        $entityType = $entity->getEntityType();
        $value = $entity->get($field);

        $statusStyles = $this->getStatusStyles();

        if (!empty($statusStyles[$entityType]) && !empty($statusStyles[$entityType][$value])) {
            $style = $statusStyles[$entityType][$value];
        }

        $note->set('data', array(
            'field' => $field,
            'value' => $value,
            'style' => $style,
        ));

        $this->getEntityManager()->saveEntity($note);
    }

    protected function getAuditedFields(Entity $entity)
    {
        $entityType = $entity->getEntityType();

        $statusFields = $this->getStatusFields();

        if (!array_key_exists($entityType, $this->auditedFieldsCache)) {
            $fields = $this->getMetadata()->get('entityDefs.' . $entityType . '.fields');
            $auditedFields = array();
            foreach ($fields as $field => $d) {
                if (!empty($d['audited'])) {
                    if (!empty($statusFields[$entityType]) && $statusFields[$entityType] === $field) {
                        continue;
                    }

                    $attributes = [];
                    $fieldsDefs = $this->getMetadata()->get('fields.' . $d['type']);

                    if (empty($fieldsDefs['actualFields'])) {
                        $attributes[] = $field;
                    } else {
                        foreach ($fieldsDefs['actualFields'] as $part) {
                            if (!empty($fieldsDefs['naming']) && $fieldsDefs['naming'] == 'prefix') {
                                $attributes[] = $part . ucfirst($field);
                            } else {
                                $attributes[] = $field . ucfirst($part);
                            }
                        }
                    }

                    $auditedFields[$field] = $attributes;
                }
            }
            $this->auditedFieldsCache[$entityType] = $auditedFields;
        }

        return $this->auditedFieldsCache[$entityType];
    }

    public function handleAudited($entity)
    {
        $auditedFields = $this->getAuditedFields($entity);

        $updatedFields = array();
        $was = array();
        $became = array();

        foreach ($auditedFields as $field => $attrs) {
            $updated = false;
            foreach ($attrs as $attr) {
                if ($entity->get($attr) != $entity->getFetched($attr)) {
                    $updated = true;
                }
            }
            if ($updated) {
                $updatedFields[] = $field;
                foreach ($attrs as $attr) {
                    $was[$attr] = $entity->getFetched($attr);
                    $became[$attr] = $entity->get($attr);
                }
            }
        }

        if (!empty($updatedFields)) {
            $note = $this->getEntityManager()->getEntity('Note');

            $note->set('type', 'Update');
            $note->set('parentId', $entity->id);
            $note->set('parentType', $entity->getEntityName());

            $note->set('data', array(
                'fields' => $updatedFields,
                'attributes' => array(
                    'was' => $was,
                    'became' => $became,
                )
            ));

            $this->getEntityManager()->saveEntity($note);
        }
    }

    public function getEntityFollowers(Entity $entity, $offset = 0, $limit = false)
    {
        $query = $this->getEntityManager()->getQuery();
        $pdo = $this->getEntityManager()->getPDO();

        if (!$limit) {
            $limit = 200;
        }

        $sql = $query->createSelectQuery('User', array(
            'select' => ['id', 'name'],
            'customJoin' => "
                JOIN subscription AS `subscription` ON
                    subscription.user_id = user.id AND
                    subscription.entity_id = ".$query->quote($entity->id)." AND
                    subscription.entity_type = ".$query->quote($entity->getEntityType())."
            ",
            'offset' => $offset,
            'limit' => $limit,
            'whereClause' => array(
                'isActive' => true
            ),
            'orderBy' => [
                ['LIST:id:' . $this->getUser()->id, 'DESC'],
                ['name']
            ]
        ));

        $sth = $pdo->prepare($sql);
        $sth->execute();

        $data = array(
            'idList' => [],
            'nameMap' => new \StdClass()
        );

        while ($row = $sth->fetch(\PDO::FETCH_ASSOC)) {
            $id = $row['id'];
            $data['idList'][] = $id;
            $data['nameMap']->$id = $row['name'];
        }

        return $data;

    }

    protected function getIgnoreScopeList()
    {
        $ignoreScopeList = [];
        $scopes = $this->getMetadata()->get('scopes', array());
        foreach ($scopes as $scope => $d) {
            if (empty($d['entity']) || !$d['entity']) continue;
            if (empty($d['object']) || !$d['object']) continue;
            if (!$this->getAcl()->checkScope($scope)) {
                $ignoreScopeList[] = $scope;
            }
        }
        return $ignoreScopeList;
    }
}

