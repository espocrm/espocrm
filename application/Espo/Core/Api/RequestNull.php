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

use Espo\Core\Api\Request as ApiRequest;

use Psr\Http\Message\UriInterface;

use Slim\Psr7\Factory\UriFactory;

use stdClass;

/**
 * An empty stub for Request.
 */
class RequestNull implements ApiRequest
{
    public function hasQueryParam(string $name): bool
    {
        return false;
    }

    /**
     * @return string|array|null
     */
    public function getQueryParam(string $name)
    {
        return null;
    }

    public function getQueryParams(): array
    {
        return [];
    }

    public function hasRouteParam(string $name): bool
    {
        return false;
    }

    public function getRouteParam(string $name): ?string
    {
        return null;
    }

    public function getRouteParams(): array
    {
        return [];
    }

    public function getHeader(string $name): ?string
    {
        return null;
    }

    public function hasHeader(string $name): bool
    {
        return false;
    }

    /**
     * @return string[]
     */
    public function getHeaderAsArray(string $name): array
    {
        return [];
    }

    public function getMethod(): string
    {
        return '';
    }

    public function getUri(): UriInterface
    {
        return (new UriFactory())->createUri();
    }

    public function getResourcePath(): string
    {
        return '';
    }

    public function getBodyContents(): ?string
    {
        return null;
    }

    public function getParsedBody(): stdClass
    {
        return (object) [];
    }

    public function getCookieParam(string $name): ?string
    {
        return null;
    }

    /**
     * @return mixed
     */
    public function getServerParam(string $name)
    {
        return null;
    }
}
