<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

namespace Espo\Tools\Api\Guard;

use Espo\Core\Utils\Config;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use RenzoFranceschini\GuardCorePsr15\GuardMiddleware;

/**
 * Opt-in application layer security (Guard Core). Does nothing, and builds nothing, unless `guardEnabled` is true.
 */
class Middleware implements MiddlewareInterface
{
    private ?GuardMiddleware $guard = null;

    public function __construct(
        private Config $config,
        private DefaultPolicy $defaultPolicy,
        private GuardMiddlewareBuilder $builder,
    ) {}

    public function process(ServerRequest $request, RequestHandler $handler): Response
    {
        if (!$this->config->get('guardEnabled')) {
            return $handler->handle($request);
        }

        $this->guard ??= $this->builder->build($this->defaultPolicy->merge($this->getOperatorSettings()));

        return $this->guard->process($request, $handler);
    }

    /**
     * @return array<string, mixed>
     */
    private function getOperatorSettings(): array
    {
        $settings = $this->config->get('guard') ?? [];

        if (!is_array($settings)) {
            throw new \InvalidArgumentException("Config parameter 'guard' must be a map of settings.");
        }

        return $settings;
    }
}
