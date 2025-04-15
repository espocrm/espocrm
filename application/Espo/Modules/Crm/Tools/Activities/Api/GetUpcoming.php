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

namespace Espo\Modules\Crm\Tools\Activities\Api;

use Espo\Core\Api\Action;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Api\ResponseComposer;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Record\SearchParamsFetcher;
use Espo\Entities\User;
use Espo\Modules\Crm\Tools\Activities\Upcoming\Params;
use Espo\Modules\Crm\Tools\Activities\UpcomingService;

/**
 * Upcoming activities.
 *
 * @noinspection PhpUnused
 */
class GetUpcoming implements Action
{
    public function __construct(
        private User $user,
        private SearchParamsFetcher $searchParamsFetcher,
        private UpcomingService $service
    ) {}

    public function process(Request $request): Response
    {
        $userId = $request->getQueryParam('userId') ?? $this->user->getId();

        $params = $this->fetchParams($request);

        $result = $this->service->get($userId, $params);

        return ResponseComposer::json($result->toApiOutput());
    }

    /**
     * @throws BadRequest
     * @throws Forbidden
     */
    private function fetchParams(Request $request): Params
    {
        $entityTypeList = $this->fetchEntityTypeList($request);
        $futureDays = $request->hasQueryParam('futureDays') ? intval($request->getQueryParam('futureDays')) : null;
        $searchParams = $this->searchParamsFetcher->fetch($request);

        return new Params(
            offset: $searchParams->getOffset(),
            maxSize: $searchParams->getMaxSize(),
            futureDays: $futureDays,
            entityTypeList: $entityTypeList,
            includeShared: $request->getQueryParam('includeShared') === 'true',
        );
    }

    /**
     * @return ?string[]
     * @throws BadRequest
     */
    private function fetchEntityTypeList(Request $request): ?array
    {
        $entityTypeList = $request->getQueryParams()['entityTypeList'] ?? null;

        if (!is_array($entityTypeList) && $entityTypeList !== null) {
            throw new BadRequest("Bad entityTypeList.");
        }

        foreach ($entityTypeList ?? [] as $it) {
            if (!is_string($it)) {
                throw new BadRequest("Bad item in entityTypeList.");
            }
        }

        return $entityTypeList;
    }
}
