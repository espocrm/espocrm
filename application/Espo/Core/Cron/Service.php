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
namespace Espo\Core\Cron;

use Espo\Core\Exceptions\NotFound;
use Espo\Core\ServiceFactory;
use Espo\Core\Utils\Json;

class Service
{

    private $serviceFactory;

    public function __construct(ServiceFactory $serviceFactory)
    {
        $this->serviceFactory = $serviceFactory;
    }

    public function run($job)
    {
        $serviceName = $job['service_name'];
        if (!$this->getServiceFactory()->checkExists($serviceName)) {
            throw new NotFound();
        }
        $service = $this->getServiceFactory()->create($serviceName);
        $serviceMethod = $job['method'];
        if (!method_exists($service, $serviceMethod)) {
            throw new NotFound();
        }
        $data = $job['data'];
        if (Json::isJSON($data)) {
            $data = Json::decode($data, true);
        }
        $service->$serviceMethod($data);
    }

    protected function getServiceFactory()
    {
        return $this->serviceFactory;
    }
}