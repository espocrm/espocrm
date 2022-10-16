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

namespace Espo\Controllers;

use Espo\Core\Exceptions\BadRequest;

use Espo\Core\Api\Request;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Field\DateTime;
use Espo\Core\Record\SearchParamsFetcher;

use Espo\Entities\User as UserEntity;
use Espo\Tools\Stream\FindParams;
use Espo\Tools\Stream\RecordService;

use stdClass;

class Stream
{
    public static string $defaultAction = 'list';

    private RecordService $service;
    private SearchParamsFetcher $searchParamsFetcher;

    public function __construct(
        RecordService $service,
        SearchParamsFetcher $searchParamsFetcher
    ) {
        $this->service = $service;
        $this->searchParamsFetcher = $searchParamsFetcher;
    }

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

        if ($id === null && $scope !== UserEntity::ENTITY_TYPE) {
            throw new BadRequest("No ID.");
        }

        $searchParams = $this->searchParamsFetcher->fetch($request);

        $after = $request->getQueryParam('after');
        $filter = $request->getQueryParam('filter');
        $skipOwn = $request->getQueryParam('skipOwn') === 'true';

        /** @todo Use named params. */
        $findParams = new FindParams(
            $searchParams,
            $skipOwn,
            $after ?
                DateTime::fromString($after) : null,
            $filter
        );

        $result = $scope === UserEntity::ENTITY_TYPE ?
            $this->service->findUser($id, $findParams) :
            $this->service->find($scope, $id ?? '', $findParams);

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

        $after = $request->getQueryParam('after');

        $searchParams = $this->searchParamsFetcher->fetch($request);

        /** @todo Use named params. */
        $findParams = new FindParams(
            $searchParams,
            false,
            $after ?
                DateTime::fromString($after) : null,
            FindParams::FILTER_POSTS
        );

        $result = $scope === UserEntity::ENTITY_TYPE ?
            $this->service->findUser($id, $findParams) :
            $this->service->find($scope, $id ?? '', $findParams);

        return (object) [
            'total' => $result->getTotal(),
            'list' => $result->getValueMapList(),
        ];
    }
}
