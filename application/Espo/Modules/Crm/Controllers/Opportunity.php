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
namespace Espo\Modules\Crm\Controllers;

use Espo\Core\Controllers\Record;
use Slim\Http\Request;

class Opportunity extends
    Record
{

    /**
     * @param         $params
     * @param         $data
     * @param Request $request
     *
     * @return mixed
     * @since 1.0
     */
    public function actionReportByLeadSource($params, $data, $request)
    {
        $dateFrom = $request->get('dateFrom');
        $dateTo = $request->get('dateTo');
        return $this->getOpportunityService()->reportByLeadSource($dateFrom, $dateTo);
    }

    /**
     * @param         $params
     * @param         $data
     * @param Request $request
     *
     * @return mixed
     * @since 1.0
     */
    public function actionReportByStage($params, $data, $request)
    {
        $dateFrom = $request->get('dateFrom');
        $dateTo = $request->get('dateTo');
        return $this->getOpportunityService()->reportByStage($dateFrom, $dateTo);
    }

    /**
     * @param         $params
     * @param         $data
     * @param Request $request
     *
     * @return mixed
     * @since 1.0
     */
    public function actionReportSalesByMonth($params, $data, $request)
    {
        $dateFrom = $request->get('dateFrom');
        $dateTo = $request->get('dateTo');
        return $this->getOpportunityService()->reportSalesByMonth($dateFrom, $dateTo);
    }

    /**
     * @param         $params
     * @param         $data
     * @param Request $request
     *
     * @return mixed
     * @since 1.0
     */
    public function actionReportSalesPipeline($params, $data, $request)
    {
        $dateFrom = $request->get('dateFrom');
        $dateTo = $request->get('dateTo');
        return $this->getOpportunityService()->reportSalesPipeline($dateFrom, $dateTo);
    }

    /**
     * @return \Espo\Modules\Crm\Services\Opportunity
     * @since 1.0
     */
    protected function getOpportunityService()
    {
        return $this->getService('Opportunity');
    }
}

