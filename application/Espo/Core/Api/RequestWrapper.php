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

use Espo\Core\Utils\Json;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Api\Request as ApiRequest;

use Psr\Http\Message\ServerRequestInterface as Psr7Request;
use Psr\Http\Message\UriInterface;

use stdClass;

/**
 * Adapter for PSR-7 request interface.
 */
class RequestWrapper implements ApiRequest
{
    private ?stdClass $parsedBody = null;

    /**
     * @param array<string, string> $routeParams
     */
    public function __construct(
        private Psr7Request $psr7Request,
        private string $basePath = '',
        private array $routeParams = []
    ) {}

    /**
     * Get a route or query parameter. Route params have a higher priority.
     *
     * @todo Don't support NULL $name.
     * @deprecated As of v6.0. Use getQueryParam & getRouteParam. Left for backward compatibility.
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

        return $this->psr7Request->getQueryParams()[$name] ?? null;
    }

    public function hasRouteParam(string $name): bool
    {
        return array_key_exists($name, $this->routeParams);
    }

    public function getRouteParam(string $name): ?string
    {
        return $this->routeParams[$name] ?? null;
    }

    /**
     * @return array<string, string>
     */
    public function getRouteParams(): array
    {
        return $this->routeParams;
    }

    public function hasQueryParam(string $name): bool
    {
        return array_key_exists($name, $this->psr7Request->getQueryParams());
    }

    public function getQueryParam(string $name): ?string
    {
        $value = $this->psr7Request->getQueryParams()[$name] ?? null;

        if (!is_string($value)) {
            return null;
        }

        return $value;
    }

    public function getQueryParams(): array
    {
        return $this->psr7Request->getQueryParams();
    }

    public function getHeader(string $name): ?string
    {
        if (!$this->psr7Request->hasHeader($name)) {
            return null;
        }

        return $this->psr7Request->getHeaderLine($name);
    }

    public function hasHeader(string $name): bool
    {
        return $this->psr7Request->hasHeader($name);
    }

    /**
     * @return string[]
     */
    public function getHeaderAsArray(string $name): array
    {
        if (!$this->psr7Request->hasHeader($name)) {
            return [];
        }

        return $this->psr7Request->getHeader($name);
    }

    public function getMethod(): string
    {
        return $this->psr7Request->getMethod();
    }

    public function getContentType(): ?string
    {
        if (!$this->hasHeader('Content-Type')) {
            return null;
        }

        $contentType = explode(
            ';',
            $this->psr7Request->getHeader('Content-Type')[0]
        )[0];

        return strtolower($contentType);
    }

    public function getBodyContents(): ?string
    {
        $contents = $this->psr7Request->getBody()->getContents();

        $this->psr7Request->getBody()->rewind();

        return $contents;
    }

    /**
     * @throws BadRequest
     */
    public function getParsedBody(): stdClass
    {
        if ($this->parsedBody === null) {
            $this->initParsedBody();
        }

        if ($this->parsedBody === null) {
            throw new BadRequest();
        }

        return Util::cloneObject($this->parsedBody);
    }

    /**
     * @throws BadRequest
     */
    private function initParsedBody(): void
    {
        $contents = $this->getBodyContents();

        $contentType = $this->getContentType();

        if ($contentType === 'application/json' && $contents) {
            $parsedBody = Json::decode($contents);

            if (is_array($parsedBody)) {
                $parsedBody = (object) [
                    'list' => $parsedBody,
                ];
            }

            if (!$parsedBody instanceof stdClass) {
                throw new BadRequest("Body is not a JSON object.");
            }

            $this->parsedBody = $parsedBody;

            return;
        }

        if (
            in_array($contentType, ['application/x-www-form-urlencoded', 'multipart/form-data']) &&
            $contents
        ) {
            $parsedBody = $this->psr7Request->getParsedBody();

            if (is_array($parsedBody)) {
                $this->parsedBody = (object) $parsedBody;

                return;
            }

            if ($parsedBody instanceof stdClass) {
                $this->parsedBody = $parsedBody;

                return;
            }
        }

        $this->parsedBody = (object) [];
    }

    public function getCookieParam(string $name): ?string
    {
        $params = $this->psr7Request->getCookieParams();

        return $params[$name] ?? null;
    }

    /**
     * @return mixed
     */
    public function getServerParam(string $name)
    {
        $params = $this->psr7Request->getServerParams();

        return $params[$name] ?? null;
    }

    public function getUri(): UriInterface
    {
        return $this->psr7Request->getUri();
    }

    public function getResourcePath(): string
    {
        $path = $this->psr7Request->getUri()->getPath();

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

    /** @noinspection PhpUnused */
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
