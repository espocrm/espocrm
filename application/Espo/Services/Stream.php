<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;

use Espo\ORM\{
    Entity,
    EntityCollection,
    Collection,
};

use Espo\Entities\User;

use Espo\Core\{
    ORM\EntityManager,
    Utils\Config,
    Utils\Metadata,
    Acl,
    AclManager,
    ServiceFactory,
    Portal\AclManagerContainer as PortalAclManagerContainer,
    Utils\FieldUtil,
    Record\Collection as RecordCollection,
    Select\SelectBuilderFactory,
    Select\SearchParams,
};

class Stream
{
    protected $statusStyles = null;

    protected $statusFields = null;

    protected $successDefaultStyleList = ['Held', 'Closed Won', 'Closed', 'Completed', 'Complete', 'Sold'];

    protected $dangerDefaultStyleList = ['Not Held', 'Closed Lost', 'Dead'];

    protected $entityManager;
    protected $config;
    protected $user;
    protected $metadata;
    protected $acl;
    protected $aclManager;
    protected $serviceFactory;
    protected $portalAclManagerContainer;
    protected $fieldUtil;
    protected $selectBuilderFactory;

    public function __construct(
        EntityManager $entityManager,
        Config $config,
        User $user,
        Metadata $metadata,
        Acl $acl,
        AclManager $aclManager,
        ServiceFactory $serviceFactory,
        PortalAclManagerContainer $portalAclManagerContainer,
        FieldUtil $fieldUtil,
        SelectBuilderFactory $selectBuilderFactory
    ) {
        $this->entityManager = $entityManager;
        $this->config = $config;
        $this->user = $user;
        $this->metadata = $metadata;
        $this->acl = $acl;
        $this->aclManager = $aclManager;
        $this->serviceFactory = $serviceFactory;
        $this->portalAclManagerContainer = $portalAclManagerContainer;
        $this->fieldUtil = $fieldUtil;
        $this->selectBuilderFactory = $selectBuilderFactory;
    }

    protected $auditedFieldsCache = [];

    private $notificationService = null;

    protected function getNotificationService()
    {
        if (empty($this->notificationService)) {
            $this->notificationService = $this->serviceFactory->create('Notification');
        }
        return $this->notificationService;
    }

    protected function getStatusStyles()
    {
        if (empty($this->statusStyles)) {
            $this->statusStyles = $this->metadata->get('entityDefs.Note.statusStyles', []);
        }
        return $this->statusStyles;
    }

    protected function getStatusFields()
    {
        if (is_null($this->statusFields)) {
            $this->statusFields = array();
            $scopes = $this->metadata->get('scopes', []);
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

        $entity = $this->entityManager->getEntity($entityType, $entityId);
        if (!$entity) {
            return;
        }

        foreach ($userIdList as $i => $userId) {
            $user = $this->entityManager->getEntity('User', $userId);
            if (!$user) {
                unset($userIdList[$i]);
                continue;
            }
            if (!$this->aclManager->check($user, $entity, 'stream')) {
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

        $noteList = $this->entityManager->getRepository('Note')->where([
            'parentType' => $entityType,
            'parentId' => $entityId
        ])->order('number', 'ASC')->find();

        foreach ($noteList as $note) {
            $this->getNotificationService()->notifyAboutNote($userIdList, $note);
        }
    }

    public function checkIsFollowed(Entity $entity, ?string $userId = null) : bool
    {
        if (empty($userId)) {
            $userId = $this->user->id;
        }

        $isFollowed = (bool) $this->entityManager->getRepository('Subscription')
            ->select(['id'])
            ->where([
                'userId' => $userId,
                'entityType' => $entity->getEntityType(),
                'entityId' => $entity->id,
            ])
            ->findOne();

        return $isFollowed;
    }

    public function followEntityMass(Entity $entity, array $sourceUserIdList, bool $skipAclCheck = false)
    {
        if (!$this->metadata->get(['scopes', $entity->getEntityType(), 'stream'])) {
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

        if (!$skipAclCheck) {
            foreach ($userIdList as $i => $userId) {
                $user = $this->entityManager->getRepository('User')
                    ->select(['id', 'type', 'isActive'])
                    ->where([
                        'id' => $userId,
                        'isActive' => true,
                    ])->findOne();

                if (!$user) {
                    unset($userIdList[$i]);
                    continue;
                }

                if (!$this->aclManager->check($user, $entity, 'stream')) {
                    unset($userIdList[$i]);
                }
            }
            $userIdList = array_values($userIdList);
        }

        if (empty($userIdList)) {
            return;
        }

        $delete = $this->entityManager->getQueryBuilder()
            ->delete()
            ->from('Subscription')
            ->where([
                'userId' => $userIdList,
                'entityId' => $entity->id,
                'entityType' => $entity->getEntityType(),
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($delete);

        $collection = new EntityCollection();

        foreach ($userIdList as $userId) {
            $subscription = $this->entityManager->getEntity('Subscription');

            $subscription->set([
                'userId' => $userId,
                'entityId' => $entity->id,
                'entityType' => $entity->getEntityType(),
            ]);

            $collection[] = $subscription;
        }

        $this->entityManager->getMapper()->massInsert($collection);
    }

    public function followEntity(Entity $entity, string $userId, bool $skipAclCheck = false)
    {
        if ($userId == 'system') {
            return false;
        }
        if (!$this->metadata->get('scopes.' . $entity->getEntityType() . '.stream')) {
            return false;
        }

        if (!$skipAclCheck) {
            $user = $this->entityManager
                ->getRepository('User')
                ->select(['id', 'type', 'isActive'])
                ->where([
                    'id' => $userId,
                    'isActive' => true,
                ])
                ->findOne();

            if (!$user) {
                return false;
            }

            $aclManager = $this->getUserAclManager($user);

            if (!$aclManager) {
                return false;
            }

            if (!$aclManager->check($user, $entity, 'stream')) {
                return false;
            }
        }

        if ($this->checkIsFollowed($entity, $userId)) {
            return true;
        }

        $this->entityManager->createEntity('Subscription', [
            'entityId' => $entity->id,
            'entityType' => $entity->getEntityType(),
            'userId' => $userId,
        ]);

        return true;
    }

    public function unfollowEntity(Entity $entity, string $userId)
    {
        if (!$this->metadata->get('scopes.' . $entity->getEntityType() . '.stream')) {
            return false;
        }

        $delete = $this->entityManager->getQueryBuilder()
            ->delete()
            ->from('Subscription')
            ->where([
                'userId' => $userId,
                'entityId' => $entity->id,
                'entityType' => $entity->getEntityType(),
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($delete);

        return true;
    }

    public function unfollowAllUsersFromEntity(Entity $entity)
    {
        if (empty($entity->id)) {
            return;
        }

        $delete = $this->entityManager->getQueryBuilder()
            ->delete()
            ->from('Subscription')
            ->where([
                'entityId' => $entity->id,
                'entityType' => $entity->getEntityType(),
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($delete);
    }

    public function findUserStream(string $userId, array $params = [])
    {
        $offset = intval($params['offset']);
        $maxSize = intval($params['maxSize']);

        $sqLimit = $offset + $maxSize + 1;

        if ($userId === $this->user->id) {
            $user = $this->user;
        }
        else {
            $user = $this->entityManager->getEntity('User', $userId);

            if (!$user) {
                throw new NotFound();
            }

            if (!$this->acl->checkUserPermission($user, 'user')) {
                throw new Forbidden();
            }
        }

        $skipOwn = $params['skipOwn'] ?? false;

        $teamIdList = $user->getTeamIdList();

        $select = [
            'id',
            'number',
            'type',
            'post',
            'data',
            'parentType',
            'parentId',
            'relatedType',
            'relatedId',
            'targetType',
            'createdAt',
            'createdById',
            'createdByName',
            'isGlobal',
            'isInternal',
            'createdByGender',
        ];

        $onlyTeamEntityTypeList = $this->getOnlyTeamEntityTypeList($user);
        $onlyOwnEntityTypeList = $this->getOnlyOwnEntityTypeList($user);

        $additionalQuery = null;

        if (!empty($params['where'])) {
            $searchParams = SearchParams::fromRaw([
                'where' => $params['where'],
            ]);

            $additionalQuery = $this->selectBuilderFactory
                ->create()
                ->from('Note')
                ->withComplexExpressionsForbidden()
                ->withWherePermissionCheck()
                ->withSearchParams($searchParams)
                ->buildQueryBuilder()
                ->order([])
                ->build();
        }

        $queryList = [];

        $baseBuilder = $this->entityManager->getQueryBuilder()->select();

        if ($additionalQuery) {
            $baseBuilder->clone($additionalQuery);
        }
        else {
            $baseBuilder->from('Note');
        }

        $baseBuilder
            ->select($select)
            ->order('number', 'DESC')
            ->limit(0, $sqLimit)
            ->useIndex('number')
            ->where(
                $this->getUserStreamWhereClause($params, $user)
            );

        $subscriptionBuilder = clone $baseBuilder;

        $subscriptionBuilder
            ->leftJoin('createdBy')
            ->join(
                'Subscription',
                'subscription',
                [
                    'entityType:' => 'parentType',
                    'entityId:' => 'parentId',
                    'subscription.userId' => $user->id,
                ]
            )
            ->useIndex('number');

        if ($user->isPortal()) {
            $subscriptionBuilder->where([
                'isInternal' => false,
            ]);

            $notAllEntityTypeList = $this->getNotAllEntityTypeList($user);

            $orGroup = [
                [
                    'relatedId' => null,
                ],
                [
                    'relatedId!=' => null,
                    'relatedType!=' => $notAllEntityTypeList,
                ],
            ];

            $aclManager = $this->getUserAclManager($user);

            if ($aclManager && $aclManager->check($user, 'Email', 'read')) {
                $orGroup[] = [
                    'relatedId!=' => null,
                    'relatedType' => 'Email',
                    'noteUser.userId' => $user->id,
                ];

                $subscriptionBuilder->leftJoin(
                    'noteUser',
                    'noteUser', [
                        'noteUser.noteId=:' => 'id',
                        'noteUser.deleted' => false,
                        'note.relatedType' => 'Email',
                    ]
                );
            }

            $subscriptionBuilder->where([
                'OR' => $orGroup,
            ]);

            $queryList[] = $subscriptionBuilder->build();
        }

        if (!$user->isPortal()) {
            $subscriptionRestBuilder = clone $subscriptionBuilder;

            $subscriptionRestBuilder->where([
                'OR' => [
                    [
                        'relatedId!=' => null,
                        'relatedType!=' => array_merge($onlyTeamEntityTypeList, $onlyOwnEntityTypeList),
                    ],
                    [
                        'relatedId=' => null,
                    ],
                ],
            ]);

            $queryList[] = $subscriptionRestBuilder->build();

            if (count($onlyTeamEntityTypeList)) {
                $subscriptionTeamBuilder = clone $subscriptionBuilder;

                $subscriptionTeamBuilder
                    ->distinct()
                    ->leftJoin(
                        'noteTeam',
                        'noteTeam',
                        [
                            'noteTeam.noteId=:' => 'id',
                            'noteTeam.deleted' => false,
                        ]
                    )
                    ->leftJoin(
                        'noteUser',
                        'noteUser',
                        [
                            'noteUser.noteId=:' => 'id',
                            'noteUser.deleted' => false,
                        ]
                    )
                    ->where([
                        [
                            'relatedId!=' => null,
                            'relatedType=' => $onlyTeamEntityTypeList,
                        ],
                        [
                            'OR' => [
                                'noteTeam.teamId' => $teamIdList,
                                'noteUser.userId' => $user->id,
                            ],
                        ],
                    ]);

                $queryList[] = $subscriptionTeamBuilder->build();
            }

            if (count($onlyOwnEntityTypeList)) {
                $subscriptionOwnBuilder = clone $subscriptionBuilder;

                $subscriptionOwnBuilder
                    ->distinct()
                    ->leftJoin(
                        'noteUser',
                        'noteUser',
                        [
                            'noteUser.noteId=:' => 'id',
                            'noteUser.deleted' => false,
                        ]
                    )
                    ->where([
                        [
                            'relatedId!=' => null,
                            'relatedType=' => $onlyOwnEntityTypeList,
                        ],
                        'noteUser.userId' => $user->id,
                    ]);

                $queryList[] = $subscriptionOwnBuilder->build();
            }
        }

        $subscriptionSuperBuilder = clone $baseBuilder;

        $subscriptionSuperBuilder
            ->join(
                'Subscription',
                'subscription',
                [
                    'entityType:' => 'superParentType',
                    'entityId:' => 'superParentId',
                    'subscription.userId' => $user->id,
                ]
            )
            ->leftJoin(
                'Subscription',
                'subscriptionExclude',
                [
                    'entityType:' => 'parentType',
                    'entityId:' => 'parentId',
                    'subscription.userId' => $user->id,
                ]
            )
            ->where([
                'OR' => [
                    'parentId!=:' => 'superParentId',
                    'parentType!=:' => 'superParentType',
                ],
                'subscriptionExclude.id' => null,
            ])
            ->useIndex('number');

        if (!$user->isPortal()) {
            $subscriptionSuperRestBuilder = clone $subscriptionSuperBuilder;

            $subscriptionSuperRestBuilder->where([
                'OR' => [
                    [
                        'relatedId!=' => null,
                        'relatedType!=' => array_merge($onlyTeamEntityTypeList, $onlyOwnEntityTypeList),
                    ],
                    [
                        'relatedId=' => null,
                        'parentType!=' => array_merge($onlyTeamEntityTypeList, $onlyOwnEntityTypeList),
                    ],
                ],
            ]);

            $queryList[] = $subscriptionSuperRestBuilder->build();

            if (count($onlyTeamEntityTypeList)) {
                $subscriptionSuperTeamBuilder = clone $subscriptionSuperBuilder;

                $subscriptionSuperTeamBuilder
                    ->distinct()
                    ->leftJoin(
                        'noteTeam',
                        'noteTeam',
                        [
                            'noteTeam.noteId=:' => 'id',
                            'noteTeam.deleted' => false,
                        ]
                    )
                    ->leftJoin(
                        'noteUser',
                        'noteUser',
                        [
                            'noteUser.noteId=:' => 'id',
                            'noteUser.deleted' => false,
                        ]
                    )
                    ->where([
                        'OR' => [
                            [
                                'relatedId!=' => null,
                                'relatedType=' => $onlyTeamEntityTypeList,
                            ],
                            [
                                'relatedId=' => null,
                                'parentType=' => $onlyTeamEntityTypeList,
                            ],
                        ],
                        [
                            'OR' => [
                                'noteTeam.teamId' => $teamIdList,
                                'noteUser.userId' => $user->id,
                            ],
                        ]
                    ]);

                $queryList[] = $subscriptionSuperTeamBuilder->build();
            }

            if (count($onlyOwnEntityTypeList)) {
                $subscriptionSuperOwnBuilder = clone $subscriptionSuperBuilder;

                $subscriptionSuperOwnBuilder
                    ->distinct()
                    ->leftJoin(
                        'noteUser',
                        'noteUser',
                        [
                            'noteUser.noteId=:' => 'id',
                            'noteUser.deleted' => false,
                        ]
                    )
                    ->where([
                        [
                            'relatedId!=' => null,
                            'relatedType=' => $onlyOwnEntityTypeList,
                        ],
                        'noteUser.userId' => $user->id,
                    ]);

                $queryList[] = $subscriptionSuperOwnBuilder->build();
            }
        }

        $queryList[] = (clone $baseBuilder)
            ->leftJoin('users')
            ->leftJoin('createdBy')
            ->where([
                'createdById!=' => $user->id,
                'usersMiddle.userId' => $user->id,
                'parentId' => null,
                'type' => 'Post',
                'isGlobal' => false,
            ])
            ->build();

        if ($user->isPortal()) {
            $portalIdList = $user->getLinkMultipleIdList('portals');

            if (!empty($portalIdList)) {

                $queryList[] = (clone $baseBuilder)
                    ->leftJoin('portals')
                    ->leftJoin('createdBy')
                    ->where([
                        'parentId' => null,
                        'portalsMiddle.portalId' => $portalIdList,
                        'type' => 'Post',
                        'isGlobal' => false,
                    ])
                    ->build();
            }
        }

        if (!empty($teamIdList)) {
            $queryList[] = (clone $baseBuilder)
                ->leftJoin('teams')
                ->leftJoin('createdBy')
                ->where([
                    'parentId' => null,
                    'teamsMiddle.teamId' => $teamIdList,
                    'type' => 'Post',
                    'isGlobal' => false,
                ])
                ->build();
        }

        if ($skipOwn) {
            foreach ($queryList as $i => $query) {
                $queryList[$i] = $this->entityManager
                    ->getQueryBuilder()
                    ->select()
                    ->clone($query)
                    ->where([
                        'createdById!=' => $this->user->id,
                    ])
                    ->build();
            }
        }

        $queryList[] = (clone $baseBuilder)
            ->leftJoin('createdBy')
            ->where([
                'createdById' => $user->id,
                'parentId' => null,
                'type' => 'Post',
                'isGlobal' => false,
            ])
            ->build();

         if (
            (!$user->isPortal() || $user->isAdmin()) &&
            !$user->isApi()
        ) {

            $queryList[] = (clone $baseBuilder)
                ->leftJoin('createdBy')
                ->where([
                    'parentId' => null,
                    'type' => 'Post',
                    'isGlobal' => true,
                ])
                ->build();
        }

        $builder = $this->entityManager
            ->getQueryBuilder()
            ->union()
            ->all()
            ->order('number', 'DESC')
            ->limit($offset, $maxSize + 1);

        foreach ($queryList as $query) {
            $builder->query($query);
        }

        $unionQuery = $builder->build();

        $sql = $this->entityManager
            ->getQueryComposer()
            ->compose($unionQuery);

        $sthCollection = $this->entityManager
            ->getRepository('Note')
            ->findBySql($sql);

        $collection = $this->entityManager
            ->getCollectionFactory()
            ->createFromSthCollection($sthCollection);

        foreach ($collection as $e) {
            $this->loadNoteAdditionalFields($e);
        }

        $total = -2;

        if (count($collection) > $maxSize) {
            $total = -1;

            unset($collection[count($collection) - 1]);
        }

        return (object) [
            'total' => $total,
            'collection' => $collection,
        ];
    }

    protected function getUserStreamWhereClause(array $params, User $user) : array
    {
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
                    'relatedType!=' => $ignoreScopeList,
                ]
            ];

            $whereClause[] = [
                'OR' => [
                    'parentType' => null,
                    'parentType!=' => $ignoreScopeList,
                ]
            ];

            if (in_array('Email', $ignoreScopeList)) {
                $whereClause[] = [
                    'type!=' => ['EmailReceived', 'EmailSent'],
                ];
            }
        }

        return $whereClause;
    }

    protected function loadNoteAdditionalFields(Entity $e)
    {
        if ($e->get('type') == 'Post' || $e->get('type') == 'EmailReceived') {
            $e->loadAttachments();
        }

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

    public function find(string $scope, ?string $id, array $params = [])
    {
        if ($scope === 'User') {
            if (empty($id)) {
                $id = $this->user->id;
            }

            return $this->findUserStream($id, $params);
        }

        $entity = $this->entityManager->getEntity($scope, $id);

        $onlyTeamEntityTypeList = $this->getOnlyTeamEntityTypeList($this->user);
        $onlyOwnEntityTypeList = $this->getOnlyOwnEntityTypeList($this->user);

        if (empty($entity)) {
            throw new NotFound();
        }

        if (!$this->acl->checkEntity($entity, 'stream')) {
            throw new Forbidden();
        }

        $additionalQuery = null;

        if (!empty($params['where'])) {
            $searchParams = SearchParams::fromRaw([
                'where' => $params['where'],
            ]);

            $additionalQuery = $this->selectBuilderFactory
                ->create()
                ->from('Note')
                ->withComplexExpressionsForbidden()
                ->withWherePermissionCheck()
                ->withSearchParams($searchParams)
                ->buildQueryBuilder()
                ->order([])
                ->build();
        }

        $builder = $this->entityManager->getQueryBuilder()->select();

        if ($additionalQuery) {
            $builder->clone($additionalQuery);
        }
        else {
            $builder->from('Note');
        }

        $where = [
            'OR' => [
                [
                    'parentType' => $scope,
                    'parentId' => $id,
                ],
                [
                    'superParentType' => $scope,
                    'superParentId' => $id,
                ],
            ]
        ];

        if ($this->user->isPortal()) {
            $where = [
                'parentType' => $scope,
                'parentId' => $id,
                'isInternal' => false,
            ];

            $notAllEntityTypeList = $this->getNotAllEntityTypeList($this->user);

            $orGroup = [
                [
                    'relatedId' => null,
                ],
                [
                    'relatedId!=' => null,
                    'relatedType!=' => $notAllEntityTypeList,
                ],
            ];

            if ($this->acl->check('Email', 'read')) {
                $builder->leftJoin(
                    'noteUser',
                    'noteUser',
                    [
                        'noteUser.noteId=:' => 'id',
                        'noteUser.deleted' => false,
                        'note.relatedType' => 'Email',
                    ]
                );

                $orGroup[] = [
                    'relatedId!=' => null,
                    'relatedType' => 'Email',
                    'noteUser.userId' => $this->user->id,
                ];
            }

            $where[] = [
                'OR' => $orGroup,
            ];
        }

        if (!$this->user->isPortal()) {
            if (count($onlyTeamEntityTypeList) || count($onlyOwnEntityTypeList)) {
                $builder
                    ->distinct()
                    ->leftJoin('teams')
                    ->leftJoin('users');

                $where[] = [
                    'OR' => [
                        'OR' => [
                            [
                                'relatedId!=' => null,
                                'relatedType!=' => array_merge($onlyTeamEntityTypeList, $onlyOwnEntityTypeList),
                            ],
                            [
                                'relatedId=' => null,
                                'superParentId' => $id,
                                'superParentType' => $scope,
                                'parentId!=' => null,
                                'parentType!=' => array_merge($onlyTeamEntityTypeList, $onlyOwnEntityTypeList),
                            ],
                            [
                                'relatedId=' => null,
                                'parentType=' => $scope,
                                'parentId=' => $id,
                            ]
                        ],
                        [
                            'OR' => [
                                [
                                    'relatedId!=' => null,
                                    'relatedType=' => $onlyTeamEntityTypeList,
                                ],
                                [
                                    'relatedId=' => null,
                                    'parentType=' => $onlyTeamEntityTypeList,
                                ]
                            ],
                            [
                                'OR' => [
                                    'teamsMiddle.teamId' => $this->user->getTeamIdList(),
                                    'usersMiddle.userId' => $this->user->id,
                                ]
                            ]
                        ],
                        [
                            'OR' => [
                                [
                                    'relatedId!=' => null,
                                    'relatedType=' => $onlyOwnEntityTypeList,
                                ],
                                [
                                    'relatedId=' => null,
                                    'parentType=' => $onlyOwnEntityTypeList,
                                ]
                            ],
                            'usersMiddle.userId' => $this->user->id,
                        ]
                    ]
                ];
            }
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

        $ignoreScopeList = $this->getIgnoreScopeList($this->user);

        if (!empty($ignoreScopeList)) {
            $where[] = [
                'OR' => [
                    'relatedType' => null,
                    'relatedType!=' => $ignoreScopeList,
                ]
            ];

            $where[] = [
                'OR' => [
                    'parentType' => null,
                    'parentType!=' => $ignoreScopeList,
                ]
            ];

            if (in_array('Email', $ignoreScopeList)) {
                $where[] = [
                    'type!=' => ['EmailReceived', 'EmailSent']
                ];
            }
        }

        $builder->where($where);

        $countBuilder = clone $builder;

        $builder
            ->limit($params['offset'], $params['maxSize'])
            ->order('number', 'DESC');

        if (!empty($params['after'])) {
            $where['createdAt>'] = $params['after'];

            $builder->where([
                'createdAt>' => $params['after'],
            ]);
        }

        $collection = $this->entityManager
            ->getRepository('Note')
            ->clone($builder->build())
            ->find();

        foreach ($collection as $e) {
            if ($e->get('type') == 'Post' || $e->get('type') == 'EmailReceived') {
                $e->loadAttachments();
            }

            if (
                $e->get('parentId') && $e->get('parentType') &&
                ($e->get('parentId') !== $id || $e->get('parentType') !== $scope)
            ) {
                $e->loadParentNameField('parent');
            }

            if ($e->get('relatedId') && $e->get('relatedType')) {
                $e->loadParentNameField('related');
            }
        }

        $count = $this->entityManager
            ->getRepository('Note')
            ->clone($countBuilder->build())
            ->count();

        return (object) [
            'total' => $count,
            'collection' => $collection,
        ];
    }

    protected function loadAssignedUserName(Entity $entity)
    {
        $user = $this->entityManager->getRepository('User')->select(['name'])->where([
            'id' =>  $entity->get('assignedUserId'),
        ])->findOne();
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

        $ownerUserIdAttribute = $this->aclManager->getImplementation($entity->getEntityType())->getOwnerUserIdAttribute($entity);
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

        if ($this->entityManager->getRepository('Note')->where([
            'type' => 'EmailReceived',
            'parentId' => $entity->id,
            'parentType' => $entityType,
            'relatedId' => $email->id,
            'relatedType' => 'Email'
        ])->findOne()) {
            return;
        }

        $note = $this->entityManager->getEntity('Note');

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

        $withContent = in_array($entityType, $this->config->get('streamEmailWithContentEntityTypeList', []));

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
            $person = $this->entityManager->getRepository('EmailAddress')->getEntityByAddress($from);
            if ($person) {
                $data['personEntityType'] = $person->getEntityType();
                $data['personEntityName'] = $person->get('name');
                $data['personEntityId'] = $person->id;
            }
        }

        $note->set('data', (object) $data);


        $this->entityManager->saveEntity($note);
    }

    public function noteEmailSent(Entity $entity, Entity $email)
    {
        $entityType = $entity->getEntityType();

        $note = $this->entityManager->getEntity('Note');

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

        $withContent = in_array($entityType, $this->config->get('streamEmailWithContentEntityTypeList', []));

        if ($withContent) {
            $note->set('post', $email->getBodyPlain());
        }

        $data = [];
        $data['emailId'] = $email->id;
        $data['emailName'] = $email->get('name');

        if ($withContent) {
            $data['attachmentsIds'] = $email->get('attachmentsIds');
        }

        $user = $this->user;

        if ($user->id != 'system') {
            $person = $user;
        } else {
            $from = $email->get('from');
            if ($from) {
                $person = $this->entityManager->getRepository('EmailAddress')->getEntityByAddress($from);
            }
        }

        if ($person) {
            $data['personEntityType'] = $person->getEntityType();
            $data['personEntityName'] = $person->get('name');
            $data['personEntityId'] = $person->id;
        }

        $note->set('data', (object) $data);

        $this->entityManager->saveEntity($note);
    }

    public function noteCreate(Entity $entity, array $options = [])
    {
        $entityType = $entity->getEntityType();

        $note = $this->entityManager->getEntity('Note');

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

        $o = [];
        if (!empty($options['createdById'])) {
            $o['createdById'] = $options['createdById'];
        }

        $this->entityManager->saveEntity($note, $o);
    }

    protected function getStatusStyle($entityType, $field, $value)
    {
        $style = $this->metadata->get(['entityDefs', $entityType, 'fields', $field, 'style', $value]);
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

    public function noteCreateRelated(Entity $entity, $parentType, $parentId, array $options = [])
    {
        $note = $this->entityManager->getEntity('Note');

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

        $o = [];
        if (!empty($options['createdById'])) {
            $o['createdById'] = $options['createdById'];
        }

        $this->entityManager->saveEntity($note, $o);
    }

    public function noteRelate(Entity $entity, $parentType, $parentId, array $options = [])
    {
        $entityType = $entity->getEntityType();

        $existing = $this->entityManager->getRepository('Note')->select(['id'])->where([
            'type' => 'Relate',
            'parentId' => $parentId,
            'parentType' => $parentType,
            'relatedId' => $entity->id,
            'relatedType' => $entityType,
        ])->findOne();
        if ($existing) return false;

        $note = $this->entityManager->getEntity('Note');

        $note->set([
            'type' => 'Relate',
            'parentId' => $parentId,
            'parentType' => $parentType,
            'relatedType' => $entityType,
            'relatedId' => $entity->id,
        ]);

        $this->processNoteTeamsUsers($note, $entity);

        $o = [];
        if (!empty($options['createdById'])) {
            $o['createdById'] = $options['createdById'];
        }

        $this->entityManager->saveEntity($note, $o);
    }

    public function noteAssign(Entity $entity, array $options = [])
    {
        $note = $this->entityManager->getEntity('Note');

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

        $o = [];
        if (!empty($options['createdById'])) {
            $o['createdById'] = $options['createdById'];
        }
        if (!empty($options['modifiedById'])) {
            $o['createdById'] = $options['modifiedById'];
        }

        $this->entityManager->saveEntity($note, $o);
    }

    public function noteStatus(Entity $entity, $field, array $options = [])
    {
        $note = $this->entityManager->getEntity('Note');

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

        $o = [];

        if (!empty($options['createdById'])) {
            $o['createdById'] = $options['createdById'];
        }

        if (!empty($options['modifiedById'])) {
            $o['createdById'] = $options['modifiedById'];
        }

        $this->entityManager->saveEntity($note, $o);
    }

    protected function getAuditedFieldsData(Entity $entity)
    {
        $entityType = $entity->getEntityType();

        $statusFields = $this->getStatusFields();

        if (!array_key_exists($entityType, $this->auditedFieldsCache)) {
            $fields = $this->metadata->get('entityDefs.' . $entityType . '.fields');
            $auditedFields = array();
            foreach ($fields as $field => $d) {
                if (!empty($d['audited'])) {
                    if (!empty($statusFields[$entityType]) && $statusFields[$entityType] === $field) {
                        continue;
                    }
                    $auditedFields[$field] = array();
                    $auditedFields[$field]['actualList'] = $this->fieldUtil->getActualAttributeList($entityType, $field);
                    $auditedFields[$field]['notActualList'] = $this->fieldUtil->getNotActualAttributeList($entityType, $field);
                    $auditedFields[$field]['fieldType'] = $d['type'];
                }
            }
            $this->auditedFieldsCache[$entityType] = $auditedFields;
        }

        return $this->auditedFieldsCache[$entityType];
    }

    public function handleAudited($entity, array $options = [])
    {
        $auditedFields = $this->getAuditedFieldsData($entity);

        $updatedFieldList = [];
        $was = [];
        $became = [];

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
                        if ($this->entityManager->hasRepository($wasParentType)) {
                            $wasParent = $this->entityManager->getEntity($wasParentType, $wasParentId);
                            if ($wasParent) {
                                $was[$field . 'Name'] = $wasParent->get('name');
                            }
                        }
                    }
                }
            }
        }

        if (!empty($updatedFieldList)) {
            $note = $this->entityManager->getEntity('Note');

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

            $o = [];
            if (!empty($options['modifiedById'])) {
                $o['createdById'] = $options['modifiedById'];
            }

            $this->entityManager->saveEntity($note, $o);
        }
    }

    public function getEntityFolowerIdList(Entity $entity) : array
    {
        $userList = $this->entityManager
            ->getRepository('User')
            ->select(['id'])
            ->join('Subscription', 'subscription', [
                'subscription.userId=:' => 'user.id',
                'subscription.entityId' => $entity->id,
                'subscription.entityType' => $entity->getEntityType(),
            ])
            ->where(['isActive' => true])
            ->find();

        $idList = [];

        foreach ($userList as $user) {
            $idList[] = $user->id;
        }

        return $idList;
    }

    public function findEntityFollowers(Entity $entity, $params) : RecordCollection
    {
        $builder = $this->selectBuilderFactory
            ->create()
            ->from('User')
            ->withSearchParams(SearchParams::fromRaw($params))
            ->withStrictAccessControl()
            ->buildQueryBuilder();

        if (empty($params['orderBy'])) {
            $builder->order('LIST:id:' . $this->user->id, 'DESC');
            $builder->order('name');
        }

        $builder->join(
            'Subscription',
            'subscription',
            [
                'subscription.userId=:' => 'user.id',
                'subscription.entityId' => $entity->id,
                'subscription.entityType' => $entity->getEntityType(),
            ]
        );

        $query = $builder->build();

        $collection = $this->entityManager
            ->getRepository('User')
            ->clone($query)
            ->find();

        $total = $this->entityManager
            ->getRepository('User')
            ->clone($query)
            ->count();

        return new RecordCollection($collection, $total);
    }

    public function getEntityFollowers(Entity $entity, $offset = 0, $limit = false)
    {
        if (!$limit) {
            $limit = 200;
        }

        $userList = $this->entityManager->getRepository('User')
            ->select(['id', 'name'])
            ->join(
                'Subscription',
                'subscription',
                [
                    'subscription.userId=:' => 'user.id',
                    'subscription.entityId' => $entity->id,
                    'subscription.entityType' => $entity->getEntityType()
                ]
            )
            ->limit($offset, $limit)
            ->where([
                'isActive' => true,
            ])
            ->order([
                ['LIST:id:' . $this->user->id, 'DESC'],
                ['name'],
            ])
            ->find();

        $data = [
            'idList' => [],
            'nameMap' => (object) []
        ];

        foreach ($userList as $user) {
            $id = $user->id;

            $data['idList'][] = $id;
            $data['nameMap']->$id = $user->get('name');
        }

        return $data;
    }

    protected function getOnlyTeamEntityTypeList(User $user)
    {
        if ($user->isPortal()) return [];

        $list = [];
        $scopes = $this->metadata->get('scopes', []);
        foreach ($scopes as $scope => $item) {
            if ($scope === 'User') continue;
            if (empty($item['entity'])) continue;
            if (empty($item['object'])) continue;
            if (
                $this->aclManager->getLevel($user, $scope, 'read') === 'team'
            ) {
                $list[] = $scope;
            }
        }

        return $list;
    }

    protected function getOnlyOwnEntityTypeList(User $user)
    {
        if ($user->isPortal()) return [];

        $list = [];
        $scopes = $this->metadata->get('scopes', []);
        foreach ($scopes as $scope => $item) {
            if ($scope === 'User') continue;
            if (empty($item['entity'])) continue;
            if (empty($item['object'])) continue;
            if (
                $this->aclManager->getLevel($user, $scope, 'read') === 'own'
            ) {
                $list[] = $scope;
            }
        }
        return $list;
    }

    protected function getUserAclManager(User $user)
    {
        $aclManager = $this->aclManager;

        if ($user->isPortal() && !$this->user->isPortal()) {
            $portal = $this->entityManager
                ->getRepository('User')
                ->getRelation($user, 'portals')
                ->findOne();

            if ($portal) {
                $aclManager = $this->portalAclManagerContainer->get($portal);
            } else {
                $aclManager = null;
            }
        }

        return $aclManager;
    }

    protected function getNotAllEntityTypeList(User $user)
    {
        if (!$user->isPortal()) return [];

        $aclManager = $this->getUserAclManager($user);

        $list = [];
        $scopes = $this->metadata->get('scopes', []);
        foreach ($scopes as $scope => $item) {
            if ($scope === 'User') continue;
            if (empty($item['entity'])) continue;
            if (empty($item['object'])) continue;
            if (
                !$aclManager || $aclManager->getLevel($user, $scope, 'read') !== 'all'
            ) {
                $list[] = $scope;
            }
        }
        return $list;
    }

    protected function getIgnoreScopeList(User $user)
    {
        $ignoreScopeList = [];
        $scopes = $this->metadata->get('scopes', []);

        $aclManager = $this->getUserAclManager($user);

        foreach ($scopes as $scope => $item) {
            if (empty($item['entity'])) continue;
            if (empty($item['object'])) continue;
            if (
                !$aclManager
                ||
                !$aclManager->checkScope($user, $scope, 'read')
                ||
                !$aclManager->checkScope($user, $scope, 'stream')
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

        if (!$this->entityManager->hasRepository($data->entityType)) return;

        $entity = $this->entityManager->getEntity($data->entityType, $data->entityId);
        if (!$entity) return;

        $idList = $this->getEntityFolowerIdList($entity);

        $userList = $this->entityManager->getRepository('User')->where(array(
            'id' => $idList
        ))->find();

        foreach ($userList as $user) {
            if (!$user->get('isActive')) {
                $this->unfollowEntity($entity, $user->id);
                continue;
            }

            if (!$user->isPortal()) {
                if (!$this->aclManager->check($user, $entity, 'stream')) {
                    $this->unfollowEntity($entity, $user->id);
                    continue;
                }
            }
        }
    }

    public function getSubscriberList(string $parentType, string $parentId, bool $isInternal = false) : Collection
    {
        if (!$this->metadata->get(['scopes', $parentType, 'stream'])) {
            return $this->entityManager->getCollectionFactory()->create('User', []);
        }

        $builder = $this->entityManager->getQueryBuilder()
            ->select()
            ->from('Subscription')
            ->select('userId')
            ->where([
                'entityId' => $parentId,
                'entityType' => $parentType,
            ]);

        if ($isInternal) {
            $builder
                ->join('User', 'user', ['user.id:' => 'userId'])
                ->where([
                    'user.type!=' => 'portal',
                ]);
        }

        $subQuery = $builder->build();

        $userList = $this->entityManager->getRepository('User')
            ->where([
                'isActive' => true,
                'id=s' => $subQuery->getRaw(),
            ])
            ->select(['id', 'type'])
            ->find();

        return $userList;
    }
}
