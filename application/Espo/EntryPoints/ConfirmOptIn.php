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

namespace Espo\EntryPoints;

use Espo\Services\LeadCapture as Service;

use Espo\Core\{
    Exceptions\BadRequest,
    Exceptions\Error,
    EntryPoint\EntryPoint,
    EntryPoint\Traits\NoAuth,
    Utils\ClientManager,
    Api\Request,
    Api\Response,
};

class ConfirmOptIn implements EntryPoint
{
    use NoAuth;

    private $clientManager;

    private $service;

    public function __construct(ClientManager $clientManager, Service $service)
    {
        $this->clientManager = $clientManager;
        $this->service = $service;
    }

    public function run(Request $request, Response $response): void
    {
        $id = $request->getQueryParam('id');

        if (!$id) {
            throw new BadRequest();
        }

        $data = $this->service->confirmOptIn($id);

        if ($data->status === 'success') {
            $action = 'optInConfirmationSuccess';
        }
        else if ($data->status === 'expired') {
            $action = 'optInConfirmationExpired';
        }
        else {
            throw new Error();
        }

        $runScript = "
            Espo.require('controllers/lead-capture-opt-in-confirmation', function (Controller) {
                var controller = new Controller(app.baseController.params, app.getControllerInjection());
                controller.masterView = app.masterView;
                controller.doAction('".$action."', ".json_encode($data).");
            });
        ";

        $this->clientManager->display($runScript);
    }
}
