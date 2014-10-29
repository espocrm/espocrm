<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014  Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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
 ************************************************************************/
namespace Espo\Controllers;

use Espo\Core\Controllers\Base;
use Espo\ORM\EntityCollection;
use Slim\Http\Request;

class Notification extends
    Base
{

    public static $defaultAction = 'list';

    /**
     * @param         $params
     * @param         $data
     * @param Request $request
     *
     * @return array
     * @since 1.0
     */
    public function actionList($params, $data, $request)
    {
        /**
         * @var \Espo\Services\Notification $notificationService
         * @var EntityCollection            $collection
         */
        $scope = $params['scope'];
        $id = $params['id'];
        $userId = $this->getUser()->id;
        $offset = intval($request->get('offset'));
        $maxSize = intval($request->get('maxSize'));
        $params = array(
            'offset' => $offset,
            'maxSize' => $maxSize,
        );
        $notificationService = $this->getService('Notification');
        $result = $notificationService->getList($userId, $params);
        $collection = $result['collection'];
        return array(
            'total' => $result['total'],
            'list' => $collection->toArray()
        );
    }

    public function actionNotReadCount()
    {
        /**
         * @var \Espo\Services\Notification $notificationService
         */
        $userId = $this->getUser()->id;
        $notificationService = $this->getService('Notification');
        return $notificationService->getNotReadCount($userId);
    }

    public function actionMarkAllRead($params, $data, $request)
    {
        /**
         * @var \Espo\Services\Notification $notificationService
         */
        $userId = $this->getUser()->id;
        $notificationService = $this->getService('Notification');
        return $notificationService->markAllRead($userId);
    }
}

