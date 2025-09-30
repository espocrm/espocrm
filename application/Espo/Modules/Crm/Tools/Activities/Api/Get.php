<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

use Espo\Core\Acl;
use Espo\Core\Api\Action;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Api\ResponseComposer;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Record\SearchParamsFetcher;
use Espo\Modules\Crm\Tools\Activities\FetchParams as ActivitiesFetchParams;
use Espo\Modules\Crm\Tools\Activities\Service as Service;

/**
 * Activities related to a record.
 */
class Get implements Action
{
    public function __construct(
        private SearchParamsFetcher $searchParamsFetcher,
        private Service $service,
        private Acl $acl
    ) {}

    public function process(Request $request): Response
    {
        if (!$this->acl->check('Activities')) {
            throw new Forbidden();
        }

        $parentType = $request->getRouteParam('parentType');
        $id = $request->getRouteParam('id');
        $type = $request->getRouteParam('type');

        if (
            !$parentType ||
            !$id ||
            !in_array($type, ['activities', 'history'])
        ) {
            throw new BadRequest();
        }

        $searchParams = $this->searchParamsFetcher->fetch($request);

        $offset = $searchParams->getOffset();
        $maxSize = $searchParams->getMaxSize();

        $targetEntityType = $request->getQueryParam('entityType');

        $fetchParams = new ActivitiesFetchParams($maxSize, $offset, $targetEntityType);

        $result = $type === 'history' ?
            $this->service->getHistory($parentType, $id, $fetchParams) :
            $this->service->getActivities($parentType, $id, $fetchParams);

        return ResponseComposer::json($result->toApiOutput());
    }
}
