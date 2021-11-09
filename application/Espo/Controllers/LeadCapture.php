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

namespace Espo\Controllers;

use Espo\Services\LeadCapture as Service;

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\NotFound;

use Espo\Core\{
    Controllers\Record,
    Api\Request,
    Api\Response,
};

use stdClass;

class LeadCapture extends Record
{
    public function postActionLeadCapture(Request $request, Response $response): bool
    {
        $params = $request->getRouteParams();
        $data = $request->getParsedBody();

        if (empty($params['apiKey'])) {
            throw new BadRequest('No API key provided.');
        }

        $allowOrigin = $this->config->get('leadCaptureAllowOrigin', '*');

        $response->setHeader('Access-Control-Allow-Origin', $allowOrigin);

        $this->getLeadCaptureService()->leadCapture($params['apiKey'], $data);

        return true;
    }

    public function optionsActionLeadCapture(Request $request, Response $response): bool
    {
        $params = $request->getRouteParams();

        if (empty($params['apiKey'])) {
            throw new BadRequest('No API key provided.');
        }

        if (!$this->getLeadCaptureService()->isApiKeyValid($params['apiKey'])) {
            throw new NotFound();
        }

        $allowOrigin = $this->config->get('leadCaptureAllowOrigin', '*');

        $response->setHeader('Access-Control-Allow-Headers', 'Content-Type, Accept');
        $response->setHeader('Access-Control-Allow-Origin', $allowOrigin);
        $response->setHeader('Access-Control-Allow-Methods', 'POST');

        return true;
    }

    public function postActionGenerateNewApiKey(Request $request): stdClass
    {
        $data = $request->getParsedBody();

        if (empty($data->id)) {
            throw new BadRequest();
        }

        return $this->getLeadCaptureService()
            ->generateNewApiKeyForEntity($data->id)
            ->getValueMap();
    }

    public function getActionSmtpAccountDataList(): array
    {
        if (!$this->getUser()->isAdmin()) {
            throw new Forbidden();
        }

        return $this->getLeadCaptureService()->getSmtpAccountDataList();
    }

    private function getLeadCaptureService(): Service
    {
        /** @var Service */
        return $this->getRecordService();
    }
}
