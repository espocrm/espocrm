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

namespace Espo\Tools\Stream\RecordService;

use Espo\Core\Acl\Permission;
use Espo\Core\Acl\Table;
use Espo\Core\AclManager;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Name\Field;
use Espo\Core\Select\SearchParams;
use Espo\Core\Select\SelectBuilderFactory;
use Espo\Entities\Note;
use Espo\Entities\User;
use Espo\ORM\EntityManager;
use Espo\ORM\Query\Part\Condition as Cond;
use Espo\ORM\Query\Part\Expression as Expr;
use Espo\ORM\Query\Select;
use Espo\ORM\Query\SelectBuilder;

class QueryHelper
{
    public function __construct(
        private EntityManager $entityManager,
        private SelectBuilderFactory $selectBuilderFactory,
        private AclManager $aclManager
    ) {}

    /**
     * @throws BadRequest
     * @throws Forbidden
     */
    public function buildBaseQueryBuilder(SearchParams $searchParams): SelectBuilder
    {
        $builder = $this->entityManager
            ->getQueryBuilder()
            ->select()
            ->from(Note::ENTITY_TYPE);

        if (
            $searchParams->getWhere() ||
            $searchParams->getTextFilter() ||
            $searchParams->getPrimaryFilter() ||
            $searchParams->getBoolFilterList() !== []
        ) {
            $builder = $this->selectBuilderFactory
                ->create()
                ->from(Note::ENTITY_TYPE)
                ->withComplexExpressionsForbidden()
                ->withWherePermissionCheck()
                ->withSearchParams(
                    $searchParams
                        ->withOffset(null)
                        ->withMaxSize(null)
                )
                ->buildQueryBuilder()
                ->order([]);
        }

        return $builder;
    }

    /**
     * @return string[]
     */
    public function getUserQuerySelect(): array
    {
        return [
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
            Field::CREATED_AT,
            'createdById',
            'createdByName',
            'isGlobal',
            'isInternal',
            'createdByGender',
        ];
    }

    public function buildPostedToUserQuery(User $user, SelectBuilder $baseBuilder): Select
    {
        return (clone $baseBuilder)
            ->where([
                'type' => Note::TYPE_POST,
                'targetType' => Note::TARGET_USERS,
                'parentId' => null,
                'createdById!=' => $user->getId(),
                'isGlobal' => false,
            ])
            ->where(
                Cond::in(
                    Expr::column('id'),
                    SelectBuilder::create()
                        ->select('noteId')
                        ->from('NoteUser')
                        ->where(['userId' => $user->getId()])
                        ->build()
                )
            )
            ->build();
    }

    public function buildPostedToPortalQuery(User $user, SelectBuilder $baseBuilder): ?Select
    {
        if (!$user->isPortal()) {
            if ($this->aclManager->getPermissionLevel($user, Permission::PORTAL) !== Table::LEVEL_YES) {
                return null;
            }

            return (clone $baseBuilder)
                ->where([
                    'parentId' => null,
                    'type' => Note::TYPE_POST,
                    'targetType' => Note::TARGET_PORTALS,
                    'createdById!=' => $user->getId(),
                    'isGlobal' => false,
                ])
                ->build();
        }

        $portalIdList = $user->getPortals()->getIdList();

        if ($portalIdList === []) {
            return null;
        }

        return (clone $baseBuilder)
            ->where([
                'parentId' => null,
                'type' => Note::TYPE_POST,
                'targetType' => Note::TARGET_PORTALS,
                'createdById!=' => $user->getId(),
                'isGlobal' => false,
            ])
            ->where(
                Cond::in(
                    Expr::column('id'),
                    SelectBuilder::create()
                        ->select('noteId')
                        ->from('NotePortal')
                        ->where(['portalId' => $portalIdList])
                        ->build()
                )
            )
            ->build();
    }

    public function buildPostedToTeamsQuery(User $user, SelectBuilder $baseBuilder): ?Select
    {
        if ($user->getTeamIdList() === []) {
            return null;
        }

        return (clone $baseBuilder)
            ->where([
                'parentId' => null,
                'type' => Note::TYPE_POST,
                'targetType' => Note::TARGET_TEAMS,
                'createdById!=' => $user->getId(),
                'isGlobal' => false,
            ])
            ->where(
                Cond::in(
                    Expr::column('id'),
                    SelectBuilder::create()
                        ->select('noteId')
                        ->from('NoteTeam')
                        ->where(['teamId' => $user->getTeamIdList()])
                        ->build()
                )
            )
            ->build();
    }

    public function buildPostedByUserQuery(User $user, SelectBuilder $baseBuilder): Select
    {
        return (clone $baseBuilder)
            ->where([
                'parentId' => null,
                'type' => Note::TYPE_POST,
                'createdById' => $user->getId(),
            ])
            ->build();
    }

    public function buildPostedToGlobalQuery(User $user, SelectBuilder $baseBuilder): ?Select
    {
        if ($user->isPortal() || $user->isApi()) {
            return null;
        }

        return (clone $baseBuilder)
            ->where([
                'type' => Note::TYPE_POST,
                'targetType' => Note::TARGET_ALL,
                'parentId' => null,
                'createdById!=' => $user->getId(),
                'isGlobal' => true,
            ])
            ->build();
    }
}
