<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

namespace Espo\Core\Portal;

use \Espo\Core\Exceptions\Error;
use \Espo\Core\Exceptions\NotFound;
use \Espo\Core\Exceptions\Forbidden;

class Application extends \Espo\Core\Application
{
    public function __construct($portalId)
    {
        date_default_timezone_set('UTC');

        $this->initContainer();

        if (empty($portalId)) {
            throw new Error("Portal id was not passed to ApplicationPortal.");
        }

        $GLOBALS['log'] = $this->getContainer()->get('log');

        $portal = $this->getContainer()->get('entityManager')->getEntity('Portal', $portalId);

        if (!$portal) {
            $portal = $this->getContainer()->get('entityManager')->getRepository('Portal')->where([
                'customId' => $portalId
            ])->findOne();
        }

        if (!$portal) {
            throw new NotFound();
        }
        if (!$portal->get('isActive')) {
            throw new Forbidden("Portal is not active.");
        }

        $this->portal = $portal;

        $this->getContainer()->setPortal($portal);

        $this->initAutoloads();
    }

    protected function getPortal()
    {
        return $this->portal;
    }

    protected function initContainer()
    {
        $this->container = new Container();
    }

    protected function getRouteList()
    {
        $routeList = parent::getRouteList();
        foreach ($routeList as $i => $route) {
            if (isset($route['route'])) {
                if ($route['route']{0} !== '/') {
                    $route['route'] = '/' . $route['route'];
                }
                $route['route'] = '/:portalId' . $route['route'];
            }
            $routeList[$i] = $route;
        }
        return $routeList;
    }

    public function runClient()
    {
        $this->getContainer()->get('clientManager')->display(null, null, [
            'portalId' => $this->getPortal()->id,
            'applicationId' => $this->getPortal()->id,
            'apiUrl' => 'api/v1/portal-access/' . $this->getPortal()->id,
            'appClientClassName' => 'app-portal'
        ]);
        exit;
    }
}
