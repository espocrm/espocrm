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

namespace Espo\Modules\Crm\Controllers;

use Espo\Core\Acl\Table;
use Espo\Core\Controllers\Record;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Api\Request;
use Espo\Core\Field\Date;
use Espo\Modules\Crm\Entities\Opportunity as OpportunityEntity;
use Espo\Modules\Crm\Tools\Opportunity\Report\ByLeadSource;
use Espo\Modules\Crm\Tools\Opportunity\Report\ByStage;
use Espo\Modules\Crm\Tools\Opportunity\Report\DateRange;
use Espo\Modules\Crm\Tools\Opportunity\Report\SalesByMonth;
use Espo\Modules\Crm\Tools\Opportunity\Report\SalesPipeline;
use Espo\Modules\Crm\Tools\Opportunity\Service;
use stdClass;

class Opportunity extends Record
{
    /**
     * @throws BadRequest
     * @throws Forbidden
     */
    public function getActionReportByLeadSource(Request $request): stdClass
    {
        if (!$this->acl->checkScope(OpportunityEntity::ENTITY_TYPE)) {
            throw new Forbidden();
        }

        $dateFrom = $request->getQueryParam('dateFrom');
        $dateTo = $request->getQueryParam('dateTo');
        $dateFilter = $request->getQueryParam('dateFilter');

        if (!$dateFilter) {
            throw new BadRequest("No `dateFilter` parameter.");
        }

        $range = new DateRange(
            $dateFilter,
            $dateFrom ? Date::fromString($dateFrom) : null,
            $dateTo ? Date::fromString($dateTo) : null
        );

        return $this->injectableFactory
            ->create(ByLeadSource::class)
            ->run($range);
    }

    /**
     * @throws BadRequest
     * @throws Forbidden
     */
    public function getActionReportByStage(Request $request): stdClass
    {
        if (!$this->acl->checkScope(OpportunityEntity::ENTITY_TYPE)) {
            throw new Forbidden();
        }

        $dateFrom = $request->getQueryParam('dateFrom');
        $dateTo = $request->getQueryParam('dateTo');
        $dateFilter = $request->getQueryParam('dateFilter');

        if (!$dateFilter) {
            throw new BadRequest("No `dateFilter` parameter.");
        }

        $range = new DateRange(
            $dateFilter,
            $dateFrom ? Date::fromString($dateFrom) : null,
            $dateTo ? Date::fromString($dateTo) : null
        );

        return $this->injectableFactory
            ->create(ByStage::class)
            ->run($range);
    }

    /**
     * @throws BadRequest
     * @throws Forbidden
     */
    public function getActionReportSalesByMonth(Request $request): stdClass
    {
        if (!$this->acl->checkScope(OpportunityEntity::ENTITY_TYPE)) {
            throw new Forbidden();
        }

        $dateFrom = $request->getQueryParam('dateFrom');
        $dateTo = $request->getQueryParam('dateTo');
        $dateFilter = $request->getQueryParam('dateFilter');

        if (!$dateFilter) {
            throw new BadRequest("No `dateFilter` parameter.");
        }

        $range = new DateRange(
            $dateFilter,
            $dateFrom ? Date::fromString($dateFrom) : null,
            $dateTo ? Date::fromString($dateTo) : null
        );

        return $this->injectableFactory
            ->create(SalesByMonth::class)
            ->run($range);
    }

    /**
     * @throws BadRequest
     * @throws Forbidden
     */
    public function getActionReportSalesPipeline(Request $request): stdClass
    {
        if (!$this->acl->checkScope(OpportunityEntity::ENTITY_TYPE)) {
            throw new Forbidden();
        }

        $dateFrom = $request->getQueryParam('dateFrom');
        $dateTo = $request->getQueryParam('dateTo');
        $dateFilter = $request->getQueryParam('dateFilter');
        $useLastStage = $request->getQueryParam('useLastStage') === 'true';
        $teamId = $request->getQueryParam('teamId') ?? null;

        if (!$dateFilter) {
            throw new BadRequest("No `dateFilter` parameter.");
        }

        $range = new DateRange(
            $dateFilter,
            $dateFrom ? Date::fromString($dateFrom) : null,
            $dateTo ? Date::fromString($dateTo) : null
        );

        return $this->injectableFactory
            ->create(SalesPipeline::class)
            ->run($range, $useLastStage, $teamId);
    }

    /**
     * @return stdClass[]
     * @throws Forbidden
     * @throws BadRequest
     */
    public function getActionEmailAddressList(Request $request): array
    {
        $id = $request->getQueryParam('id');

        if (!$id) {
            throw new BadRequest();
        }

        if (!$this->acl->checkScope(OpportunityEntity::ENTITY_TYPE, Table::ACTION_READ)) {
            throw new Forbidden();
        }

        $result = $this->injectableFactory
            ->create(Service::class)
            ->getEmailAddressList($id);

        return array_map(
            fn ($item) => $item->getValueMap(),
            $result
        );
    }
}
