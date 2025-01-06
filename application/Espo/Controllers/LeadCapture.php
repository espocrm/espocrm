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

namespace Espo\Controllers;

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\NotFound;

use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Controllers\Record;
use Espo\Core\Exceptions\Error;

use Espo\Tools\LeadCapture\Service;
use Espo\Tools\LeadCapture\CaptureService as CaptureService;
use stdClass;

class LeadCapture extends Record
{
    /**
     * @throws BadRequest
     * @throws NotFound
     * @throws Error
     */
    public function postActionLeadCapture(Request $request, Response $response): bool
    {
        $data = $request->getParsedBody();
        $apiKey = $request->getRouteParam('apiKey');

        if (!$apiKey) {
            throw new BadRequest('No API key provided.');
        }

        $allowOrigin = $this->config->get('leadCaptureAllowOrigin', '*');

        $response->setHeader('Access-Control-Allow-Origin', $allowOrigin);

        $this->getCaptureService()->capture($apiKey, $data);

        return true;
    }

    /**
     * @throws BadRequest
     * @throws NotFound
     */
    public function optionsActionLeadCapture(Request $request, Response $response): bool
    {
        $apiKey = $request->getRouteParam('apiKey');

        if (!$apiKey) {
            throw new BadRequest('No API key provided.');
        }

        if (!$this->getLeadCaptureService()->isApiKeyValid($apiKey)) {
            throw new NotFound();
        }

        $allowOrigin = $this->config->get('leadCaptureAllowOrigin', '*');

        $response->setHeader('Access-Control-Allow-Headers', 'Content-Type, Accept');
        $response->setHeader('Access-Control-Allow-Origin', $allowOrigin);
        $response->setHeader('Access-Control-Allow-Methods', 'POST');

        return true;
    }

    /**
     * @throws BadRequest
     * @throws NotFound
     * @throws Forbidden
     */
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

    /**
     * @throws BadRequest
     * @throws NotFound
     * @throws Forbidden
     */
    public function postActionGenerateNewFormId(Request $request): stdClass
    {
        $data = $request->getParsedBody();

        if (empty($data->id)) {
            throw new BadRequest();
        }

        return $this->getLeadCaptureService()
            ->generateNewFormIdForEntity($data->id)
            ->getValueMap();
    }

    /**
     * @return stdClass[]
     * @throws Forbidden
     */
    public function getActionSmtpAccountDataList(): array
    {
        if (!$this->user->isAdmin()) {
            throw new Forbidden();
        }

        return $this->getLeadCaptureService()->getSmtpAccountDataList();
    }

    private function getCaptureService(): CaptureService
    {
        return $this->injectableFactory->create(CaptureService::class);
    }

    private function getLeadCaptureService(): Service
    {
        return $this->injectableFactory->create(Service::class);
    }
}
