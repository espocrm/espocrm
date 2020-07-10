<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2020 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

class RequestWrapper implements ApiRequest
{
    protected $request;

    protected $basePath;

    public function __construct(Psr7Request $request, string $basePath = '')
    {
        $this->request = $request;
        $this->basePath = $basePath;
    }

    public function get(?string $name = null)
    {
        if (is_null($name)) {
            return $this->getQueryParams();
        }

        return $this->getQueryParam($name);
    }

    public function getQueryParam(string $name)
    {
        return $this->request->getQueryParams()[$name] ?? null;
    }

    public function getQueryParams() : array
    {
        return $this->request->getQueryParams();
    }

    public function getHeader(string $name) : ?string
    {
        if (!$this->request->hasHeader($name)) return null;

        return $this->request->getHeaderLine($name);
    }

    public function hasHeader(string $name) : bool
    {
        return $this->request->hasHeader($name);
    }

    public function getMethod() : string
    {
        return $this->request->getMethod();
    }

    public function getContentType() : ?string
    {
        return $this->getHeader('Content-Type');
    }

    public function getBodyContents() : ?string
    {
        return $this->request->getBody()->getContents();
    }

    public function getCookieParam(string $name) : ?string
    {
        $params = $this->request->getCookieParams();
        return $params[$name] ?? null;
    }

    public function getServerParam(string $name) : ?string
    {
        $params = $this->request->getServerParams();
        return $params[$name] ?? null;
    }

    public function getUri() : UriInterface
    {
        return $this->request->getUri();
    }

    public function getResourcePath() : string
    {
        $path = $this->request->getUri()->getPath();
        return substr($path, strlen($this->basePath));
    }

    public function isGet() : bool
    {
        return $this->getMethod() === 'GET';
    }

    public function isPut() : bool
    {
        return $this->getMethod() === 'PUT';
    }

    public function isUpdate() : bool
    {
        return $this->getMethod() === 'UPDATE';
    }

    public function isPost() : bool
    {
        return $this->getMethod() === 'POST';
    }

    public function isPatch() : bool
    {
        return $this->getMethod() === 'PATCH';
    }

    public function isDelete() : bool
    {
        return $this->getMethod() === 'DELETE';
    }
}
