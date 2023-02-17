<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2023 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Api\Request;
use Espo\Core\Acl;
use Espo\Core\Record\SearchParamsFetcher;
use Espo\Modules\Crm\Tools\Activities\FetchParams as ActivitiesFetchParams;
use Espo\Modules\Crm\Tools\Activities\Service as Service;
use Espo\Entities\User;

use stdClass;

class Activities
{
    public function __construct(
        private User $user,
        private Acl $acl,
        private SearchParamsFetcher $searchParamsFetcher,
        private Service $service
    ) {}

    /**
     * @throws Forbidden
     * @throws NotFound
     * @throws BadRequest
     */
    public function getActionListUpcoming(Request $request): stdClass
    {
        $userId = $request->getQueryParam('userId');

        if (!$userId) {
            $userId = $this->user->getId();
        }

        $searchParams = $this->searchParamsFetcher->fetch($request);

        $offset = $searchParams->getOffset();
        $maxSize = $searchParams->getMaxSize();

        $entityTypeList = (array) ($request->getQueryParams()['entityTypeList'] ?? null);

        $futureDays = intval($request->getQueryParam('futureDays'));

        $recordCollection = $this->service->getUpcomingActivities(
            $userId,
            [
                'offset' => $offset,
                'maxSize' => $maxSize,
            ],
            $entityTypeList,
            $futureDays
        );

        return (object) [
            'total' => $recordCollection->getTotal(),
            'list' => $recordCollection->getValueMapList(),
        ];
    }

    /**
     * @throws BadRequest
     */
    public function postActionRemovePopupNotification(Request $request): bool
    {
        $data = $request->getParsedBody();

        if (empty($data->id)) {
            throw new BadRequest();
        }

        $id = $data->id;

        $this->service->removeReminder($id);

        return true;
    }

    /**
     * @throws BadRequest
     * @throws Error
     * @throws Forbidden
     * @throws NotFound
     */
    public function getActionList(Request $request): stdClass
    {
        $params = $request->getRouteParams();

        if (!$this->acl->check('Activities')) {
            throw new Forbidden();
        }

        $name = $params['name'] ?? null;

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

        $searchParams = $this->searchParamsFetcher->fetch($request);

        $offset = $searchParams->getOffset();
        $maxSize = $searchParams->getMaxSize();

        $targetEntityType = $request->getQueryParam('entityType');

        $fetchParams = new ActivitiesFetchParams(
            $maxSize,
            $offset,
            $targetEntityType
        );

        $recordCollection = $name === 'history' ?
            $this->service->getHistory($entityType, $id, $fetchParams) :
            $this->service->getActivities($entityType, $id, $fetchParams);

        return (object) [
            'total' => $recordCollection->getTotal(),
            'list' => $recordCollection->getValueMapList(),
        ];
    }

    /**
     * @throws BadRequest
     * @throws Error
     * @throws Forbidden
     * @throws NotFound
     */
    public function getActionEntityTypeList(Request $request): stdClass
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

        $searchParams = $this->searchParamsFetcher->fetch($request);

        $result = $this->service->findActivitiesEntityType(
            $scope,
            $id,
            $entityType,
            $isHistory,
            $searchParams
        );

        return (object) [
            'total' => $result->getTotal(),
            'list' => $result->getValueMapList(),
        ];
    }
}
