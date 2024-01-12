<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Tools\Stream;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Select\SearchParams;
use Espo\ORM\EntityManager;
use Espo\Entities\Subscription;
use Espo\Entities\User;
use Espo\Entities\Note;
use Espo\Entities\Email;
use Espo\Core\Utils\Metadata;
use Espo\Core\Acl;
use Espo\Core\AclManager;
use Espo\Core\Acl\Table;
use Espo\Core\Record\Collection as RecordCollection;
use Espo\Core\Utils\Acl\UserAclManagerProvider;
use Espo\ORM\Query\Part\Condition as Cond;
use Espo\ORM\Query\Part\Expression as Expr;
use Espo\ORM\Query\Part\Order;
use Espo\ORM\Query\Part\Where\OrGroup;
use Espo\ORM\Query\Select;
use Espo\ORM\Query\SelectBuilder;
use Espo\Tools\Stream\RecordService\Helper;

class UserRecordService
{
    public function __construct(
        private EntityManager $entityManager,
        private User $user,
        private Metadata $metadata,
        private Acl $acl,
        private UserAclManagerProvider $userAclManagerProvider,
        private NoteAccessControl $noteAccessControl,
        private Helper $helper
    ) {}

    /**
     * Find user stream records.
     *
     * @return RecordCollection<Note>
     * @throws Forbidden
     * @throws BadRequest
     * @throws NotFound
     */
    public function find(?string $userId, SearchParams $searchParams): RecordCollection
    {
        $userId ??= $this->user->getId();

        $offset = $searchParams->getOffset() ?? 0;
        $maxSize = $searchParams->getMaxSize();

        $sqLimit = $offset + $maxSize + 1;

        $user = $userId === $this->user->getId() ?
            $this->user :
            $this->entityManager->getRDBRepositoryByClass(User::class)->getById($userId);

        if (!$user) {
            throw new NotFound("User not found.");
        }

        /** @noinspection PhpRedundantOptionalArgumentInspection */
        if (!$this->acl->checkUserPermission($user, 'user')) {
            throw new Forbidden("No user permission access.");
        }

        $queryList = [];

        $baseBuilder = $this->helper->buildBaseQueryBuilder($searchParams)
            ->select([
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
            ])
            ->order('number', Order::DESC)
            ->limit(0, $sqLimit);

        $this->buildSubscriptionQueries($user, $baseBuilder, $queryList);
        $this->buildSubscriptionSuperQuery($user, $baseBuilder, $queryList);
        $this->buildPostedToUserQuery($user, $baseBuilder, $queryList);
        $this->buildPostedToPortalQuery($user, $baseBuilder, $queryList);
        $this->buildPostedToTeamsQuery($user, $baseBuilder, $queryList);
        $this->buildPostedByUserQuery($user, $baseBuilder, $queryList);
        $this->buildPostedToGlobalQuery($user, $baseBuilder, $queryList);

        $builder = $this->entityManager
            ->getQueryBuilder()
            ->union()
            ->all()
            ->order('number', Order::DESC)
            ->limit($offset, $maxSize + 1);

        foreach ($queryList as $query) {
            $builder->query($query);
        }

        $unionQuery = $builder->build();

        $sql = $this->entityManager
            ->getQueryComposer()
            ->compose($unionQuery);

        $sthCollection = $this->entityManager
            ->getRDBRepositoryByClass(Note::class)
            ->findBySql($sql);

        $collection = $this->entityManager
            ->getCollectionFactory()
            ->createFromSthCollection($sthCollection);

        foreach ($collection as $e) {
            $this->loadNoteAdditionalFields($e);
            $this->noteAccessControl->apply($e, $user);
        }

        return RecordCollection::createNoCount($collection, $maxSize);
    }

    /**
     * @return array<string|int, mixed>
     */
    private function getSubscriptionIgnoreWhereClause(User $user): array
    {
        $ignoreScopeList = $this->helper->getIgnoreScopeList($user, true);
        $ignoreRelatedScopeList = $this->helper->getIgnoreScopeList($user);

        if (empty($ignoreScopeList)) {
            return [];
        }

        $whereClause = [];

        $whereClause[] = [
            'OR' => [
                'relatedType' => null,
                'relatedType!=' => $ignoreRelatedScopeList,
            ]
        ];

        $whereClause[] = [
            'OR' => [
                'parentType' => null,
                'parentType!=' => $ignoreScopeList,
            ]
        ];

        if (in_array(Email::ENTITY_TYPE, $ignoreRelatedScopeList)) {
            $whereClause[] = [
                'type!=' => [
                    Note::TYPE_EMAIL_RECEIVED,
                    Note::TYPE_EMAIL_SENT,
                ],
            ];
        }

        return $whereClause;
    }

    private function loadNoteAdditionalFields(Note $note): void
    {
        $note->loadAdditionalFields();
    }

    private function getUserAclManager(User $user): ?AclManager
    {
        try {
            return $this->userAclManagerProvider->get($user);
        }
        catch (Acl\Exceptions\NotAvailable) {
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

            if (empty($item['entity']) || empty($item['object'])) {
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
     * @param Select[] $queryList
     */
    private function buildSubscriptionQueriesPortal(
        User $user,
        SelectBuilder $builder,
        array &$queryList
    ): void {

        if (!$user->isPortal()) {
            return;
        }

        $builder->where([
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

            $builder->leftJoin(
                'noteUser',
                'noteUser', [
                    'noteUser.noteId=:' => 'id',
                    'noteUser.deleted' => false,
                    'note.relatedType' => Email::ENTITY_TYPE,
                ]
            );
        }

        $builder->where([
            'OR' => $orGroup,
        ]);

        $queryList[] = $builder->build();
    }

    /**
     * @param Select[] $queryList
     */
    private function buildSubscriptionQueries(
        User $user,
        SelectBuilder $baseBuilder,
        array &$queryList
    ): void {

        $ignoreWhereClause = $this->getSubscriptionIgnoreWhereClause($user);

        $builder = clone $baseBuilder;

        $builder
            ->leftJoin('createdBy')
            ->join(
                Subscription::ENTITY_TYPE,
                'subscription',
                [
                    'entityType:' => 'parentType',
                    'entityId:' => 'parentId',
                    'subscription.userId' => $user->getId(),
                ]
            )
            ->where($ignoreWhereClause);

        if ($user->isPortal()) {
            $this->buildSubscriptionQueriesPortal($user, $builder, $queryList);

            return;
        }

        $this->buildAccessQueries($user, $builder, $queryList, true);
    }

    /**
     * @param Select[] $queryList
     */
    private function buildSubscriptionSuperQuery(
        User $user,
        SelectBuilder $baseBuilder,
        array &$queryList
    ): void {

        if ($user->isPortal()) {
            return;
        }

        $ignoreWhereClause = $this->getSubscriptionIgnoreWhereClause($user);

        $builder = clone $baseBuilder;

        $builder
            ->join(
                Subscription::ENTITY_TYPE,
                'subscription',
                [
                    'entityType:' => 'superParentType',
                    'entityId:' => 'superParentId',
                    'subscription.userId' => $user->getId(),
                ]
            )
            // NOT EXISTS sub-query would perform very slow.
            ->leftJoin(
                Subscription::ENTITY_TYPE,
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
            ->where($ignoreWhereClause);

        $this->buildAccessQueries($user, $builder, $queryList);
    }

    /**
     * @param Select[] $queryList
     */
    private function buildPostedToUserQuery(
        User $user,
        SelectBuilder $baseBuilder,
        array &$queryList
    ): void {

        $queryList[] = (clone $baseBuilder)
            ->leftJoin('users')
            ->leftJoin('createdBy')
            ->where([
                'createdById!=' => $user->getId(),
                'usersMiddle.userId' => $user->getId(),
                'parentId' => null,
                'type' => Note::TYPE_POST,
                'isGlobal' => false,
            ])
            ->build();
    }

    /**
     * @param Select[] $queryList
     */
    private function buildPostedToPortalQuery(
        User $user,
        SelectBuilder $baseBuilder,
        array &$queryList
    ): void {

        if (!$user->isPortal()) {
            return;
        }

        $portalIdList = $user->getLinkMultipleIdList('portals');

        if ($portalIdList === []) {
            return;
        }

        $queryList[] = (clone $baseBuilder)
            ->leftJoin('portals')
            ->leftJoin('createdBy')
            ->where([
                'parentId' => null,
                'portalsMiddle.portalId' => $portalIdList,
                'type' => Note::TYPE_POST,
                'isGlobal' => false,
            ])
            ->build();
    }

    /**
     * @param Select[] $queryList
     */
    private function buildPostedToTeamsQuery(
        User $user,
        SelectBuilder $baseBuilder,
        array &$queryList
    ): void {

        if ($user->getTeamIdList() === []) {
            return;
        }

        $queryList[] = (clone $baseBuilder)
            ->leftJoin('teams')
            ->leftJoin('createdBy')
            ->where([
                'parentId' => null,
                'teamsMiddle.teamId' => $user->getTeamIdList(),
                'type' => Note::TYPE_POST,
                'isGlobal' => false,
            ])
            ->build();
    }

    /**
     * @param Select[] $queryList
     */
    private function buildPostedByUserQuery(
        User $user,
        SelectBuilder $baseBuilder,
        array &$queryList
    ): void {

        $queryList[] = (clone $baseBuilder)
            ->leftJoin('createdBy')
            ->where([
                'createdById' => $user->getId(),
                'parentId' => null,
                'type' => Note::TYPE_POST,
                'isGlobal' => false,
            ])
            ->build();
    }

    /**
     * @param Select[] $queryList
     */
    private function buildPostedToGlobalQuery(
        User $user,
        SelectBuilder $baseBuilder,
        array &$queryList
    ): void {

        if (
            $user->isPortal() &&
            !$user->isAdmin() || $user->isApi()
        ) {
            return;
        }

        $queryList[] = (clone $baseBuilder)
            ->leftJoin('createdBy')
            ->where([
                'parentId' => null,
                'type' => Note::TYPE_POST,
                'isGlobal' => true,
            ])
            ->build();
    }

    /**
     * Split into tree queries for all, team and own.
     *
     * @param Select[] $queryList
     * @param bool $noParentFilter Don't apply filtering for the 'parent'. Assumed that access is controlled
     *   by subscription.
     */
    private function buildAccessQueries(
        User $user,
        SelectBuilder $baseBuilder,
        array &$queryList,
        bool $noParentFilter = false
    ): void {

        $onlyTeamEntityTypeList = $this->helper->getOnlyTeamEntityTypeList($user);
        $onlyOwnEntityTypeList = $this->helper->getOnlyOwnEntityTypeList($user);

        $allBuilder = clone $baseBuilder;

        $orWhere = [
            [
                'relatedId!=' => null,
                'relatedType!=' => array_merge($onlyTeamEntityTypeList, $onlyOwnEntityTypeList),
            ],
        ];

        if (!$noParentFilter) {
            $orWhere[] = [
                'relatedId=' => null,
                'parentType!=' => array_merge($onlyTeamEntityTypeList, $onlyOwnEntityTypeList),
            ];
        } else {
            $orWhere[] = ['relatedId=' => null];
        }

        $allBuilder->where(['OR' => $orWhere]);

        $queryList[] = $allBuilder->build();

        if ($onlyTeamEntityTypeList !== []) {
            $teamBuilder = clone $baseBuilder;

            $orWhere = [
                ['relatedType=' => $onlyTeamEntityTypeList],
            ];

            if (!$noParentFilter) {
                $orWhere[] = [
                    'relatedId=' => null,
                    'parentType=' => $onlyTeamEntityTypeList,
                ];
            }

            $teamBuilder
                ->where(['OR' => $orWhere])
                ->where(
                    // Separate sub-queries perform faster that a single with two LEFT JOINs inside.
                    OrGroup::create(
                        Cond::in(
                            Expr::column('id'),
                            SelectBuilder::create()
                                ->from('NoteTeam')
                                ->select('noteId')
                                ->where(['teamId' => $user->getTeamIdList()])
                                ->build()
                        ),
                        Cond::in(
                            Expr::column('id'),
                            SelectBuilder::create()
                                ->from('NoteUser')
                                ->select('noteId')
                                ->where(['userId' => $user->getId()])
                                ->build()
                        ),
                    )
                );

            $queryList[] = $teamBuilder->build();
        }

        if ($onlyOwnEntityTypeList !== []) {
            $ownBuilder = clone $baseBuilder;

            $orWhere = [
                ['relatedType=' => $onlyOwnEntityTypeList],
            ];

            if (!$noParentFilter) {
                $orWhere[] = [
                    'relatedId=' => null,
                    'parentType=' => $onlyOwnEntityTypeList,
                ];
            }

            $ownBuilder
                ->where(['OR' => $orWhere])
                ->where(
                    Cond::in(
                        Expr::column('id'),
                        SelectBuilder::create()
                            ->from('NoteUser')
                            ->select('noteId')
                            ->where(['userId' => $user->getId()])
                            ->build()
                    )
                );

            $queryList[] = $ownBuilder->build();
        }
    }
}
