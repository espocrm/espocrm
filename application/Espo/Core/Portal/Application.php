<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2020 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

use Espo\Core\Exceptions\{
    Error,
    NotFound,
    Forbidden,
};

use Espo\Core\{
    Portal\Container as PortalContainer,
    Portal\ContainerConfiguration as PortalContainerConfiguration,
    Portal\Loaders\Config as ConfigLoader,
    Application as BaseApplication,
};

class Application extends BaseApplication
{
    public function __construct(?string $portalId)
    {
        date_default_timezone_set('UTC');

        $this->initContainer();

        $this->initPortal($portalId);

        $this->initAutoloads();
        $this->initPreloads();
    }

    protected function initContainer()
    {
        $this->loaderClassNames['config'] = ConfigLoader::class;

        $this->container = new PortalContainer(PortalContainerConfiguration::class, $this->loaderClassNames);
    }

    protected function initPortal(?string $portalId)
    {
        if (!$portalId) {
            throw new Error("Portal ID was not passed to Portal\Application.");
        }

        $entityManager = $this->container->get('entityManager');

        $portal = $entityManager->getEntity('Portal', $portalId);

        if (!$portal) {
            $portal = $entityManager->getRepository('Portal')->where(['customId' => $portalId])->findOne();
        }

        if (!$portal) {
            throw new NotFound("Portal {$portalId} not found.");
        }
        if (!$portal->get('isActive')) {
            throw new Forbidden("Portal {$portalId} is not active.");
        }

        $this->portal = $portal;

        $this->container->setPortal($portal);
    }

    protected function getPortal()
    {
        return $this->portal;
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
        $this->container->get('clientManager')->display(null, null, [
            'portalId' => $this->getPortal()->id,
            'applicationId' => $this->getPortal()->id,
            'apiUrl' => 'api/v1/portal-access/' . $this->getPortal()->id,
            'appClientClassName' => 'app-portal'
        ]);
        exit;
    }

    protected function initPreloads()
    {
        foreach ($this->getMetadata()->get(['app', 'portalContainerServices']) ?? [] as $name => $defs) {
            if ($defs['preload'] ?? false) {
                $this->container->get($name);
            }
        }
    }
}
