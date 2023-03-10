<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2023 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Core\Portal\Api;

use Espo\Core\Api\MiddlewareProvider;
use Espo\Core\Api\Starter as StarterBase;
use Espo\Core\ApplicationState;
use Espo\Core\Portal\Utils\Route as RouteUtil;
use Espo\Core\Api\RouteProcessor;
use Espo\Core\Api\Route\RouteParamsFetcher;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Log;

class Starter extends StarterBase
{
    public function __construct(
        RouteProcessor $requestProcessor,
        RouteUtil $routeUtil,
        RouteParamsFetcher $routeParamsFetcher,
        MiddlewareProvider $middlewareProvider,
        Log $log,
        Config $config,
        ApplicationState $applicationState
    ) {
        $routeCacheFile = 'data/cache/application/slim-routes-portal-' . $applicationState->getPortalId() . '.php';

        parent::__construct(
            $requestProcessor,
            $routeUtil,
            $routeParamsFetcher,
            $middlewareProvider,
            $log,
            $config,
            $routeCacheFile
        );
    }
}
