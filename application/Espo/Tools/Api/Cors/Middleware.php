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

namespace Espo\Tools\Api\Cors;

use Fig\Http\Message\RequestMethodInterface as RequestMethod;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Factory\ResponseFactory;

class Middleware implements MiddlewareInterface
{
    private const DEFAULT_SUCCESS_STATUS = 204;
    private const DEFAULT_MAX_AGE = 86400;

    public function __construct(private Helper $helper)
    {}

    public function process(ServerRequest $request, RequestHandler $handler): Response
    {
        $isPreFlight = $request->getMethod() === RequestMethod::METHOD_OPTIONS;

        $response = $isPreFlight ?
            (new ResponseFactory)->createResponse() :
            $handler->handle($request);

        $allowedOrigin = $this->helper->getAllowedOrigin($request);

        if (!$allowedOrigin) {
            return $response;
        }

        $status = $this->helper->getSuccessStatus() ?? self::DEFAULT_SUCCESS_STATUS;
        $allowedMethods = $this->helper->getAllowedMethods($request);
        $allowedHeaders = $this->helper->getAllowedHeaders($request);
        $maxAge = $this->helper->getMaxAge() ?? self::DEFAULT_MAX_AGE;
        $credentialsAllowed = $this->helper->isCredentialsAllowed($request);

        $response = $response
            ->withHeader('Access-Control-Allow-Origin', $allowedOrigin)
            ->withHeader('Access-Control-Max-Age', (string) $maxAge);

        if ($credentialsAllowed) {
            $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
        }

        if (!$isPreFlight) {
            return $response;
        }

        if ($allowedMethods !== []) {
            $response = $response->withHeader('Access-Control-Allow-Methods', implode(', ', $allowedMethods));
        }

        if ($allowedHeaders !== []) {
            $response = $response->withHeader('Access-Control-Allow-Headers', implode(', ', $allowedHeaders));
        }

        return $response->withStatus($status);
    }
}
