<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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
use Espo\Core\Exceptions\Error;
use Espo\Core\Record\ServiceContainer as RecordServiceContainer;
use Espo\Core\ORM\Entity as CoreEntity;

use Espo\Tools\Stream\NoteAccessControl;

use Espo\Repositories\EmailAddress as EmailAddressRepository;

use Espo\ORM\{
    Entity,
    EntityCollection,
    Collection,
};

use Espo\{
    Entities\User,
    Entities\Note as NoteEntity,
    Entities\Email,
    Entities\EmailAddress,
};

use Espo\Core\{
    ORM\EntityManager,
    Utils\Config,
    Utils\Metadata,
    Acl,
    AclManager,
    Acl\Table,
    Acl\Exceptions\NotImplemented as AclNotImplemented,
    Utils\FieldUtil,
    Record\Collection as RecordCollection,
    Select\SelectBuilderFactory,
    Select\SearchParams,
    Utils\Acl\UserAclManagerProvider,
};

use stdClass;
use DateTime;
use LogicException;

class Stream
{
    /**
     * @var ?array<string,string>
     */
    private $statusStyles = null;

    /**
     * @var ?array<string,string>
     */
    private $statusFields = null;

    /**
     * @var string[]
     */
    private $successDefaultStyleList = [
        'Held',
        'Closed Won',
        'Closed',
        'Completed',
        'Complete',
        'Sold',
    ];

    /**
     * @var string[]
     */
    private $dangerDefaultStyleList = [
        'Not Held',
        'Closed Lost',
        'Dead',
    ];

    /**
     *
     * @var array<
     *   string,
     *   array<
     *     string,
     *     array{
     *       actualList: string[],
     *       notActualList: string[],
     *       fieldType: string,
     *     }
     *   >
     * >
     */
    private $auditedFieldsCache = [];

    private $entityManager;

    private $config;

    private $user;

    private $metadata;

    private $acl;

    private $aclManager;

    private $fieldUtil;

    private $selectBuilderFactory;

    private $userAclManagerProvider;

    private $noteAccessControl;

    private $recordServiceContainer;

    /**
     * When a record is re-assigned, ACL will be recalculated for related notes
     * created within the period.
     */
    private const NOTE_ACL_PERIOD = '3 days';

    private const NOTE_ACL_LIMIT = 50;

    private const SYSTEM_USER_ID = 'system';

    /**
     * Not used currently.
     */
    private const NOTE_NOTIFICATION_PERIOD = '1 hour';

    public function __construct(
        EntityManager $entityManager,
        Config $config,
        User $user,
        Metadata $metadata,
        Acl $acl,
        AclManager $aclManager,
        FieldUtil $fieldUtil,
        SelectBuilderFactory $selectBuilderFactory,
        UserAclManagerProvider $userAclManagerProvider,
        NoteAccessControl $noteAccessControl,
        RecordServiceContainer $recordServiceContainer
    ) {
        $this->entityManager = $entityManager;
        $this->config = $config;
        $this->user = $user;
        $this->metadata = $metadata;
        $this->acl = $acl;
        $this->aclManager = $aclManager;
        $this->fieldUtil = $fieldUtil;
        $this->selectBuilderFactory = $selectBuilderFactory;
        $this->userAclManagerProvider = $userAclManagerProvider;
        $this->noteAccessControl = $noteAccessControl;
        $this->recordServiceContainer = $recordServiceContainer;
    }

    /**
     * @return array<string,string>
     */
    private function getStatusStyles(): array
    {
        if (empty($this->statusStyles)) {
            $this->statusStyles = $this->metadata->get('entityDefs.Note.statusStyles', []);
        }

        return $this->statusStyles;
    }

    /**
     * @return array<string,string>
     */
    private function getStatusFields(): array
    {
        if (is_null($this->statusFields)) {
            $this->statusFields = [];

            /** @var array<string,array<string,mixed>> $scopes */
            $scopes = $this->metadata->get('scopes', []);

            foreach ($scopes as $scope => $data) {
                /** @var ?string $statusField */
                $statusField = $data['statusField'] ?? null;

                if (!$statusField) {
                    continue;
                }

                $this->statusFields[$scope] = $statusField;
            }
        }

        return $this->statusFields;
    }

    public function checkIsFollowed(Entity $entity, ?string $userId = null): bool
    {
        if (!$userId) {
            $userId = $this->user->getId();
        }

        $isFollowed = (bool) $this->entityManager
            ->getRDBRepository('Subscription')
            ->select(['id'])
            ->where([
                'userId' => $userId,
                'entityType' => $entity->getEntityType(),
                'entityId' => $entity->getId(),
            ])
            ->findOne();

        return $isFollowed;
    }

    /**
     * @param string[] $sourceUserIdList
     */
    public function followEntityMass(Entity $entity, array $sourceUserIdList, bool $skipAclCheck = false): void
    {
        if (!$this->metadata->get(['scopes', $entity->getEntityType(), 'stream'])) {
            return;
        }

        $userIdList = [];

        foreach ($sourceUserIdList as $id) {
            if ($id === self::SYSTEM_USER_ID) {
                continue;
            }

            $userIdList[] = $id;
        }

        $userIdList = array_unique($userIdList);

        if (!$skipAclCheck) {
            foreach ($userIdList as $i => $userId) {
                $user = $this->entityManager
                    ->getRDBRepository(User::ENTITY_TYPE)
                    ->select(['id', 'type', 'isActive'])
                    ->where([
                        'id' => $userId,
                        'isActive' => true,
                    ])
                    ->findOne();

                if (!$user) {
                    unset($userIdList[$i]);
                    continue;
                }

                try {
                    $hasAccess = $this->aclManager->checkEntityStream($user, $entity);
                }
                catch (AclNotImplemented $e) {
                    $hasAccess = false;
                }

                if (!$hasAccess) {
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
                'entityId' => $entity->getId(),
                'entityType' => $entity->getEntityType(),
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($delete);

        $collection = new EntityCollection();

        foreach ($userIdList as $userId) {
            $subscription = $this->entityManager->getNewEntity('Subscription');

            $subscription->set([
                'userId' => $userId,
                'entityId' => $entity->getId(),
                'entityType' => $entity->getEntityType(),
            ]);

            $collection[] = $subscription;
        }

        $this->entityManager->getMapper()->massInsert($collection);
    }

    public function followEntity(Entity $entity, string $userId, bool $skipAclCheck = false): bool
    {
        if ($userId === self::SYSTEM_USER_ID) {
            return false;
        }

        if (!$this->metadata->get(['scopes', $entity->getEntityType(), 'stream'])) {
            return false;
        }

        if (!$skipAclCheck) {
            $user = $this->entityManager
                ->getRDBRepository(User::ENTITY_TYPE)
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

            if (!$aclManager->check($user, $entity, Table::ACTION_STREAM)) {
                return false;
            }
        }

        if ($this->checkIsFollowed($entity, $userId)) {
            return true;
        }

        $this->entityManager->createEntity('Subscription', [
            'entityId' => $entity->getId(),
            'entityType' => $entity->getEntityType(),
            'userId' => $userId,
        ]);

        return true;
    }

    public function unfollowEntity(Entity $entity, string $userId): bool
    {
        if (!$this->metadata->get('scopes.' . $entity->getEntityType() . '.stream')) {
            return false;
        }

        $delete = $this->entityManager->getQueryBuilder()
            ->delete()
            ->from('Subscription')
            ->where([
                'userId' => $userId,
                'entityId' => $entity->getId(),
                'entityType' => $entity->getEntityType(),
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($delete);

        return true;
    }

    public function unfollowAllUsersFromEntity(Entity $entity): void
    {
        if (!$entity->hasId()) {
            return;
        }

        $delete = $this->entityManager->getQueryBuilder()
            ->delete()
            ->from('Subscription')
            ->where([
                'entityId' => $entity->getId(),
                'entityType' => $entity->getEntityType(),
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($delete);
    }

    /**
     * @param array{
     *   offset?: int|null,
     *   maxSize: int|null,
     *   skipOwn?: bool,
     *   where?: ?array<mixed,mixed>,
     *   after?: ?string,
     *   filter?: ?string,
     * } $params
     * @throws NotFound
     * @throws Forbidden
     */
    public function findUserStream(string $userId, array $params): stdClass
    {
        $offset = intval($params['offset'] ?? 0);
        $maxSize = intval($params['maxSize']);

        $sqLimit = $offset + $maxSize + 1;

        if ($userId === $this->user->getId()) {
            $user = $this->user;
        }
        else {
            /** @var ?User $user */
            $user = $this->entityManager->getEntity(User::ENTITY_TYPE, $userId);

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
                ->from(NoteEntity::ENTITY_TYPE)
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
            $baseBuilder->from(NoteEntity::ENTITY_TYPE);
        }

        $baseBuilder
            ->select($select)
            ->order('number', 'DESC')
            ->limit(0, $sqLimit)
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
                    'subscription.userId' => $user->getId(),
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

            if ($aclManager && $aclManager->check($user, Email::ENTITY_TYPE, Table::ACTION_READ)) {
                $orGroup[] = [
                    'relatedId!=' => null,
                    'relatedType' => Email::ENTITY_TYPE,
                    'noteUser.userId' => $user->getId(),
                ];

                $subscriptionBuilder->leftJoin(
                    'noteUser',
                    'noteUser', [
                        'noteUser.noteId=:' => 'id',
                        'noteUser.deleted' => false,
                        'note.relatedType' => Email::ENTITY_TYPE,
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
                                'noteUser.userId' => $user->getId(),
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
                        'noteUser.userId' => $user->getId(),
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
                    'subscription.userId' => $user->getId(),
                ]
            )
            ->leftJoin(
                'Subscription',
                'subscriptionExclude',
                [
                    'entityType:' => 'parentType',
                    'entityId:' => 'parentId',
                    'subscription.userId' => $user->getId(),
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
                                'noteUser.userId' => $user->getId(),
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
                        'noteUser.userId' => $user->getId(),
                    ]);

                $queryList[] = $subscriptionSuperOwnBuilder->build();
            }
        }

        $queryList[] = (clone $baseBuilder)
            ->leftJoin('users')
            ->leftJoin('createdBy')
            ->where([
                'createdById!=' => $user->getId(),
                'usersMiddle.userId' => $user->getId(),
                'parentId' => null,
                'type' => NoteEntity::TYPE_POST,
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
                        'type' => NoteEntity::TYPE_POST,
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
                    'type' => NoteEntity::TYPE_POST,
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
                        'createdById!=' => $this->user->getId(),
                    ])
                    ->build();
            }
        }

        $queryList[] = (clone $baseBuilder)
            ->leftJoin('createdBy')
            ->where([
                'createdById' => $user->getId(),
                'parentId' => null,
                'type' => NoteEntity::TYPE_POST,
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
                    'type' => NoteEntity::TYPE_POST,
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
            ->getRDBRepository(NoteEntity::ENTITY_TYPE)
            ->findBySql($sql);

        $collection = $this->entityManager
            ->getCollectionFactory()
            ->createFromSthCollection($sthCollection);

        foreach ($collection as $e) {
            $this->loadNoteAdditionalFields($e);

            $this->applyAccessControlToNote($e, $user);
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

    /**
     * @param array{
     *   offset?: int|null,
     *   maxSize: int|null,
     *   skipOwn?: bool,
     *   where?: ?array<mixed,mixed>,
     *   after?: ?string,
     *   filter?: ?string,
     * } $params
     * @return array<mixed,mixed>
     */
    private function getUserStreamWhereClause(array $params, User $user): array
    {
        $whereClause = [];

        if (!empty($params['after'])) {
            $whereClause[]['createdAt>'] = $params['after'];
        }

        if (!empty($params['filter'])) {
            switch ($params['filter']) {
                case 'posts':
                    $whereClause[]['type'] = NoteEntity::TYPE_POST;

                    break;

                  case 'updates':
                    $whereClause[]['type'] = [
                        NoteEntity::TYPE_UPDATE,
                        NoteEntity::TYPE_STATUS,
                    ];

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

            if (in_array(Email::ENTITY_TYPE, $ignoreScopeList)) {
                $whereClause[] = [
                    'type!=' => [
                        NoteEntity::TYPE_EMAIL_RECEIVED,
                        NoteEntity::TYPE_EMAIL_SENT,
                    ],
                ];
            }
        }

        return $whereClause;
    }

    private function loadNoteAdditionalFields(NoteEntity $note): void
    {
        $note->loadAdditionalFields();
    }

    /**
     * @param array{
     *   offset?: int|null,
     *   maxSize: int|null,
     *   skipOwn?: bool,
     *   where?: ?array<mixed,mixed>,
     *   after?: ?string,
     *   filter?: ?string,
     * } $params
     * @throws NotFound
     * @throws Forbidden
     */
    public function find(string $scope, ?string $id, array $params): stdClass
    {
        if ($scope === User::ENTITY_TYPE) {
            if (empty($id)) {
                $id = $this->user->getId();
            }

            return $this->findUserStream($id, $params);
        }

        $entity = $this->entityManager->getEntity($scope, $id);

        $onlyTeamEntityTypeList = $this->getOnlyTeamEntityTypeList($this->user);
        $onlyOwnEntityTypeList = $this->getOnlyOwnEntityTypeList($this->user);

        if (empty($entity)) {
            throw new NotFound();
        }

        if (!$this->acl->checkEntity($entity, Table::ACTION_STREAM)) {
            throw new Forbidden();
        }

        $additionalQuery = null;

        if (!empty($params['where'])) {
            $searchParams = SearchParams::fromRaw([
                'where' => $params['where'],
            ]);

            $additionalQuery = $this->selectBuilderFactory
                ->create()
                ->from(NoteEntity::ENTITY_TYPE)
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
            $builder->from(NoteEntity::ENTITY_TYPE);
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

            if ($this->acl->check(Email::ENTITY_TYPE, Table::ACTION_READ)) {
                $builder->leftJoin(
                    'noteUser',
                    'noteUser',
                    [
                        'noteUser.noteId=:' => 'id',
                        'noteUser.deleted' => false,
                        'note.relatedType' => Email::ENTITY_TYPE,
                    ]
                );

                $orGroup[] = [
                    'relatedId!=' => null,
                    'relatedType' => Email::ENTITY_TYPE,
                    'noteUser.userId' => $this->user->getId(),
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
                                    'usersMiddle.userId' => $this->user->getId(),
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
                            'usersMiddle.userId' => $this->user->getId(),
                        ]
                    ]
                ];
            }
        }

        if (!empty($params['filter'])) {
            switch ($params['filter']) {
                case 'posts':
                    $where['type'] = NoteEntity::TYPE_POST;

                    break;

                  case 'updates':
                    $where['type'] = [
                        NoteEntity::TYPE_ASSIGN,
                        NoteEntity::TYPE_STATUS,
                    ];

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

            if (in_array(Email::ENTITY_TYPE, $ignoreScopeList)) {
                $where[] = [
                    'type!=' => [
                        NoteEntity::TYPE_EMAIL_RECEIVED,
                        NoteEntity::TYPE_EMAIL_SENT,
                    ]
                ];
            }
        }

        $builder->where($where);

        if (!empty($params['after'])) {
            $builder->where([
                'createdAt>' => $params['after'],
            ]);
        }

        $countBuilder = clone $builder;

        $builder
            ->limit($params['offset'] ?? 0, $params['maxSize'])
            ->order('number', 'DESC');

        /** @var iterable<NoteEntity> $collection */
        $collection = $this->entityManager
            ->getRDBRepository(NoteEntity::ENTITY_TYPE)
            ->clone($builder->build())
            ->find();

        foreach ($collection as $e) {
            if (
                $e->getType() === NoteEntity::TYPE_POST ||
                $e->getType() === NoteEntity::TYPE_EMAIL_RECEIVED
            ) {
                $e->loadAttachments();
            }

            if (
                $e->getParentId() && $e->getParentType() &&
                ($e->getParentId() !== $id || $e->getParentType() !== $scope)
            ) {
                $e->loadParentNameField('parent');
            }

            if ($e->getRelatedId() && $e->getRelatedType()) {
                $e->loadParentNameField('related');
            }

            $this->applyAccessControlToNote($e);
        }

        $count = $this->entityManager
            ->getRDBRepository(NoteEntity::ENTITY_TYPE)
            ->clone($countBuilder->build())
            ->count();

        return (object) [
            'total' => $count,
            'collection' => $collection,
        ];
    }

    private function loadAssignedUserName(Entity $entity): void
    {
        $user = $this->entityManager
            ->getRDBRepository(User::ENTITY_TYPE)
            ->select(['name'])
            ->where([
                'id' =>  $entity->get('assignedUserId'),
            ])
            ->findOne();

        if ($user) {
            $entity->set('assignedUserName', $user->get('name'));
        }
    }

    /**
     * Notes having `related` or `superParent` are subjects to access control
     * through `users` and `teams` fields.
     *
     * When users or teams of `related` or `parent` record are changed
     * the note record will be changed too.
     */
    private function processNoteTeamsUsers(NoteEntity $note, Entity $entity): void
    {
        if (!$entity instanceof CoreEntity) {
            return;
        }

        $note->setAclIsProcessed();

        $note->set('teamsIds', []);
        $note->set('usersIds', []);

        if ($entity->hasLinkMultipleField('teams') && $entity->has('teamsIds')) {
            $teamIdList = $entity->get('teamsIds');

            if (!empty($teamIdList)) {
                $note->set('teamsIds', $teamIdList);
            }
        }

        $ownerUserField = $this->aclManager->getReadOwnerUserField($entity->getEntityType());

        if (!$ownerUserField) {
            return;
        }

        $defs = $this->entityManager->getDefs()->getEntity($entity->getEntityType());

        if (!$defs->hasField($ownerUserField)) {
            return;
        }

        $fieldDefs = $defs->getField($ownerUserField);

        if ($fieldDefs->getType() === 'linkMultiple') {
            $ownerUserIdAttribute = $ownerUserField . 'Ids';
        }
        else if ($fieldDefs->getType() === 'link') {
            $ownerUserIdAttribute = $ownerUserField . 'Id';
        }
        else {
            return;
        }

        if (!$entity->has($ownerUserIdAttribute)) {
            return;
        }

        if ($fieldDefs->getType() === 'linkMultiple') {
            $userIdList = $entity->getLinkMultipleIdList($ownerUserField);
        }
        else {
            $userId = $entity->get($ownerUserIdAttribute);

            if (!$userId) {
                return;
            }

            $userIdList = [$userId];
        }

        $note->set('usersIds', $userIdList);
    }

    public function noteEmailReceived(Entity $entity, Email $email, bool $isInitial = false): void
    {
        $entityType = $entity->getEntityType();

        if (
            $this->entityManager
                ->getRDBRepository(NoteEntity::ENTITY_TYPE)
                ->where([
                    'type' => NoteEntity::TYPE_EMAIL_RECEIVED,
                    'parentId' => $entity->getId(),
                    'parentType' => $entityType,
                    'relatedId' => $email->getId(),
                    'relatedType' => Email::ENTITY_TYPE,
                ])
                ->findOne()
        ) {
            return;
        }

        /** @var NoteEntity $note */
        $note = $this->entityManager->getNewEntity(NoteEntity::ENTITY_TYPE);

        $note->set('type', NoteEntity::TYPE_EMAIL_RECEIVED);
        $note->set('parentId', $entity->getId());
        $note->set('parentType', $entityType);
        $note->set('relatedId', $email->getId());
        $note->set('relatedType', Email::ENTITY_TYPE);

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

        $data['emailId'] = $email->getId();
        $data['emailName'] = $email->get('name');
        $data['isInitial'] = $isInitial;

        if ($withContent) {
            $data['attachmentsIds'] = $email->get('attachmentsIds');
        }

        $from = $email->get('from');

        if ($from) {
            $person = $this->getEmailAddressRepository()->getEntityByAddress($from);

            if ($person) {
                $data['personEntityType'] = $person->getEntityType();
                $data['personEntityName'] = $person->get('name');
                $data['personEntityId'] = $person->getId();
            }
        }

        $note->set('data', (object) $data);

        $this->entityManager->saveEntity($note);
    }

    public function noteEmailSent(Entity $entity, Email $email): void
    {
        $entityType = $entity->getEntityType();

        /** @var NoteEntity $note */
        $note = $this->entityManager->getNewEntity(NoteEntity::ENTITY_TYPE);

        $note->set('type', NoteEntity::TYPE_EMAIL_SENT);
        $note->set('parentId', $entity->getId());
        $note->set('parentType', $entityType);
        $note->set('relatedId', $email->getId());
        $note->set('relatedType', Email::ENTITY_TYPE);

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

        $data['emailId'] = $email->getId();
        $data['emailName'] = $email->get('name');

        if ($withContent) {
            $data['attachmentsIds'] = $email->get('attachmentsIds');
        }

        $user = $this->user;

        $person = null;

        if (!$user->isSystem()) {
            $person = $user;
        }
        else {
            $from = $email->get('from');

            if ($from) {
                $person = $this->getEmailAddressRepository()->getEntityByAddress($from);
            }
        }

        if ($person) {
            $data['personEntityType'] = $person->getEntityType();
            $data['personEntityName'] = $person->get('name');
            $data['personEntityId'] = $person->getId();
        }

        $note->set('data', (object) $data);

        $this->entityManager->saveEntity($note);
    }

    /**
     * @param array<string,mixed> $options
     */
    public function noteCreate(Entity $entity, array $options = []): void
    {
        $entityType = $entity->getEntityType();

        /** @var NoteEntity $note */
        $note = $this->entityManager->getNewEntity(NoteEntity::ENTITY_TYPE);

        $note->set('type', NoteEntity::TYPE_CREATE);
        $note->set('parentId', $entity->getId());
        $note->set('parentType', $entityType);

        if ($entity->has('accountId') && $entity->get('accountId')) {
            $note->set('superParentId', $entity->get('accountId'));
            $note->set('superParentType', 'Account');

            // only if has super parent
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

    /**
     * @param mixed $value
     */
    private function getStatusStyle(string $entityType, string $field, $value): string
    {
        $style = $this->metadata->get(['entityDefs', $entityType, 'fields', $field, 'style', $value]);

        if ($style) {
            return $style;
        }

        $statusStyles = $this->getStatusStyles();

        if (isset($statusStyles[$entityType]) && isset($statusStyles[$entityType][$value])) {
            return (string) $statusStyles[$entityType][$value];
        }

        if (in_array($value, $this->successDefaultStyleList)) {
            return 'success';
        }

        if (in_array($value, $this->dangerDefaultStyleList)) {
            return 'danger';
        }

        return 'default';
    }

    /**
     * @param array<string,mixed> $options
     */
    public function noteCreateRelated(
        Entity $entity,
        string $parentType,
        string $parentId,
        array $options = []
    ): void {

        /** @var NoteEntity $note */
        $note = $this->entityManager->getNewEntity(NoteEntity::ENTITY_TYPE);

        $entityType = $entity->getEntityType();

        $note->set('type', NoteEntity::TYPE_CREATE_RELATED);
        $note->set('parentId', $parentId);
        $note->set('parentType', $parentType);
        $note->set([
            'relatedType' => $entityType,
            'relatedId' => $entity->getId(),
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

    /**
     * @param array<string,mixed> $options
     */
    public function noteRelate(Entity $entity, string $parentType, string $parentId, array $options = []): void
    {
        $entityType = $entity->getEntityType();

        $existing = $this->entityManager
            ->getRDBRepository(NoteEntity::ENTITY_TYPE)
            ->select(['id'])
            ->where([
                'type' => NoteEntity::TYPE_RELATE,
                'parentId' => $parentId,
                'parentType' => $parentType,
                'relatedId' => $entity->getId(),
                'relatedType' => $entityType,
            ])
            ->findOne();

        if ($existing) {
            return;
        }

        /** @var NoteEntity $note */
        $note = $this->entityManager->getNewEntity(NoteEntity::ENTITY_TYPE);

        $note->set([
            'type' => NoteEntity::TYPE_RELATE,
            'parentId' => $parentId,
            'parentType' => $parentType,
            'relatedType' => $entityType,
            'relatedId' => $entity->getId(),
        ]);

        $this->processNoteTeamsUsers($note, $entity);

        $o = [];

        if (!empty($options['createdById'])) {
            $o['createdById'] = $options['createdById'];
        }

        $this->entityManager->saveEntity($note, $o);
    }

    /**
     * @param array<string,mixed> $options
     */
    public function noteAssign(Entity $entity, array $options = []): void
    {
        /** @var NoteEntity $note */
        $note = $this->entityManager->getNewEntity(NoteEntity::ENTITY_TYPE);

        $note->set('type', NoteEntity::TYPE_ASSIGN);
        $note->set('parentId', $entity->getId());
        $note->set('parentType', $entity->getEntityType());

        if ($entity->has('accountId') && $entity->get('accountId')) {
            $note->set('superParentId', $entity->get('accountId'));
            $note->set('superParentType', 'Account');

            // only if has super parent
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

    /**
     * @param array<string,mixed> $options
     */
    public function noteStatus(Entity $entity, string $field, array $options = []): void
    {
        /** @var NoteEntity $note */
        $note = $this->entityManager->getNewEntity(NoteEntity::ENTITY_TYPE);

        $note->set('type', NoteEntity::TYPE_STATUS);
        $note->set('parentId', $entity->getId());
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
            'style' => $style,
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

    /**
     * @return array<
     *   string,
     *   array{
     *     actualList: string[],
     *     notActualList: string[],
     *     fieldType: string,
     *   }
     * >
     */
    private function getAuditedFieldsData(Entity $entity): array
    {
        $entityType = $entity->getEntityType();

        $statusFields = $this->getStatusFields();

        if (array_key_exists($entityType, $this->auditedFieldsCache)) {
            return $this->auditedFieldsCache[$entityType];
        }

        /** @var array<string,array<string,mixed>> $fields */
        $fields = $this->metadata->get('entityDefs.' . $entityType . '.fields');

        $auditedFields = [];

        foreach ($fields as $field => $defs) {
            if (empty($defs['audited'])) {
                continue;
            }

            if (!empty($statusFields[$entityType]) && $statusFields[$entityType] === $field) {
                continue;
            }

            /** @var ?string $type */
            $type = $defs['type'] ?? null;

            if (!$type) {
                continue;
            }

            $auditedFields[$field] = [];

            $auditedFields[$field]['actualList'] =
                $this->fieldUtil->getActualAttributeList($entityType, $field);

            $auditedFields[$field]['notActualList'] =
                $this->fieldUtil->getNotActualAttributeList($entityType, $field);

            $auditedFields[$field]['fieldType'] = $type;
        }

        $this->auditedFieldsCache[$entityType] = $auditedFields;

        return $this->auditedFieldsCache[$entityType];
    }

    /**
     * @param array<string,mixed> $options
     */
    public function handleAudited(Entity $entity, array $options = []): void
    {
        $auditedFields = $this->getAuditedFieldsData($entity);

        $updatedFieldList = [];

        $was = [];
        $became = [];

        $entityDefs = $this->entityManager
            ->getDefs()
            ->getEntity($entity->getEntityType());

        foreach ($auditedFields as $field => $item) {
            $updated = false;

            foreach ($item['actualList'] as $attribute) {
                if ($entity->hasFetched($attribute) && $entity->isAttributeChanged($attribute)) {
                    $updated = true;

                    break;
                }
            }

            if (!$updated) {
                continue;
            }

            $updatedFieldList[] = $field;

            $fieldDefs = $entityDefs->hasField($field) ? $entityDefs->getField($field) : null;

            if (
                $fieldDefs &&
                in_array($fieldDefs->getType(), ['text', 'wysiwyg'])
            ) {
                continue;
            }

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

                if ($wasParentType && $wasParentId && $this->entityManager->hasRepository($wasParentType)) {
                    $wasParent = $this->entityManager->getEntity($wasParentType, $wasParentId);

                    if ($wasParent) {
                        $was[$field . 'Name'] = $wasParent->get('name');
                    }
                }
            }
        }

        if (count($updatedFieldList) === 0) {
            return;
        }

        /** @var NoteEntity $note */
        $note = $this->entityManager->getNewEntity(NoteEntity::ENTITY_TYPE);

        $note->set('type', NoteEntity::TYPE_UPDATE);
        $note->set('parentId', $entity->getId());
        $note->set('parentType', $entity->getEntityType());

        $note->set('data', [
            'fields' => $updatedFieldList,
            'attributes' => [
                'was' => (object) $was,
                'became' => (object) $became,
            ],
        ]);

        $o = [];

        if (!empty($options['modifiedById'])) {
            $o['createdById'] = $options['modifiedById'];
        }

        $this->entityManager->saveEntity($note, $o);
    }

    /**
     * @return string[]
     */
    public function getEntityFolowerIdList(Entity $entity): array
    {
        $userList = $this->entityManager
            ->getRDBRepository(User::ENTITY_TYPE)
            ->select(['id'])
            ->join('Subscription', 'subscription', [
                'subscription.userId=:' => 'user.id',
                'subscription.entityId' => $entity->getId(),
                'subscription.entityType' => $entity->getEntityType(),
            ])
            ->where(['isActive' => true])
            ->find();

        $idList = [];

        foreach ($userList as $user) {
            $idList[] = $user->getId();
        }

        return $idList;
    }

    /**
     * @return RecordCollection<User>
     */
    public function findEntityFollowers(Entity $entity, SearchParams $searchParams): RecordCollection
    {
        $builder = $this->selectBuilderFactory
            ->create()
            ->from(User::ENTITY_TYPE)
            ->withSearchParams($searchParams)
            ->withStrictAccessControl()
            ->buildQueryBuilder();

        if (!$searchParams->getOrderBy()) {
            $builder->order('LIST:id:' . $this->user->getId(), 'DESC');
            $builder->order('name');
        }

        $builder->join(
            'Subscription',
            'subscription',
            [
                'subscription.userId=:' => 'user.id',
                'subscription.entityId' => $entity->getId(),
                'subscription.entityType' => $entity->getEntityType(),
            ]
        );

        $query = $builder->build();

        /** @var \Espo\ORM\Collection<User> $collection */
        $collection = $this->entityManager
            ->getRDBRepository(User::ENTITY_TYPE)
            ->clone($query)
            ->find();

        $total = $this->entityManager
            ->getRDBRepository(User::ENTITY_TYPE)
            ->clone($query)
            ->count();

        $userService = $this->recordServiceContainer->get(User::ENTITY_TYPE);

        foreach ($collection as $e) {
            $userService->prepareEntityForOutput($e);
        }

        /** @var RecordCollection<User> */
        return new RecordCollection($collection, $total);
    }

    /**
     * @return array{
     *   idList: string[],
     *   nameMap: stdClass,
     * }
     */
    public function getEntityFollowers(Entity $entity, int $offset = 0, ?int $limit = null): array
    {
        if (!$limit) {
            $limit = 200;
        }

        $userList = $this->entityManager
            ->getRDBRepository(User::ENTITY_TYPE)
            ->select(['id', 'name'])
            ->join(
                'Subscription',
                'subscription',
                [
                    'subscription.userId=:' => 'user.id',
                    'subscription.entityId' => $entity->getId(),
                    'subscription.entityType' => $entity->getEntityType()
                ]
            )
            ->limit($offset, $limit)
            ->where([
                'isActive' => true,
            ])
            ->order([
                ['LIST:id:' . $this->user->getId(), 'DESC'],
                ['name'],
            ])
            ->find();

        $data = [
            'idList' => [],
            'nameMap' => (object) [],
        ];

        foreach ($userList as $user) {
            /** @var string $id */
            $id = $user->getId();

            $data['idList'][] = $id;
            $data['nameMap']->$id = $user->get('name');
        }

        return $data;
    }

    /**
     * @return string[]
     */
    private function getOnlyTeamEntityTypeList(User $user): array
    {
        if ($user->isPortal()) {
            return [];
        }

        $list = [];

        $scopes = $this->metadata->get('scopes', []);

        foreach ($scopes as $scope => $item) {
            if ($scope === User::ENTITY_TYPE) {
                continue;
            }

            if (empty($item['entity'])) {
                continue;
            }

            if (empty($item['object'])) {
                continue;
            }

            if (
                $this->aclManager->checkReadOnlyTeam($user, $scope)
            ) {
                $list[] = $scope;
            }
        }

        return $list;
    }

    /**
     * @return string[]
     */
    private function getOnlyOwnEntityTypeList(User $user): array
    {
        if ($user->isPortal()) {
            return [];
        }

        $list = [];

        $scopes = $this->metadata->get('scopes', []);

        foreach ($scopes as $scope => $item) {
            if ($scope === User::ENTITY_TYPE) {
                continue;
            }

            if (empty($item['entity'])) {
                continue;
            }

            if (empty($item['object'])) {
                continue;
            }

            if (
                $this->aclManager->checkReadOnlyOwn($user, $scope)
            ) {
                $list[] = $scope;
            }
        }

        return $list;
    }

    private function getUserAclManager(User $user): ?AclManager
    {
        try {
            return $this->userAclManagerProvider->get($user);
        }
        catch (Error $e) {
            return null;
        }
    }

    /**
     * @return string[]
     */
    private function getNotAllEntityTypeList(User $user): array
    {
        if (!$user->isPortal()) {
            return [];
        }

        $aclManager = $this->getUserAclManager($user);

        $list = [];

        $scopes = $this->metadata->get('scopes', []);

        foreach ($scopes as $scope => $item) {
            if ($scope === User::ENTITY_TYPE) {
                continue;
            }

            if (empty($item['entity'])) {
                continue;
            }

            if (empty($item['object'])) {
                continue;
            }

            if (
                !$aclManager ||
                $aclManager->getLevel($user, $scope, Table::ACTION_READ) !== Table::LEVEL_ALL
            ) {
                $list[] = $scope;
            }
        }

        return $list;
    }

    /**
     * @return string[]
     */
    private function getIgnoreScopeList(User $user): array
    {
        $ignoreScopeList = [];

        $scopes = $this->metadata->get('scopes', []);

        $aclManager = $this->getUserAclManager($user);

        foreach ($scopes as $scope => $item) {
            if (empty($item['entity'])) {
                continue;
            }

            if (empty($item['object'])) {
                continue;
            }

            try {
                $hasAccess =
                    $aclManager &&
                    $aclManager->checkScope($user, $scope, Table::ACTION_READ) &&
                    $aclManager->checkScope($user, $scope, Table::ACTION_STREAM);
            }
            catch (AclNotImplemented $e) {
                $hasAccess = false;
            }

            if (!$hasAccess) {
                $ignoreScopeList[] = $scope;
            }
        }

        return $ignoreScopeList;
    }

    /**
     * @return Collection<User>
     */
    public function getSubscriberList(string $parentType, string $parentId, bool $isInternal = false): Collection
    {
        if (!$this->metadata->get(['scopes', $parentType, 'stream'])) {
            /** @var Collection<User> */
            return $this->entityManager->getCollectionFactory()->create(User::ENTITY_TYPE, []);
        }

        $builder = $this->entityManager
            ->getQueryBuilder()
            ->select()
            ->from('Subscription')
            ->select('userId')
            ->where([
                'entityId' => $parentId,
                'entityType' => $parentType,
            ]);

        if ($isInternal) {
            $builder
                ->join(User::ENTITY_TYPE, 'user', ['user.id:' => 'userId'])
                ->where([
                    'user.type!=' => User::TYPE_PORTAL,
                ]);
        }

        $subQuery = $builder->build();

        /** @var Collection<User> */
        return $this->entityManager
            ->getRDBRepository(User::ENTITY_TYPE)
            ->where([
                'isActive' => true,
                'id=s' => $subQuery->getRaw(),
            ])
            ->select(['id', 'type'])
            ->find();
    }

    public function processNoteAclJob(stdClass $data): void
    {
        $targetType = $data->targetType;
        $targetId = $data->targetId;

        if ($targetType && $targetId && $this->entityManager->hasRepository($targetType)) {
            $entity = $this->entityManager->getEntity($targetType, $targetId);

            if ($entity) {
                $this->processNoteAcl($entity, true);
            }
        }
    }

    /**
     * Changes users and teams of notes related to an entity according users and teams of the entity.
     *
     * Notes having `related` or `superParent` are subjects to access control
     * through `users` and `teams` fields.
     *
     * When users or teams of `related` or `parent` record are changed
     * the note record will be changed too.
     */
    public function processNoteAcl(Entity $entity, bool $forceProcessNoteNotifications = false): void
    {
        if (!$entity instanceof CoreEntity) {
            return;
        }

        $entityType = $entity->getEntityType();

        if (in_array($entityType, ['Note', 'User', 'Team', 'Role', 'Portal', 'PortalRole'])) {
            return;
        }

        if (!$this->metadata->get(['scopes', $entityType, 'acl'])) {
            return;
        }

        if (!$this->metadata->get(['scopes', $entityType, 'object'])) {
            return;
        }

        $usersAttributeIsChanged = false;
        $teamsAttributeIsChanged = false;

        $ownerUserField = $this->aclManager->getReadOwnerUserField($entityType);

        /* @var \Espo\ORM\Defs\EntityDefs $defs */
        $defs = $this->entityManager->getDefs()->getEntity($entity->getEntityType());

        $userIdList = [];
        $teamIdList = [];

        if ($ownerUserField) {
            if (!$defs->hasField($ownerUserField)) {
                throw new LogicException("Non-existing read-owner user field.");
            }

            $fieldDefs = $defs->getField($ownerUserField);

            if ($fieldDefs->getType() === 'linkMultiple') {
                $ownerUserIdAttribute = $ownerUserField . 'Ids';
            }
            else if ($fieldDefs->getType() === 'link') {
                $ownerUserIdAttribute = $ownerUserField . 'Id';
            }
            else {
                throw new LogicException("Bad read-owner user field type.");
            }

            if ($entity->isAttributeChanged($ownerUserIdAttribute)) {
                $usersAttributeIsChanged = true;
            }

            if ($usersAttributeIsChanged || $forceProcessNoteNotifications) {
                if ($fieldDefs->getType() === 'linkMultiple') {
                    $userIdList = $entity->getLinkMultipleIdList($ownerUserField) ?? [];
                }
                else {
                    $userId = $entity->get($ownerUserIdAttribute);

                    $userIdList = $userId ? [$userId] : [];
                }
            }
        }

        if ($entity->hasLinkMultipleField('teams')) {
            if ($entity->isAttributeChanged('teamsIds')) {
                $teamsAttributeIsChanged = true;
            }

            if ($teamsAttributeIsChanged || $forceProcessNoteNotifications) {
                $teamIdList = $entity->getLinkMultipleIdList('teams') ?? [];
            }
        }

        if (!$usersAttributeIsChanged && !$teamsAttributeIsChanged && !$forceProcessNoteNotifications) {
            return;
        }

        $limit = $this->config->get('noteAclLimit', self::NOTE_ACL_LIMIT);

        $noteList = $this->entityManager
            ->getRDBRepository(NoteEntity::ENTITY_TYPE)
            ->where([
                'OR' => [
                    [
                        'relatedId' => $entity->getId(),
                        'relatedType' => $entityType,
                    ],
                    [
                        'parentId' => $entity->getId(),
                        'parentType' => $entityType,
                        'superParentId!=' => null,
                        'relatedId' => null,
                    ]
                ]
            ])
            ->select([
                'id',
                'parentType',
                'parentId',
                'superParentType',
                'superParentId',
                'isInternal',
                'relatedType',
                'relatedId',
                'createdAt',
            ])
            ->order('number', 'DESC')
            ->limit(0, $limit)
            ->find();

        $notificationPeriod = '-' . $this->config->get('noteNotificationPeriod', self::NOTE_NOTIFICATION_PERIOD);
        $aclPeriod = '-' . $this->config->get('noteAclPeriod', self::NOTE_ACL_PERIOD);

        $notificationThreshold = (new DateTime())->modify($notificationPeriod);
        $aclThreshold = (new DateTime())->modify($aclPeriod);

        foreach ($noteList as $note) {
            $this->processNoteAclItem($entity, $note, [
                'teamsAttributeIsChanged' => $teamsAttributeIsChanged,
                'usersAttributeIsChanged' => $usersAttributeIsChanged,
                'forceProcessNoteNotifications' => $forceProcessNoteNotifications,
                'teamIdList' => $teamIdList,
                'userIdList' => $userIdList,
                'notificationThreshold' => $notificationThreshold,
                'aclThreshold' => $aclThreshold,
            ]);
        }
    }

    /**
     * @param array{
     *   teamsAttributeIsChanged: bool,
     *   usersAttributeIsChanged: bool,
     *   forceProcessNoteNotifications: bool,
     *   teamIdList: string[],
     *   userIdList: string[],
     *   notificationThreshold: \DateTimeInterface,
     *   aclThreshold: \DateTimeInterface,
     * } $params
     * @return void
     */
    private function processNoteAclItem(Entity $entity, NoteEntity $note, array $params): void
    {
        $teamsAttributeIsChanged = $params['teamsAttributeIsChanged'];
        $usersAttributeIsChanged = $params['usersAttributeIsChanged'];
        $forceProcessNoteNotifications = $params['forceProcessNoteNotifications'];

        $teamIdList = $params['teamIdList'];
        $userIdList = $params['userIdList'];

        $notificationThreshold = $params['notificationThreshold'];
        $aclThreshold = $params['aclThreshold'];

        $createdAt = $note->getCreatedAt();

        if (!$createdAt) {
            return;
        }

        if (!$entity->isNew()) {
            if ($createdAt->getTimestamp() < $notificationThreshold->getTimestamp()) {
                $forceProcessNoteNotifications = false;
            }

            if ($createdAt->getTimestamp() < $aclThreshold->getTimestamp()) {
                return;
            }
        }

        if ($teamsAttributeIsChanged || $forceProcessNoteNotifications) {
            $note->set('teamsIds', $teamIdList);
        }

        if ($usersAttributeIsChanged || $forceProcessNoteNotifications) {
            $note->set('usersIds', $userIdList);
        }

        $this->entityManager->saveEntity($note, [
            'forceProcessNotifications' => $forceProcessNoteNotifications,
        ]);
    }

    public function applyAccessControlToNote(NoteEntity $note, ?User $user = null): void
    {
        if (!$user) {
            $user = $this->user;
        }

        $this->noteAccessControl->apply($note, $user);
    }

    private function getEmailAddressRepository(): EmailAddressRepository
    {
        /** @var EmailAddressRepository */
        return $this->entityManager->getRepository(EmailAddress::ENTITY_TYPE);
    }
}
