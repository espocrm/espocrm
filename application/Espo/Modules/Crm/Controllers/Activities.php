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

namespace Espo\Modules\Crm\Controllers;

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\BadRequest;

use Espo\Core\{
    Api\Request,
    Controllers\Base,
    Utils\ControllerUtil,
};

class Activities extends Base
{
    protected $maxCalendarRange = 123;

    const MAX_SIZE_LIMIT = 200;

    public function getActionListCalendarEvents(Request $request)
    {
        if (!$this->acl->check('Calendar')) {
            throw new Forbidden();
        }

        $from = $request->getQueryParam('from');
        $to = $request->getQueryParam('to');

        if (empty($from) || empty($to)) {
            throw new BadRequest();
        }

        if (strtotime($to) - strtotime($from) > $this->maxCalendarRange * 24 * 3600) {
            throw new Forbidden('Too long range.');
        }

        $service = $this->getService('Activities');

        $scopeList = null;

        if ($request->getQueryParam('scopeList') !== null) {
            $scopeList = explode(',', $request->getQueryParam('scopeList'));
        }

        $userId = $request->getQueryParam('userId');
        $userIdList = $request->getQueryParam('userIdList');
        $teamIdList = $request->getQueryParam('teamIdList');

        if ($teamIdList) {
            $teamIdList = explode(',', $teamIdList);

            return $userResultList = $service->getTeamsEventList($teamIdList, $from, $to, $scopeList);
        }

        if ($userIdList) {
            $userIdList = explode(',', $userIdList);

            return $service->getUsersEventList($userIdList, $from, $to, $scopeList);
        }
        else {
            if (!$userId) {
                $userId = $this->getUser()->id;
            }
        }

        return $service->getEventList($userId, $from, $to, $scopeList);
    }

    public function getActionGetTimeline(Request $request)
    {
        if (!$this->acl->check('Calendar')) {
            throw new Forbidden();
        }

        $from = $request->getQueryParam('from');
        $to = $request->getQueryParam('to');

        if (empty($from) || empty($to)) {
            throw new BadRequest();
        }

        if (strtotime($to) - strtotime($from) > $this->maxCalendarRange * 24 * 3600) {
            throw new Forbidden('Too long range.');
        }

        $service = $this->getService('Activities');

        $scopeList = null;

        if ($request->getQueryParam('scopeList') !== null) {
            $scopeList = explode(',', $request->getQueryParam('scopeList'));
        }

        $userId = $request->getQueryParam('userId');
        $userIdList = $request->getQueryParam('userIdList');

        if ($userIdList) {
            $userIdList = explode(',', $userIdList);
        }
        else {
            $userIdList = [];
        }

        if ($userId) {
            $userIdList[] = $userId;
        }

        return $service->getUsersTimeline($userIdList, $from, $to, $scopeList);
    }

    public function getActionListUpcoming(Request $request)
    {
        $service = $this->getService('Activities');

        $userId = $request->getQueryParam('userId');

        if (!$userId) {
            $userId = $this->getUser()->id;
        }

        $offset = intval($request->getQueryParam('offset'));
        $maxSize = intval($request->getQueryParam('maxSize'));

        $entityTypeList = $request->getQueryParam('entityTypeList');

        $futureDays = intval($request->getQueryParam('futureDays'));

        $maxSizeLimit = $this->config->get('recordListMaxSizeLimit', self::MAX_SIZE_LIMIT);

        if (empty($maxSize)) {
            $maxSize = $maxSizeLimit;
        }

        if (!empty($maxSize) && $maxSize > $maxSizeLimit) {
            throw new Forbidden("Max should should not exceed " . $maxSizeLimit . ". Use offset and limit.");
        }

        return $service->getUpcomingActivities(
            $userId,
            [
                'offset' => $offset,
                'maxSize' => $maxSize
            ],
            $entityTypeList,
            $futureDays
        );
    }

    public function getActionPopupNotifications()
    {
        $userId = $this->user->id;

        return $this->getService('Activities')->getPopupNotifications($userId);
    }

    public function postActionRemovePopupNotification(Request $request)
    {
        $data = $request->getParsedBody();

        if (empty($data->id)) {
            throw new BadRequest();
        }

        $id = $data->id;

        return $this->getService('Activities')->removeReminder($id);
    }

    public function getActionList(Request $request)
    {
        $params = $request->getRouteParams();

        if (!$this->acl->check('Activities')) {
            throw new Forbidden();
        }

        $name = $params['name'];

        if (!in_array($name, ['activities', 'history'])) {
            throw new BadRequest();
        }

        if (empty($params['scope'])) {
            throw new BadRequest();
        }

        if (empty($params['id'])) {
            throw new BadRequest();
        }

        $entityType = $params['scope'];
        $id = $params['id'];

        $offset = intval($request->getQueryParam('offset'));
        $maxSize = intval($request->getQueryParam('maxSize'));
        $order = $request->getQueryParam('order');
        $orderBy = $request->getQueryParam('orderBy');
        $where = $request->getQueryParam('where');

        $maxSizeLimit = $this->config->get('recordListMaxSizeLimit', self::MAX_SIZE_LIMIT);

        if (empty($maxSize)) {
            $maxSize = $maxSizeLimit;
        }

        if (!empty($maxSize) && $maxSize > $maxSizeLimit) {
            throw new Forbidden("Max should should not exceed " . $maxSizeLimit . ". Use offset and limit.");
        }

        $scope = null;

        if (is_array($where) && !empty($where[0]) && $where[0] !== 'false') {
            $scope = $where[0];
        }

        $service = $this->getService('Activities');

        $methodName = 'get' . ucfirst($name);

        return $service->$methodName($entityType, $id, [
            'scope' => $scope,
            'offset' => $offset,
            'maxSize' => $maxSize,
            'order' => $order,
            'orderBy' => $orderBy,
        ]);
    }

    public function getActionEntityTypeList(Request $request)
    {
        $params = $request->getRouteParams();

        if (empty($params['scope'])) {
            throw new BadRequest();
        }

        if (empty($params['id'])) {
            throw new BadRequest();
        }

        if (empty($params['name'])) {
            throw new BadRequest();
        }

        if (empty($params['entityType'])) {
            throw new BadRequest();
        }

        $scope = $params['scope'];
        $id = $params['id'];
        $name = $params['name'];
        $entityType = $params['entityType'];

        if ($name === 'activities') {
            $isHistory = false;
        }
        else  if ($name === 'history') {
            $isHistory = true;
        }
        else {
            throw new BadRequest();
        }

        $searchParams = ControllerUtil::fetchSearchParamsFromRequest($request);

        $maxSizeLimit = $this->config->get('recordListMaxSizeLimit', 200);

        if (empty($searchParams['maxSize'])) {
            $searchParams['maxSize'] = $maxSizeLimit;
        }

        if (!empty($searchParams['maxSize']) && $searchParams['maxSize'] > $maxSizeLimit) {
            throw new Forbidden(
                "Max size should should not exceed " . $maxSizeLimit . ". Use offset and limit."
            );
        }

        $service = $this->getService('Activities');

        $result = $service->findActivitiyEntityType($scope, $id, $entityType, $isHistory, $searchParams);

        return (object) [
            'total' => $result->getTotal(),
            'list' => $result->getValueMapList(),
        ];
    }

    public function getActionBusyRanges(Request $request)
    {
        $from = $request->getQueryParam('from');
        $to = $request->getQueryParam('to');
        $userIdListString = $request->getQueryParam('userIdList');

        if (!$from || !$to || !$userIdListString) {
            throw new BadRequest();
        }

        $userIdList = explode(',', $userIdListString);

        return $this->getService('Activities')->getBusyRanges(
            $userIdList,
            $from,
            $to,
            $request->getQueryParam('entityType'),
            $request->getQueryParam('entityId')
        );
    }
}
