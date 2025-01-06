<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\EntryPoints;

use Espo\Tools\LeadCapture\CaptureService as Service;

use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\EntryPoint\EntryPoint;
use Espo\Core\EntryPoint\Traits\NoAuth;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Utils\Client\ActionRenderer;
use Espo\Tools\LeadCapture\ConfirmResult;
use LogicException;

class ConfirmOptIn implements EntryPoint
{
    use NoAuth;

    private Service $service;
    private ActionRenderer $actionRenderer;

    public function __construct(Service $service, ActionRenderer $actionRenderer)
    {
        $this->service = $service;
        $this->actionRenderer = $actionRenderer;
    }

    /**
     * @throws BadRequest
     * @throws Error
     * @throws NotFound
     */
    public function run(Request $request, Response $response): void
    {
        $id = $request->getQueryParam('id');

        if (!$id) {
            throw new BadRequest("No id.");
        }

        $result = $this->service->confirmOptIn($id);

        $action = null;

        if ($result->getStatus() === ConfirmResult::STATUS_EXPIRED) {
            $action = 'optInConfirmationExpired';
        }

        if ($result->getStatus() === ConfirmResult::STATUS_SUCCESS) {
            $action = 'optInConfirmationSuccess';
        }

        if (!$action) {
            throw new LogicException();
        }

        $data = [
            'status' => $result->getStatus(),
            'message' => $result->getMessage(),
            'leadCaptureId' => $result->getLeadCaptureId(),
            'leadCaptureName' => $result->getLeadCaptureName(),
        ];

        $params = new ActionRenderer\Params('controllers/lead-capture-opt-in-confirmation', $action, $data);

        $this->actionRenderer->write($response, $params);
    }
}
