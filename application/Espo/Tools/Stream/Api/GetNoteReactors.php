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

namespace Espo\Tools\Stream\Api;

use Espo\Core\Api\Action;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Api\ResponseComposer;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Record\EntityProvider;
use Espo\Core\Record\SearchParamsFetcher;
use Espo\Core\Select\SelectBuilderFactory;
use Espo\Entities\Note;
use Espo\Entities\User;
use Espo\Entities\UserReaction;
use Espo\ORM\EntityManager;
use Espo\ORM\Query\Part\Condition;
use Espo\ORM\Query\Part\Expression;
use Espo\ORM\Query\SelectBuilder;

/**
 * @noinspection PhpUnused
 */
class GetNoteReactors implements Action
{
    public function __construct(
        private EntityProvider $entityProvider,
        private SearchParamsFetcher $searchParamsFetcher,
        private SelectBuilderFactory $selectBuilderFactory,
        private EntityManager $entityManager,
    ) {}

    public function process(Request $request): Response
    {
        $id = $request->getRouteParam('id') ?? throw new BadRequest();
        $type = $request->getRouteParam('type') ?? throw new BadRequest();

        $note = $this->entityProvider->getByClass(Note::class, $id);
        $searchParams = $this->searchParamsFetcher->fetch($request);

        $query = $this->selectBuilderFactory
            ->create()
            ->from(User::ENTITY_TYPE)
            ->withSearchParams($searchParams)
            ->withStrictAccessControl()
            ->withDefaultOrder()
            ->buildQueryBuilder()
            ->select([
                'id',
                'name',
                'userName',
            ])
            ->where(
                Condition::in(
                    Expression::column('id'),
                    SelectBuilder::create()
                        ->from(UserReaction::ENTITY_TYPE)
                        ->select('userId')
                        ->where([
                            'type' => $type,
                            'parentId' => $note->getId(),
                            'parentType' => $note->getEntityType(),
                        ])
                        ->build()
                )
            )
            ->build();

        $repository = $this->entityManager->getRDBRepositoryByClass(User::class);

        $users = $repository->clone($query)->find();
        $count = $repository->clone($query)->count();

        return ResponseComposer::json([
            'list' => $users->getValueMapList(),
            'total' => $count,
        ]);
    }
}
