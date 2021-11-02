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

namespace Espo\Core\Api;

use Psr\Http\Message\{
    ServerRequestInterface as Psr7Request,
    UriInterface,
};

use Espo\Core\Api\Request as ApiRequest;

use stdClass;

/**
 * Adapter for PSR-7 request interface.
 */
class RequestWrapper implements ApiRequest
{
    private $request;

    private $basePath;

    private $parsedBody = null;

    private $routeParams;

    public function __construct(Psr7Request $request, string $basePath = '', array $routeParams = [])
    {
        $this->request = $request;
        $this->basePath = $basePath;
        $this->routeParams = $routeParams;
    }

    /**
     * Get a route or query parameter. Route params have a higher priority.
     *
     * @todo Don't support NULL $name.
     * @deprecated For backward compatibility.
     *
     * @return mixed
     */
    public function get(?string $name = null)
    {
        if (is_null($name)) {
            return array_merge(
                $this->getQueryParams(),
                $this->routeParams
            );
        }

        if ($this->hasRouteParam($name)) {
            return $this->getRouteParam($name);
        }

        return $this->getQueryParam($name);
    }

    public function hasRouteParam(string $name): bool
    {
        return array_key_exists($name, $this->routeParams);
    }

    public function getRouteParam(string $name): ?string
    {
        return $this->routeParams[$name] ?? null;
    }

    public function getRouteParams(): array
    {
        return $this->routeParams;
    }

    public function hasQueryParam(string $name): bool
    {
        return array_key_exists($name, $this->request->getQueryParams());
    }

    /**
     * @return string|array|null
     */
    public function getQueryParam(string $name)
    {
        $value = $this->request->getQueryParams()[$name] ?? null;

        if ($value === null) {
            return null;
        }

        return $value;
    }

    public function getQueryParams(): array
    {
        return $this->request->getQueryParams();
    }

    public function getHeader(string $name): ?string
    {
        if (!$this->request->hasHeader($name)) {
            return null;
        }

        return $this->request->getHeaderLine($name);
    }

    public function hasHeader(string $name): bool
    {
        return $this->request->hasHeader($name);
    }

    /**
     * @return string[]
     */
    public function getHeaderAsArray(string $name): array
    {
        if (!$this->request->hasHeader($name)) {
            return [];
        }

        return $this->request->getHeader($name);
    }

    public function getMethod(): string
    {
        return $this->request->getMethod();
    }

    public function getContentType(): ?string
    {
        if (!$this->hasHeader('Content-Type')) {
            return null;
        }

        $contentType = explode(
            ';',
            $this->request->getHeader('Content-Type')[0]
        )[0];

        return strtolower($contentType);
    }

    public function getBodyContents(): ?string
    {
        $contents = $this->request->getBody()->getContents();

        $this->request->getBody()->rewind();

        return $contents;
    }

    public function getParsedBody(): stdClass
    {
        if ($this->parsedBody === null) {
            $this->initParsedBody();
        }

        return Util::cloneObject($this->parsedBody);
    }

    private function initParsedBody()
    {
        $contents = $this->getBodyContents();

        if ($this->getContentType() === 'application/json' && $contents) {
            $this->parsedBody = json_decode($contents);

            if (is_array($this->parsedBody)) {
                $this->parsedBody = (object) [
                    'list' => $this->parsedBody,
                ];
            }

            return;
        }

        $this->parsedBody = (object) [];
    }

    public function getCookieParam(string $name): ?string
    {
        $params = $this->request->getCookieParams();

        return $params[$name] ?? null;
    }

    /**
     * @return mixed
     */
    public function getServerParam(string $name)
    {
        $params = $this->request->getServerParams();

        return $params[$name] ?? null;
    }

    public function getUri(): UriInterface
    {
        return $this->request->getUri();
    }

    public function getResourcePath(): string
    {
        $path = $this->request->getUri()->getPath();

        return substr($path, strlen($this->basePath));
    }

    public function isGet(): bool
    {
        return $this->getMethod() === 'GET';
    }

    public function isPut(): bool
    {
        return $this->getMethod() === 'PUT';
    }

    public function isUpdate(): bool
    {
        return $this->getMethod() === 'UPDATE';
    }

    public function isPost(): bool
    {
        return $this->getMethod() === 'POST';
    }

    public function isPatch(): bool
    {
        return $this->getMethod() === 'PATCH';
    }

    public function isDelete(): bool
    {
        return $this->getMethod() === 'DELETE';
    }
}
