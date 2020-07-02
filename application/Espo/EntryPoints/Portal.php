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

namespace Espo\EntryPoints;

use Espo\Core\Exceptions\NotFound;

use Espo\Core\EntryPoints\{
    EntryPoint,
    NoAuth,
};

use Espo\Core\{
    Utils\ClientManager,
    Utils\Config,
    Portal\Application as PortalApplication,
};

class Portal implements EntryPoint
{
    use NoAuth;

    protected $clientManager;
    protected $config;

    public function __construct(ClientManager $clientManager, Config $config)
    {
        $this->clientManager = $clientManager;
        $this->config = $config;
    }

    public function run($request, $response, $data = [])
    {
        $id = $request->get('id') ?? $data['id'] ?? null;

        if (!$id) {
            $url = $_SERVER['REQUEST_URI'];
            $id = explode('/', $url)[count(explode('/', $_SERVER['SCRIPT_NAME'])) - 1];

            if (!isset($id)) {
                $url = $_SERVER['REDIRECT_URL'];
                $id = explode('/', $url)[count(explode('/', $_SERVER['SCRIPT_NAME'])) - 1];
            }

            if (!$id) {
                $id = $this->config->get('defaultPortalId');
            }
            if (!$id) {
                throw new NotFound();
            }
        }

        $application = new PortalApplication($id);
        $application->setBasePath($this->clientManager->getBasePath());
        $application->runClient();
    }
}
