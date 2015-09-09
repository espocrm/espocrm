<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

namespace Espo\Modules\Crm\Jobs;

use \Espo\Core\Exceptions;

class ProcessMassEmail extends \Espo\Core\Jobs\Base
{
    public function run()
    {
        $service = $this->getServiceFactory()->create('MassEmail');

        $massEmailList = $this->getEntityManager()->getRepository('MassEmail')->where(array(
            'status' => 'Pending',
            'startAt<=' => date('Y-m-d H:i:s')
        ))->find();
        foreach ($massEmailList as $massEmail) {
            try {
                $service->createQueue($massEmail);
            } catch (\Exception $e) {
                $GLOBALS['log']->error('Job ProcessMassEmail#createQueue '.$massEmail->id.': [' . $e->getCode() . '] ' .$e->getMessage());
            }
        }

        $massEmailList = $this->getEntityManager()->getRepository('MassEmail')->where(array(
            'status' => 'In Process'
        ))->find();
        foreach ($massEmailList as $massEmail) {
            try {
                $service->processSending($massEmail);
            } catch (\Exception $e) {
                $GLOBALS['log']->error('Job ProcessMassEmail#processSending '.$massEmail->id.': [' . $e->getCode() . '] ' .$e->getMessage());
            }
        }

        return true;
    }
}

