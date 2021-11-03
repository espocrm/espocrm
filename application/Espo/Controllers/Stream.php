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

namespace Espo\Controllers;

use Espo\Core\{
    Api\Request,
    Record\SearchParamsFetcher,
};

use Espo\Services\Stream as Service;

use StdClass;

class Stream
{
    public static $defaultAction = 'list';

    private $service;

    private $searchParamsFetcher;

    public function __construct(
        Service $service,
        SearchParamsFetcher $searchParamsFetcher
    ) {
        $this->service = $service;
        $this->searchParamsFetcher = $searchParamsFetcher;
    }

    public function getActionList(Request $request): StdClass
    {
        $params = $request->getRouteParams();

        $scope = $params['scope'];
        $id = isset($params['id']) ? $params['id'] : null;

        $searchParams = $this->searchParamsFetcher->fetch($request);

        $offset = $searchParams->getOffset();
        $maxSize = $searchParams->getMaxSize();

        $after = $request->getQueryParam('after');
        $filter = $request->getQueryParam('filter');
        $skipOwn = $request->getQueryParam('skipOwn') === 'true';

        $result = $this->service->find($scope, $id, [
            'offset' => $offset,
            'maxSize' => $maxSize,
            'after' => $after,
            'filter' => $filter,
            'skipOwn' => $skipOwn,
        ]);

        return (object) [
            'total' => $result->total,
            'list' => $result->collection->getValueMapList()
        ];
    }

    public function getActionListPosts(Request $request): StdClass
    {
        $params = $request->getRouteParams();

        $scope = $params['scope'];
        $id = isset($params['id']) ? $params['id'] : null;

        $searchParams = $this->searchParamsFetcher->fetch($request);

        $offset = $searchParams->getOffset();
        $maxSize = $searchParams->getMaxSize();

        $after = $request->getQueryParam('after');
        $where = $request->getQueryParam('where');

        $result = $this->service->find($scope, $id, [
            'offset' => $offset,
            'maxSize' => $maxSize,
            'after' => $after,
            'filter' => 'posts',
            'where' => $where,
        ]);

        return (object) [
            'total' => $result->total,
            'list' => $result->collection->getValueMapList(),
        ];
    }
}
