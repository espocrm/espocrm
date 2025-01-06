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

namespace Espo\Core\ApplicationRunners;

use Espo\Core\Api\ErrorOutput;
use Espo\Core\Api\RequestWrapper;
use Espo\Core\Api\ResponseWrapper;
use Espo\Core\Application\Runner\Params;
use Espo\Core\Application\RunnerParameterized;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Portal\Application as PortalApplication;
use Espo\Core\Portal\ApplicationRunners\Client as PortalPortalClient;
use Espo\Core\Portal\Utils\Url;
use Espo\Core\Utils\ClientManager;
use Espo\Core\Utils\Config;

use Slim\Factory\ServerRequestCreatorFactory;
use Slim\Psr7\Response;
use Slim\ResponseEmitter;

use Exception;

/**
 * Runs a portal client.
 */
class PortalClient implements RunnerParameterized
{

    public function __construct(
        private ClientManager $clientManager,
        private Config $config,
        private ErrorOutput $errorOutput
    ) {}

    /**
     * @throws BadRequest
     */
    public function run(Params $params): void
    {
        $id = $params->get('id') ??
            Url::detectPortalId() ??
            $this->config->get('defaultPortalId');

        $basePath = $params->get('basePath') ?? $this->clientManager->getBasePath();

        $requestWrapped = new RequestWrapper(
            ServerRequestCreatorFactory::create()->createServerRequestFromGlobals()
        );

        $responseWrapped = new ResponseWrapper(new Response());

        if ($requestWrapped->getMethod() !== 'GET') {
            throw new BadRequest("Only GET request is allowed.");
        }

        try {
            if (!$id) {
                throw new NotFound("Portal ID not detected.");
            }

            $application = new PortalApplication($id);
        } catch (Exception $e) {
            $this->processError($requestWrapped, $responseWrapped, $e);

            return;
        }

        $application->setClientBasePath($basePath);

        $application->run(PortalPortalClient::class);
    }

    private function processError(RequestWrapper $request, ResponseWrapper $response, Exception $exception): void
    {
        $this->errorOutput->processWithBodyPrinting($request, $response, $exception);

        (new ResponseEmitter())->emit($response->toPsr7());
    }
}
