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

namespace Espo\Core\Api;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Conflict;
use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;

use Espo\Core\Utils\Config;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseInterface as Psr7Response;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ResponseFactory;

/**
 * @internal
 */
class ActionHandler implements RequestHandlerInterface
{
    private const DEFAULT_CONTENT_TYPE = 'application/json';

    public function __construct(
        private Action $action,
        private ProcessData $processData,
        private Config $config
    ) {}

    /**
     * @throws BadRequest
     * @throws Forbidden
     * @throws Error
     * @throws NotFound
     * @throws Conflict
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $requestWrapped = new RequestWrapper(
            $request,
            $this->processData->getBasePath(),
            $this->processData->getRouteParams()
        );

        $response = $this->action->process($requestWrapped);

        return $this->prepareResponse($response);
    }

    private function prepareResponse(Response $response): Psr7Response
    {
        if (!$response->hasHeader('Content-Type')) {
            $response->setHeader('Content-Type', self::DEFAULT_CONTENT_TYPE);
        }

        if (!$response->hasHeader('Cache-Control')) {
            $response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        }

        if (!$response->hasHeader('Expires')) {
            $response->setHeader('Expires', '0');
        }

        if (!$response->hasHeader('Last-Modified')) {
            $response->setHeader('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT');
        }

        $response->setHeader('X-App-Timestamp', (string) ($this->config->get('appTimestamp') ?? '0'));

        /** @noinspection PhpConditionAlreadyCheckedInspection */
        return $response instanceof ResponseWrapper ?
            $response->toPsr7() :
            self::responseToPsr7($response);
    }

    private static function responseToPsr7(Response $response): Psr7Response
    {
        $psr7Response = (new ResponseFactory())->createResponse();

        $statusCode = $response->getStatusCode();
        $reason = $response->getReasonPhrase();
        $body = $response->getBody();

        $psr7Response = $psr7Response
            ->withStatus($statusCode, $reason)
            ->withBody($body);

        foreach ($response->getHeaderNames() as $name) {
            $psr7Response = $psr7Response->withHeader($name, $response->getHeaderAsArray($name));
        }

        return $psr7Response;
    }
}
