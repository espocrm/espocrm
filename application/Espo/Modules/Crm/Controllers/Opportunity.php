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

use Espo\Core\Api\Request;

use Espo\Modules\Crm\Services\Opportunity as Service;

class Opportunity extends \Espo\Core\Controllers\Record
{
    public function getActionReportByLeadSource(Request $request)
    {
        $level = $this->getAcl()->getLevel('Opportunity', 'read');

        if (!$level || $level == 'no') {
            throw new Forbidden();
        }

        $dateFrom = $request->getQueryParam('dateFrom');
        $dateTo = $request->getQueryParam('dateTo');
        $dateFilter = $request->getQueryParam('dateFilter');

        return $this->getOpportunityService()->reportByLeadSource($dateFilter, $dateFrom, $dateTo);
    }

    public function getActionReportByStage(Request $request)
    {
        $level = $this->getAcl()->getLevel('Opportunity', 'read');

        if (!$level || $level == 'no') {
            throw new Forbidden();
        }

        $dateFrom = $request->getQueryParam('dateFrom');
        $dateTo = $request->getQueryParam('dateTo');
        $dateFilter = $request->getQueryParam('dateFilter');

        return $this->getOpportunityService()->reportByStage($dateFilter, $dateFrom, $dateTo);
    }

    public function getActionReportSalesByMonth(Request $request)
    {
        $level = $this->getAcl()->getLevel('Opportunity', 'read');

        if (!$level || $level == 'no') {
            throw new Forbidden();
        }

        $dateFrom = $request->getQueryParam('dateFrom');
        $dateTo = $request->getQueryParam('dateTo');
        $dateFilter = $request->getQueryParam('dateFilter');

        return $this->getOpportunityService()->reportSalesByMonth($dateFilter, $dateFrom, $dateTo);
    }

    public function getActionReportSalesPipeline(Request $request)
    {
        $level = $this->getAcl()->getLevel('Opportunity', 'read');

        if (!$level || $level == 'no') {
            throw new Forbidden();
        }

        $dateFrom = $request->getQueryParam('dateFrom');
        $dateTo = $request->getQueryParam('dateTo');
        $dateFilter = $request->getQueryParam('dateFilter');
        $useLastStage = $request->getQueryParam('useLastStage') === 'true';
        $teamId = $request->getQueryParam('teamId') ?? null;

        return $this->getOpportunityService()
            ->reportSalesPipeline($dateFilter, $dateFrom, $dateTo, $useLastStage, $teamId);
    }

    public function getActionEmailAddressList(Request $request)
    {
        if (!$request->getQueryParam('id')) {
            throw new BadRequest();
        }

        if (!$this->getAcl()->checkScope($this->name, 'read')) {
            throw new Forbidden();
        }

        return $this->getOpportunityService()->getEmailAddressList($request->getQueryParam('id'));
    }

    private function getOpportunityService(): Service
    {
        /** @var Service */
        return $this->getRecordService();
    }
}
