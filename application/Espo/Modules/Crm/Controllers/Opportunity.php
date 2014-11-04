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

use \Espo\Core\Exceptions\Error;
use \Espo\Core\Exceptions\Forbidden;

class Opportunity extends \Espo\Core\Controllers\Record
{
    public function actionReportByLeadSource($params, $data, $request)
    {
        $level = $this->getAcl()->getLevel('Lead', 'read');
        if (!$level || $level == 'own') {
            throw new Forbidden();
        }

        $dateFrom = $request->get('dateFrom');
        $dateTo = $request->get('dateTo');
                
        return $this->getService('Opportunity')->reportByLeadSource($dateFrom, $dateTo);
    }
    
    public function actionReportByStage($params, $data, $request)
    {
        $level = $this->getAcl()->getLevel('Opportunity', 'read');
        if (!$level || $level == 'own') {
            throw new Forbidden();
        }

        $dateFrom = $request->get('dateFrom');
        $dateTo = $request->get('dateTo');
        
        return $this->getService('Opportunity')->reportByStage($dateFrom, $dateTo);
    }
    
    public function actionReportSalesByMonth($params, $data, $request)
    {
        $level = $this->getAcl()->getLevel('Opportunity', 'read');
        if (!$level || $level == 'own') {
            throw new Forbidden();
        }

        $dateFrom = $request->get('dateFrom');
        $dateTo = $request->get('dateTo');
                
        return $this->getService('Opportunity')->reportSalesByMonth($dateFrom, $dateTo);        
    }
    
    public function actionReportSalesPipeline($params, $data, $request)
    {
        $level = $this->getAcl()->getLevel('Opportunity', 'read');
        if (!$level || $level == 'own') {
            throw new Forbidden();
        }

        $dateFrom = $request->get('dateFrom');
        $dateTo = $request->get('dateTo');
        
        return $this->getService('Opportunity')->reportSalesPipeline($dateFrom, $dateTo);
    }
}

