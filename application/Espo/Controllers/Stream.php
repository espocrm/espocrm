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

use Espo\Core\Exceptions\Error;

use Espo\Core\ServiceFactory;
use Espo\Core\Utils\Config;

class Stream
{
    const MAX_SIZE_LIMIT = 200;

    public static $defaultAction = 'list';

    protected $serviceFactory;
    protected $config;

    public function __construct(ServiceFactory $serviceFactory, Config $config)
    {
        $this->serviceFactory = $serviceFactory;
        $this->config = $config;
    }

    public function actionList($params, $data, $request)
    {
        $scope = $params['scope'];
        $id = isset($params['id']) ? $params['id'] : null;

        $offset = intval($request->get('offset'));
        $maxSize = intval($request->get('maxSize'));
        $after = $request->get('after');
        $filter = $request->get('filter');
        $skipOwn = $request->get('skipOwn') === 'true';

        $maxSizeLimit = $this->config->get('recordListMaxSizeLimit', self::MAX_SIZE_LIMIT);
        if (empty($maxSize)) {
            $maxSize = $maxSizeLimit;
        }
        if (!empty($maxSize) && $maxSize > $maxSizeLimit) {
            throw new Forbidden("Max size should should not exceed " . $maxSizeLimit . ". Use offset and limit.");
        }

        $result = $this->serviceFactory->create('Stream')->find($scope, $id, [
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

    public function getActionListPosts($params, $data, $request)
    {
        $scope = $params['scope'];
        $id = isset($params['id']) ? $params['id'] : null;

        $offset = intval($request->get('offset'));
        $maxSize = intval($request->get('maxSize'));
        $after = $request->get('after');

        $where = $request->get('where');

        $maxSizeLimit = $this->config->get('recordListMaxSizeLimit', self::MAX_SIZE_LIMIT);
        if (empty($maxSize)) {
            $maxSize = $maxSizeLimit;
        }
        if (!empty($maxSize) && $maxSize > $maxSizeLimit) {
            throw new Forbidden("Max size should should not exceed " . $maxSizeLimit . ". Use offset and limit.");
        }

        $result = $this->serviceFactory->create('Stream')->find($scope, $id, [
            'offset' => $offset,
            'maxSize' => $maxSize,
            'after' => $after,
            'filter' => 'posts',
            'where' => $where,
        ]);

        return (object) [
            'total' => $result->total,
            'list' => $result->collection->getValueMapList()
        ];
    }
}
