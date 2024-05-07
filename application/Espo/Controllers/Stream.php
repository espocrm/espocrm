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

namespace Espo\Controllers;

use Espo\Core\Exceptions\BadRequest;

use Espo\Core\Api\Request;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Record\SearchParamsFetcher;

use Espo\Core\Select\SearchParams;
use Espo\Core\Select\Where\Item as WhereItem;
use Espo\Entities\User as UserEntity;
use Espo\Tools\Stream\RecordService;

use Espo\Tools\Stream\UserRecordService;
use stdClass;

class Stream
{
    public static string $defaultAction = 'list';

    public function __construct(
        private RecordService $service,
        private UserRecordService $userRecordService,
        private SearchParamsFetcher $searchParamsFetcher
    ) {}

    /**
     * @throws BadRequest
     * @throws Forbidden
     * @throws NotFound
     */
    public function getActionList(Request $request): stdClass
    {
        $id = $request->getRouteParam('id');
        $scope = $request->getRouteParam('scope');

        if ($scope === null) {
            throw new BadRequest();
        }

        $searchParams = $this->fetchSearchParams($request);

        if ($scope === UserEntity::ENTITY_TYPE) {
            $collection = $this->userRecordService->find($id, $searchParams);

            return (object) [
                'total' => $collection->getTotal(),
                'list' => $collection->getValueMapList(),
            ];
        }

        if ($id === null) {
            throw new BadRequest();
        }

        $collection = $this->service->find($scope, $id, $searchParams);
        $pinnedCollection = $this->service->getPinned($scope, $id);

        return (object) [
            'total' => $collection->getTotal(),
            'list' => $collection->getValueMapList(),
            'pinnedList' => $pinnedCollection->getValueMapList(),
        ];
    }

    /**
     * @throws BadRequest
     * @throws Forbidden
     * @throws NotFound
     */
    public function getActionListPosts(Request $request): stdClass
    {
        $id = $request->getRouteParam('id');
        $scope = $request->getRouteParam('scope');

        if ($scope === null) {
            throw new BadRequest();
        }

        if ($id === null && $scope !== UserEntity::ENTITY_TYPE) {
            throw new BadRequest("No ID.");
        }

        $searchParams = $this->fetchSearchParams($request)
            ->withPrimaryFilter('posts');

        $result = $scope === UserEntity::ENTITY_TYPE ?
            $this->userRecordService->find($id, $searchParams) :
            $this->service->find($scope, $id ?? '', $searchParams);

        return (object) [
            'total' => $result->getTotal(),
            'list' => $result->getValueMapList(),
        ];
    }

    /**
     * @throws BadRequest
     * @throws Forbidden
     * @throws NotFound
     */
    public function getActionListUpdates(Request $request): stdClass
    {
        $id = $request->getRouteParam('id');
        $scope = $request->getRouteParam('scope');

        if ($scope === null || $id === null) {
            throw new BadRequest();
        }

        $searchParams = $this->fetchSearchParams($request);

        $result = $this->service->findUpdates($scope, $id, $searchParams);

        return (object) [
            'total' => $result->getTotal(),
            'list' => $result->getValueMapList(),
        ];
    }

    /**
     * @throws BadRequest
     * @throws Forbidden
     */
    private function fetchSearchParams(Request $request): SearchParams
    {
        $searchParams = $this->searchParamsFetcher->fetch($request);

        $after = $request->getQueryParam('after');
        $filter = $request->getQueryParam('filter');

        if ($after) {
            $searchParams = $searchParams
                ->withWhereAdded(
                    WhereItem
                        ::createBuilder()
                        ->setAttribute('createdAt')
                        ->setType(WhereItem\Type::AFTER)
                        ->setValue($after)
                        ->build()
                );
        }

        if ($filter) {
            $searchParams = $searchParams->withPrimaryFilter($filter);
        }

        if ($request->getQueryParam('skipOwn') === 'true') {
            $searchParams = $searchParams->withBoolFilterAdded('skipOwn');
        }

        $beforeNumber = $request->getQueryParam('beforeNumber');

        if ($beforeNumber) {
            $searchParams = $searchParams
                ->withWhereAdded(
                    WhereItem
                        ::createBuilder()
                        ->setAttribute('number')
                        ->setType(WhereItem\Type::LESS_THAN)
                        ->setValue($beforeNumber)
                        ->build()
                );
        }

        return $searchParams;
    }
}
