<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

namespace Espo\Custom\Controllers;

use Espo\Core\Acl;
use Espo\Core\Acl\Table;
use Espo\Core\Api\Request;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Custom\Services\ActivitySummaryService;

/**
 * Controller exposing aggregated activity summary for dashlets and charts.
 */
class ActivitySummary
{
    public static string $defaultAction = 'summary';

    public function __construct(
        private ActivitySummaryService $service,
        private Acl $acl
    ) {}

    /**
     * Get aggregated activity summary per account for the specified days (default 30).
     *
     * @return array<string, mixed>
     * @throws Forbidden
     * @throws BadRequest
     */
    public function getActionSummary(Request $request): array
    {
        if (!$this->acl->checkScope('Account', Table::ACTION_READ)) {
            throw new Forbidden("Access to Account records is forbidden.");
        }

        $daysParam = $request->getQueryParam('days');
        $days = $daysParam !== null ? (int) $daysParam : ActivitySummaryService::DEFAULT_DAYS;

        if ($days <= 0) {
            throw new BadRequest("Parameter 'days' must be a positive integer.");
        }

        return $this->service->getActivitySummary($days);
    }

    /**
     * Fallback alias for non-method-prefixed action routing.
     *
     * @return array<string, mixed>
     * @throws Forbidden
     * @throws BadRequest
     */
    public function actionSummary(Request $request): array
    {
        return $this->getActionSummary($request);
    }
}
