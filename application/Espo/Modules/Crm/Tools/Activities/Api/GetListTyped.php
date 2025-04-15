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
use Espo\Core\Record\SearchParamsFetcher;
use Espo\Modules\Crm\Tools\Activities\Service as Service;

/**
 * Activities of specific entity type related to a record.
 */
class GetListTyped implements Action
{
    public function __construct(
        private SearchParamsFetcher $searchParamsFetcher,
        private Service $service
    ) {}

    public function process(Request $request): Response
    {
        $parentType = $request->getRouteParam('parentType');
        $id = $request->getRouteParam('id');
        $type = $request->getRouteParam('type');
        $targetType = $request->getRouteParam('targetType');

        if (
            !$parentType ||
            !$id ||
            !$type ||
            !$targetType
        ) {
            throw new BadRequest();
        }

        if ($type === 'activities') {
            $isHistory = false;
        } else if ($type === 'history') {
            $isHistory = true;
        } else {
            throw new BadRequest("Bad type.");
        }

        $searchParams = $this->searchParamsFetcher->fetch($request);

        $result = $this->service->findActivitiesEntityType(
            $parentType,
            $id,
            $targetType,
            $isHistory,
            $searchParams
        );

        return ResponseComposer::json($result->toApiOutput());
    }
}
