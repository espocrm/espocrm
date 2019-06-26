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

namespace Espo\Services;

use \Espo\Core\Exceptions\Forbidden;
use \Espo\Core\Exceptions\NotFound;

use Espo\ORM\Entity;

class Stream extends \Espo\Core\Services\Base
{
    protected $statusStyles = null;

    protected $statusFields = null;

    protected $successDefaultStyleList = ['Held', 'Closed Won', 'Closed', 'Completed', 'Complete', 'Sold'];

    protected $dangerDefaultStyleList = ['Not Held', 'Closed Lost', 'Dead'];

    protected function init()
    {
        parent::init();
        $this->addDependencyList([
            'entityManager',
            'config',
            'user',
            'metadata',
            'acl',
            'aclManager',
            'container'
        ]);
    }

    protected $emailsWithContentEntityList = ['Case'];

    protected $auditedFieldsCache = array();

    private $notificationService = null;

    protected function getServiceFactory()
    {
        return $this->getInjection('container')->get('serviceFactory');
    }

    protected function getAcl()
    {
        return $this->getInjection('acl');
    }

    protected function getAclManager()
    {
        return $this->getInjection('aclManager');
    }

    protected function getMetadata()
    {
        return $this->getInjection('metadata');
    }

    protected function getFieldManager()
    {
        return $this->getInjection('container')->get('fieldManager');
    }

    protected function getSelectMangerFactory()
    {
        return $this->getInjection('container')->get('selectManagerFactory');
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
        if (is_null($this->statusFields)) {
            $this->statusFields = array();
            $scopes = $this->getMetadata()->get('scopes', array());
            foreach ($scopes as $scope => $data) {
                if (empty($data['statusField'])) continue;
                $this->statusFields[$scope] = $data['statusField'];
            }
        }
        return $this->statusFields;
    }

    public function afterRecordCreatedJob($data)
    {
        if (empty($data)) {
            return;
        }
        if (empty($data->entityId) || empty($data->entityType) || empty($data->userIdList)) {
            return;
        }
        $userIdList = $data->userIdList;
        $entityType = $data->entityType;
        $entityId = $data->entityId;

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

        $userIdQuotedList = [];
        foreach ($userIdList as $userId) {
            $userIdQuotedList[] = $pdo->quote($userId);
        }

        $sql = "
            DELETE FROM subscription WHERE user_id IN (".implode(', ', $userIdQuotedList).") AND entity_id = ".$pdo->quote($entity->id) . "
        ";
        $pdo->query($sql);

        $sql = "
            INSERT INTO subscription
            (entity_id, entity_type, user_id)
            VALUES
        ";
        foreach ($userIdList as $userId) {
            $arr[] = "
                (".$pdo->quote($entity->id) . ", " . $pdo->quote($entity->getEntityType()) . ", " . $pdo->quote($userId).")
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
                entity_id = " . $pdo->quote($entity->id) . " AND entity_type = " . $pdo->quote($entity->getEntityType()) . "
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

        $teamIdList = $user->getTeamIdList();

        $pdo = $this->getEntityManager()->getPDO();

        $select = [
            'id', 'number', 'type', 'post', 'data', 'parentType', 'parentId', 'relatedType', 'relatedId',
            'targetType', 'createdAt', 'createdById', 'createdByName', 'isGlobal', 'isInternal', 'createdByGender'
        ];

        $onlyTeamEntityTypeList = $this->getOnlyTeamEntityTypeList($user);
        $onlyOwnEntityTypeList = $this->getOnlyOwnEntityTypeList($user);

        $additionalSelectParams = null;
        if (!empty($params['where'])) {
            $selectManager = $this->getSelectMangerFactory()->create('Note');
            $additionalSelectParams = $selectManager->getSelectParams([
                'where' => $params['where']
            ], false, true);
        }

        $selectParamsList = [];

        $selectParamsSubscription = [
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
                    subscription.user_id = ". $pdo->quote($user->id) ."
            ",
            'whereClause' => [],
            'orderBy' => 'number',
            'order' => 'DESC'
        ];

        if ($user->isPortal()) {
            $selectParamsSubscription['whereClause'][] = [
                'isInternal' => false
            ];

            $notAllEntityTypeList = $this->getNotAllEntityTypeList($user);

            $selectParamsSubscription['whereClause'][] = [
                'OR' => [
                    [
                        'relatedId' => null
                    ],
                    [
                        'relatedId!=' => null,
                        'relatedType!=' => $notAllEntityTypeList
                    ]
                ]
            ];

            $selectParamsList[] = $selectParamsSubscription;
        } else {
            $selectParamsSubscriptionRest = $selectParamsSubscription;
            $selectParamsSubscriptionRest['whereClause'][] = [
                'OR' => [
                    [
                        'relatedId!=' => null,
                        'relatedType!=' => array_merge($onlyTeamEntityTypeList, $onlyOwnEntityTypeList)
                    ],
                    [
                        'relatedId=' => null
                    ]
                ]
            ];
            $selectParamsList[] = $selectParamsSubscriptionRest;

            if (count($onlyTeamEntityTypeList)) {
                $selectParamsSubscriptionTeam = $selectParamsSubscription;
                $selectParamsSubscriptionTeam['distinct'] = true;
                $selectParamsSubscriptionTeam['leftJoins'][] = ['noteTeam', 'noteTeam', ['noteTeam.noteId=:' => 'id', 'noteTeam.deleted' => false]];
                $selectParamsSubscriptionTeam['leftJoins'][] = ['noteUser', 'noteUser', ['noteUser.noteId=:' => 'id', 'noteUser.deleted' => false]];
                $selectParamsSubscriptionTeam['whereClause'][] = [
                    [
                        'relatedId!=' => null,
                        'relatedType=' => $onlyTeamEntityTypeList
                    ],
                    [
                        'OR' => [
                            'noteTeam.teamId' => $teamIdList,
                            'noteUser.userId' => $user->id
                        ]
                    ]
                ];
                $selectParamsList[] = $selectParamsSubscriptionTeam;
            }

            if (count($onlyOwnEntityTypeList)) {
                $selectParamsSubscriptionOwn = $selectParamsSubscription;
                $selectParamsSubscriptionOwn['distinct'] = true;
                $selectParamsSubscriptionOwn['leftJoins'][] = ['noteUser', 'noteUser', ['noteUser.noteId=:' => 'id', 'noteUser.deleted' => false]];
                $selectParamsSubscriptionOwn['whereClause'][] = [
                    [
                        'relatedId!=' => null,
                        'relatedType=' => $onlyOwnEntityTypeList
                    ],
                    'noteUser.userId' => $user->id
                ];
                $selectParamsList[] = $selectParamsSubscriptionOwn;
            }
        }

        $selectParamsSubscriptionSuper = [
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
            'whereClause' => [],
            'orderBy' => 'number',
            'order' => 'DESC'
        ];

        if ($user->isPortal()) {

        } else {
            $selectParamsSubscriptionRest = $selectParamsSubscriptionSuper;
            $selectParamsSubscriptionRest['whereClause'][] = [
                'OR' => [
                    [
                        'relatedId!=' => null,
                        'relatedType!=' => array_merge($onlyTeamEntityTypeList, $onlyOwnEntityTypeList)
                    ],
                    [
                        'relatedId=' => null,
                        'parentType!=' => array_merge($onlyTeamEntityTypeList, $onlyOwnEntityTypeList)
                    ]
                ]
            ];
            $selectParamsList[] = $selectParamsSubscriptionRest;

            if (count($onlyTeamEntityTypeList)) {
                $selectParamsSubscriptionTeam = $selectParamsSubscriptionSuper;
                $selectParamsSubscriptionTeam['distinct'] = true;
                $selectParamsSubscriptionTeam['leftJoins'][] = ['noteTeam', 'noteTeam', ['noteTeam.noteId=:' => 'id', 'noteTeam.deleted' => false]];
                $selectParamsSubscriptionTeam['leftJoins'][] = ['noteUser', 'noteUser', ['noteUser.noteId=:' => 'id', 'noteUser.deleted' => false]];
                $selectParamsSubscriptionTeam['whereClause'][] = [
                    'OR' => [
                        [
                            'relatedId!=' => null,
                            'relatedType=' => $onlyTeamEntityTypeList
                        ],
                        [
                            'relatedId=' => null,
                            'parentType=' => $onlyTeamEntityTypeList
                        ]
                    ],
                    [
                        'OR' => [
                            'noteTeam.teamId' => $teamIdList,
                            'noteUser.userId' => $user->id
                        ]
                    ]
                ];
                $selectParamsList[] = $selectParamsSubscriptionTeam;
            }

            if (count($onlyOwnEntityTypeList)) {
                $selectParamsSubscriptionOwn = $selectParamsSubscriptionSuper;
                $selectParamsSubscriptionOwn['distinct'] = true;
                $selectParamsSubscriptionOwn['leftJoins'][] = ['noteUser', 'noteUser', ['noteUser.noteId=:' => 'id', 'noteUser.deleted' => false]];
                $selectParamsSubscriptionOwn['whereClause'][] = [
                    'OR' => [
                        [
                            'relatedId!=' => null,
                            'relatedType=' => $onlyOwnEntityTypeList
                        ],
                        [
                            'relatedId=' => null,
                            'parentType=' => $onlyOwnEntityTypeList
                        ]
                    ],
                    'noteUser.userId' => $user->id
                ];
                $selectParamsList[] = $selectParamsSubscriptionOwn;
            }
        }

        $selectParamsList[] = [
            'select' => $select,
            'leftJoins' => ['createdBy'],
            'whereClause' => [
                'createdById' => $user->id,
                'parentId' => null,
                'type' => 'Post',
                'isGlobal' => false
            ],
            'orderBy' => 'number',
            'order' => 'DESC'
        ];

        $selectParamsList[] = [
            'select' => $select,
            'leftJoins' => ['users', 'createdBy'],
            'whereClause' => [
                'createdById!=' => $user->id,
                'usersMiddle.userId' => $user->id,
                'parentId' => null,
                'type' => 'Post',
                'isGlobal' => false
            ],
            'orderBy' => 'number',
            'order' => 'DESC'
        ];

        if ((!$user->isPortal() || $user->isAdmin()) && !$user->isApi()) {
            $selectParamsList[] = [
                'select' => $select,
                'leftJoins' => ['createdBy'],
                'whereClause' => [
                    'parentId' => null,
                    'type' => 'Post',
                    'isGlobal' => true
                ],
                'orderBy' => 'number',
                'order' => 'DESC'
            ];
        }

        if ($user->isPortal()) {
            $portalIdList = $user->getLinkMultipleIdList('portals');
            $portalIdQuotedList = [];
            foreach ($portalIdList as $portalId) {
                $portalIdQuotedList[] = $pdo->quote($portalId);
            }
            if (!empty($portalIdQuotedList)) {
                $selectParamsList[] = [
                    'select' => $select,
                    'leftJoins' => ['portals', 'createdBy'],
                    'whereClause' => [
                        'parentId' => null,
                        'portalsMiddle.portalId' => $portalIdList,
                        'type' => 'Post',
                        'isGlobal' => false
                    ],
                    'orderBy' => 'number',
                    'order' => 'DESC'
                ];
            }
        }

        if (!empty($teamIdList)) {
            $selectParamsList[] = [
                'select' => $select,
                'leftJoins' => ['teams', 'createdBy'],
                'whereClause' => [
                    'parentId' => null,
                    'teamsMiddle.teamId' => $teamIdList,
                    'type' => 'Post',
                    'isGlobal' => false
                ],
                'orderBy' => 'number',
                'order' => 'DESC'
            ];
        }

        $whereClause = [];
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

        $ignoreScopeList = $this->getIgnoreScopeList($user);

        if (!empty($ignoreScopeList)) {
            $whereClause[] = [
                'OR' => [
                    'relatedType' => null,
                    'relatedType!=' => $ignoreScopeList
                ]
            ];
            $whereClause[] = [
                'OR' => [
                    'parentType' => null,
                    'parentType!=' => $ignoreScopeList
                ]
            ];
            if (in_array('Email', $ignoreScopeList)) {
                $whereClause[] = [
                    'type!=' => ['EmailReceived', 'EmailSent']
                ];
            }
        }

        if ($additionalSelectParams) {
            foreach ($selectParamsList as $i => $selectParams) {
                $selectParamsList[$i] = $selectManager->mergeSelectParams($selectParams, $additionalSelectParams);
            }
        }

        $sqlPartList = [];
        foreach ($selectParamsList as $i => $selectParams) {
            if (empty($selectParams['whereClause'])) {
                $selectParams['whereClause'] = [];
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
                $e->loadParentNameField('parent');
            }
            if ($e->get('relatedId') && $e->get('relatedType')) {
                $e->loadParentNameField('related');
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

        return (object) [
            'total' => $total,
            'collection' => $collection,
        ];
    }

    public function find($scope, $id, $params = [])
    {
        if ($scope === 'User') {
            if (empty($id)) {
                $id = $this->getUser()->id;
            }
            return $this->findUserStream($id, $params);
        }
        $entity = $this->getEntityManager()->getEntity($scope, $id);

        $onlyTeamEntityTypeList = $this->getOnlyTeamEntityTypeList($this->getUser());
        $onlyOwnEntityTypeList = $this->getOnlyOwnEntityTypeList($this->getUser());

        if (empty($entity)) {
            throw new NotFound();
        }

        if (!$this->getAcl()->checkEntity($entity, 'stream')) {
            throw new Forbidden();
        }

        $selectParams = [
            'offset' => $params['offset'],
            'limit' => $params['maxSize'],
            'orderBy' => 'number',
            'order' => 'DESC'
        ];

        $where = [
            'OR' => [
                [
                    'parentType' => $scope,
                    'parentId' => $id
                ],
                [
                    'superParentType' => $scope,
                    'superParentId' => $id
                ]
            ]
        ];

        if ($this->getUser()->isPortal()) {
            $where = [
                'OR' => [
                    [
                        'parentType' => $scope,
                        'parentId' => $id
                    ]
                ]
            ];
            $notAllEntityTypeList = $this->getNotAllEntityTypeList($this->getUser());
            $where[] = [
                'OR' => [
                    [
                        'relatedId' => null
                    ],
                    [
                        'relatedId!=' => null,
                        'relatedType!=' => $notAllEntityTypeList
                    ]
                ]
            ];
        } else {
            if (count($onlyTeamEntityTypeList) || count($onlyOwnEntityTypeList)) {
                $selectParams['leftJoins'] = ['teams', 'users'];
                $selectParams['distinct'] = true;
                $where[] = [
                    'OR' => [
                        'OR' => [
                            [
                                'relatedId!=' => null,
                                'relatedType!=' => array_merge($onlyTeamEntityTypeList, $onlyOwnEntityTypeList)
                            ],
                            [
                                'relatedId=' => null,
                                'superParentId' => $id,
                                'superParentType' => $scope,
                                'parentId!=' => null,
                                'parentType!=' => array_merge($onlyTeamEntityTypeList, $onlyOwnEntityTypeList)
                            ],
                            [
                                'relatedId=' => null,
                                'parentType=' => $scope,
                                'parentId=' => $id
                            ]
                        ],
                        [
                            'OR' => [
                                [
                                    'relatedId!=' => null,
                                    'relatedType=' => $onlyTeamEntityTypeList
                                ],
                                [
                                    'relatedId=' => null,
                                    'parentType=' => $onlyTeamEntityTypeList
                                ]
                            ],
                            [
                                'OR' => [
                                    'teamsMiddle.teamId' => $this->getUser()->getTeamIdList(),
                                    'usersMiddle.userId' => $this->getUser()->id
                                ]
                            ]
                        ],
                        [
                            'OR' => [
                                [
                                    'relatedId!=' => null,
                                    'relatedType=' => $onlyOwnEntityTypeList
                                ],
                                [
                                    'relatedId=' => null,
                                    'parentType=' => $onlyOwnEntityTypeList
                                ]
                            ],
                            'usersMiddle.userId' => $this->getUser()->id
                        ]
                    ]
                ];
            }
        }

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

        $ignoreScopeList = $this->getIgnoreScopeList($this->getUser());
        if (!empty($ignoreScopeList)) {
            $where[] = [
                'OR' => [
                    'relatedType' => null,
                    'relatedType!=' => $ignoreScopeList
                ]
            ];
            $where[] = [
                'OR' => [
                    'parentType' => null,
                    'parentType!=' => $ignoreScopeList
                ]
            ];
            if (in_array('Email', $ignoreScopeList)) {
                $where[] = [
                    'type!=' => ['EmailReceived', 'EmailSent']
                ];
            }
        }

        if ($this->getUser()->isPortal()) {
            $where[] = [
                'isInternal' => false
            ];
        }

        $selectParams['whereClause'] = $where;

        if (!empty($params['where'])) {
            $selectManager = $this->getSelectMangerFactory()->create('Note');
            $additionalSelectParams = $selectManager->getSelectParams([
                'where' => $params['where']
            ], false, true);
            $selectParams = $selectManager->mergeSelectParams($selectParams, $additionalSelectParams);

            $where = $selectParams['whereClause'];
        }

        $collection = $this->getEntityManager()->getRepository('Note')->find($selectParams);

        foreach ($collection as $e) {
            if ($e->get('type') == 'Post' || $e->get('type') == 'EmailReceived') {
                $e->loadAttachments();
            }

            if ($e->get('parentId') && $e->get('parentType')) {
                if (
                    ($e->get('parentId') != $id) ||
                    ($e->get('parentType') != $scope)
                ) {
                    $e->loadParentNameField('parent');
                }
            }
            if ($e->get('relatedId') && $e->get('relatedType')) {
                $e->loadParentNameField('related');
            }
        }

        unset($where['createdAt>']);

        unset($selectParams['offset']);
        unset($selectParams['limit']);

        $selectParams['where'] = $where;
        $count = $this->getEntityManager()->getRepository('Note')->count($selectParams);

        return (object) [
            'total' => $count,
            'collection' => $collection
        ];
    }

    protected function loadAssignedUserName(Entity $entity)
    {
        $user = $this->getEntityManager()->getEntity('User', $entity->get('assignedUserId'));
        if ($user) {
            $entity->set('assignedUserName', $user->get('name'));
        }
    }

    protected function processNoteTeamsUsers(Entity $note, Entity $entity)
    {
        $note->setAclIsProcessed();
        $note->set('teamsIds', []);
        $note->set('usersIds', []);

        if ($entity->hasLinkMultipleField('teams') && $entity->has('teamsIds')) {
            $teamIdList = $entity->get('teamsIds');
            if (!empty($teamIdList)) {
                $note->set('teamsIds', $teamIdList);
            }
        }

        $ownerUserIdAttribute = $this->getAclManager()->getImplementation($entity->getEntityType())->getOwnerUserIdAttribute($entity);
        if ($ownerUserIdAttribute && $entity->get($ownerUserIdAttribute)) {
            if ($entity->getAttributeParam($ownerUserIdAttribute, 'isLinkMultipleIdList')) {
                $userIdList = $entity->get($ownerUserIdAttribute);
            } else {
                $userId = $entity->get($ownerUserIdAttribute);
                $userIdList = [$userId];
            }
            $note->set('usersIds', $userIdList);
        }
    }

    public function noteEmailReceived(Entity $entity, Entity $email, $isInitial = false)
    {
        $entityType = $entity->getEntityType();

        if ($this->getEntityManager()->getRepository('Note')->where([
            'type' => 'EmailReceived',
            'parentId' => $entity->id,
            'parentType' => $entityType,
            'relatedId' => $email->id,
            'relatedType' => 'Email'
        ])->findOne()) {
            return;
        }

        $note = $this->getEntityManager()->getEntity('Note');

        $note->set('type', 'EmailReceived');
        $note->set('parentId', $entity->id);
        $note->set('parentType', $entityType);
        $note->set('relatedId', $email->id);
        $note->set('relatedType', 'Email');

        $this->processNoteTeamsUsers($note, $email);

        if ($email->get('accountId')) {
            $note->set('superParentId', $email->get('accountId'));
            $note->set('superParentType', 'Account');
        }

        $withContent = in_array($entityType, $this->emailsWithContentEntityList);

        if ($withContent) {
            $note->set('post', $email->getBodyPlain());
        }

        $data = [];

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

        $note->set('data', (object) $data);


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

        $this->processNoteTeamsUsers($note, $email);

        if ($email->get('accountId')) {
            $note->set('superParentId', $email->get('accountId'));
            $note->set('superParentType', 'Account');
        }

        $withContent = in_array($entityType, $this->emailsWithContentEntityList);

        if ($withContent) {
            $note->set('post', $email->getBodyPlain());
        }

        $data = [];
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

        $note->set('data', (object) $data);

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

            $this->processNoteTeamsUsers($note, $entity);
        }

        $data = [];

        if ($entity->get('assignedUserId')) {
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
                $data['statusValue'] = $value;
                $data['statusField'] = $field;
                $data['statusStyle'] = $this->getStatusStyle($entityType, $field, $value);
            }
        }

        $note->set('data', (object) $data);

        $this->getEntityManager()->saveEntity($note);
    }

    protected function getStatusStyle($entityType, $field, $value)
    {
        $style = $this->getMetadata()->get(['entityDefs', $entityType, 'fields', $field, 'style', $value]);
        if ($style) {
            return $style;
        }

        $statusStyles = $this->getStatusStyles();
        $style = 'default';
        if (!empty($statusStyles[$entityType]) && !empty($statusStyles[$entityType][$value])) {
            $style = $statusStyles[$entityType][$value];
        } else {
            if (in_array($value, $this->successDefaultStyleList)) {
                $style = 'success';
            } else if (in_array($value, $this->dangerDefaultStyleList)) {
                $style = 'danger';
            }
        }

        return $style;
    }

    public function noteCreateRelated(Entity $entity, $parentType, $parentId)
    {
        $note = $this->getEntityManager()->getEntity('Note');

        $entityType = $entity->getEntityType();

        $note->set('type', 'CreateRelated');
        $note->set('parentId', $parentId);
        $note->set('parentType', $parentType);
        $note->set([
            'relatedType' => $entityType,
            'relatedId' => $entity->id
        ]);

        $this->processNoteTeamsUsers($note, $entity);

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

            $this->processNoteTeamsUsers($note, $entity);
        }

        if ($entity->get('assignedUserId')) {
            if (!$entity->has('assignedUserName')) {
                $this->loadAssignedUserName($entity);
            }
            $note->set('data', [
                'assignedUserId' => $entity->get('assignedUserId'),
                'assignedUserName' => $entity->get('assignedUserName'),
            ]);
        } else {
            $note->set('data', [
                'assignedUserId' => null
            ]);
        }

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

            $this->processNoteTeamsUsers($note, $entity);
        }

        $entityType = $entity->getEntityType();
        $value = $entity->get($field);

        $style = $this->getStatusStyle($entityType, $field, $value);

        $note->set('data', [
            'field' => $field,
            'value' => $value,
            'style' => $style
        ]);

        $this->getEntityManager()->saveEntity($note);
    }

    protected function getAuditedFieldsData(Entity $entity)
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
                    $auditedFields[$field] = array();
                    $auditedFields[$field]['actualList'] = $this->getFieldManager()->getActualAttributeList($entityType, $field);
                    $auditedFields[$field]['notActualList'] = $this->getFieldManager()->getNotActualAttributeList($entityType, $field);
                    $auditedFields[$field]['fieldType'] = $d['type'];
                }
            }
            $this->auditedFieldsCache[$entityType] = $auditedFields;
        }

        return $this->auditedFieldsCache[$entityType];
    }

    public function handleAudited($entity)
    {
        $auditedFields = $this->getAuditedFieldsData($entity);

        $updatedFieldList = [];
        $was = array();
        $became = array();

        foreach ($auditedFields as $field => $item) {
            $updated = false;
            foreach ($item['actualList'] as $attribute) {
                if ($entity->hasFetched($attribute) && $entity->isAttributeChanged($attribute)) {
                    $updated = true;
                }
            }
            if ($updated) {
                $updatedFieldList[] = $field;
                foreach ($item['actualList'] as $attribute) {
                    $was[$attribute] = $entity->getFetched($attribute);
                    $became[$attribute] = $entity->get($attribute);
                }
                foreach ($item['notActualList'] as $attribute) {
                    $was[$attribute] = $entity->getFetched($attribute);
                    $became[$attribute] = $entity->get($attribute);
                }

                if ($item['fieldType'] === 'linkParent') {
                    $wasParentType = $was[$field . 'Type'];
                    $wasParentId = $was[$field . 'Id'];
                    if ($wasParentType && $wasParentId) {
                        if ($this->getEntityManager()->hasRepository($wasParentType)) {
                            $wasParent = $this->getEntityManager()->getEntity($wasParentType, $wasParentId);
                            if ($wasParent) {
                                $was[$field . 'Name'] = $wasParent->get('name');
                            }
                        }
                    }
                }
            }
        }

        if (!empty($updatedFieldList)) {
            $note = $this->getEntityManager()->getEntity('Note');

            $note->set('type', 'Update');
            $note->set('parentId', $entity->id);
            $note->set('parentType', $entity->getEntityType());

            $note->set('data', [
                'fields' => $updatedFieldList,
                'attributes' => [
                    'was' => $was,
                    'became' => $became
                ]
            ]);

            $this->getEntityManager()->saveEntity($note);
        }
    }

    public function getEntityFolowerIdList(Entity $entity)
    {
        $query = $this->getEntityManager()->getQuery();
        $pdo = $this->getEntityManager()->getPDO();
        $sql = $query->createSelectQuery('User', array(
            'select' => ['id'],
            'customJoin' => "
                JOIN subscription AS `subscription` ON
                    subscription.user_id = user.id AND
                    subscription.entity_id = ".$query->quote($entity->id)." AND
                    subscription.entity_type = ".$query->quote($entity->getEntityType())."
            ",
            'whereClause' => array(
                'isActive' => true
            )
        ));

        $sth = $pdo->prepare($sql);
        $sth->execute();

        $idList = [];
        while ($row = $sth->fetch(\PDO::FETCH_ASSOC)) {
            $idList[] = $row['id'];
        }

        return $idList;
    }

    public function findEntityFollowers(Entity $entity, $params)
    {
        $selectAttributeList = $this->getServiceFactory()->create('User')->getSelectAttributeList($params);

        $selectManager = $this->getSelectMangerFactory()->create('User');

        $selectParams = $selectManager->getSelectParams($params, true, true);

        if (empty($params['orderBy'])) {
            $selectParams['orderBy'] = [
                ['LIST:id:' . $this->getUser()->id, 'DESC'],
                ['name']
            ];
        }

        $selectManager->addJoin([
            'Subscription',
            'subscription',
            [
                'subscription.userId=:' => 'user.id',
                'subscription.entityId' => $entity->id,
                'subscription.entityType' => $entity->getEntityType()
            ]
        ], $selectParams);

        if ($selectAttributeList) {
            $selectParams['select'] = $selectAttributeList;
        }

        $query = $this->getEntityManager()->getQuery();
        $selectParams['t'] = true;
        $sql = $query->createSelectQuery('User', $selectParams);

        $collection = $this->getEntityManager()->getRepository('User')->find($selectParams);
        $total = $this->getEntityManager()->getRepository('User')->count($selectParams);

        return (object) [
            'total' => $total,
            'collection' => $collection
        ];
    }

    public function getEntityFollowers(Entity $entity, $offset = 0, $limit = false)
    {
        $query = $this->getEntityManager()->getQuery();
        $pdo = $this->getEntityManager()->getPDO();

        if (!$limit) {
            $limit = 200;
        }

        $sql = $query->createSelectQuery('User', [
            'select' => ['id', 'name'],
            'joins' => [
                [
                    'Subscription',
                    'subscription',
                    [
                        'subscription.userId=:' => 'user.id',
                        'subscription.entityId' => $entity->id,
                        'subscription.entityType' => $entity->getEntityType()
                    ]
                ]
            ],
            'offset' => $offset,
            'limit' => $limit,
            'whereClause' => [
                'isActive' => true
            ],
            'orderBy' => [
                ['LIST:id:' . $this->getUser()->id, 'DESC'],
                ['name']
            ]
        ]);

        $sth = $pdo->prepare($sql);
        $sth->execute();

        $data = [
            'idList' => [],
            'nameMap' => (object) []
        ];

        while ($row = $sth->fetch(\PDO::FETCH_ASSOC)) {
            $id = $row['id'];
            $data['idList'][] = $id;
            $data['nameMap']->$id = $row['name'];
        }

        return $data;
    }

    protected function getOnlyTeamEntityTypeList(\Espo\Entities\User $user)
    {
        if ($user->isPortal()) return [];

        $list = [];
        $scopes = $this->getMetadata()->get('scopes', []);
        foreach ($scopes as $scope => $item) {
            if ($scope === 'User') continue;
            if (empty($item['entity'])) continue;
            if (empty($item['object'])) continue;
            if (
                $this->getAclManager()->getLevel($user, $scope, 'read') === 'team'
            ) {
                $list[] = $scope;
            }
        }

        return $list;
    }

    protected function getOnlyOwnEntityTypeList(\Espo\Entities\User $user)
    {
        if ($user->isPortal()) return [];

        $list = [];
        $scopes = $this->getMetadata()->get('scopes', []);
        foreach ($scopes as $scope => $item) {
            if ($scope === 'User') continue;
            if (empty($item['entity'])) continue;
            if (empty($item['object'])) continue;
            if (
                $this->getAclManager()->getLevel($user, $scope, 'read') === 'own'
            ) {
                $list[] = $scope;
            }
        }
        return $list;
    }

    protected function getNotAllEntityTypeList(\Espo\Entities\User $user)
    {
        if (!$user->isPortal()) return [];

        $list = [];
        $scopes = $this->getMetadata()->get('scopes', []);
        foreach ($scopes as $scope => $item) {
            if ($scope === 'User') continue;
            if (empty($item['entity'])) continue;
            if (empty($item['object'])) continue;
            if (
                $this->getAclManager()->getLevel($user, $scope, 'read') !== 'all'
            ) {
                $list[] = $scope;
            }
        }
        return $list;
    }

    protected function getIgnoreScopeList(\Espo\Entities\User $user)
    {
        $ignoreScopeList = [];
        $scopes = $this->getMetadata()->get('scopes', []);
        foreach ($scopes as $scope => $item) {
            if (empty($item['entity'])) continue;
            if (empty($item['object'])) continue;
            if (
                !$this->getAclManager()->checkScope($user, $scope, 'read')
                ||
                !$this->getAclManager()->checkScope($user, $scope, 'stream')
            ) {
                $ignoreScopeList[] = $scope;
            }
        }
        return $ignoreScopeList;
    }

    public function controlFollowersJob($data)
    {
        if (empty($data)) {
            return;
        }
        if (empty($data->entityId) || empty($data->entityType)) {
            return;
        }

        if (!$this->getEntityManager()->hasRepository($data->entityType)) return;

        $entity = $this->getEntityManager()->getEntity($data->entityType, $data->entityId);
        if (!$entity) return;

        $idList = $this->getEntityFolowerIdList($entity);

        $userList = $this->getEntityManager()->getRepository('User')->where(array(
            'id' => $idList
        ))->find();

        foreach ($userList as $user) {
            if (!$user->get('isActive')) {
                $this->unfollowEntity($entity, $user->id);
                continue;
            }

            if (!$user->isPortal()) {
                if (!$this->getAclManager()->check($user, $entity, 'stream')) {
                    $this->unfollowEntity($entity, $user->id);
                    continue;
                }
            }
        }
    }
}
