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

namespace Espo\EntryPoints;

use \Espo\Core\Exceptions\NotFound;
use \Espo\Core\Exceptions\Forbidden;
use \Espo\Core\Exceptions\BadRequest;
use \Espo\Core\Exceptions\Error;

class ConfirmOptIn extends \Espo\Core\EntryPoints\Base
{
    public static $authRequired = false;

    public function run()
    {
        if (empty($_GET['id'])) throw new BadRequest();

        $id = $_GET['id'];

        $data = $this->getServiceFactory()->create('LeadCapture')->confirmOptIn($id);

        if ($data->status === 'success') {
            $action = 'optInConfirmationSuccess';
        } else if ($data->status === 'expired') {
            $action = 'optInConfirmationExpired';
        } else {
            throw new Error();
        }

        $runScript = "
            Espo.require('controllers/lead-capture-opt-in-confirmation', function (Controller) {
                var controller = new Controller(app.baseController.params, app.getControllerInjection());
                controller.masterView = app.masterView;
                controller.doAction('".$action."', ".json_encode($data).");
            });
        ";

        $this->getClientManager()->display($runScript);
    }
}
