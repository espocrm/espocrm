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

use Psr\Http\Message\ResponseInterface as Psr7Response;
use Psr\Http\Message\StreamInterface;

use Espo\Core\Api\Response as ApiResponse;

/**
 * Adapter for PSR-7 response interface.
 */
class ResponseWrapper implements ApiResponse
{
    public function __construct(private Psr7Response $psr7Response)
    {
        // Slim adds Authorization header. It's not needed.
        $this->psr7Response = $this->psr7Response->withoutHeader('Authorization');
    }

    public function setStatus(int $code, ?string $reason = null): Response
    {
        $this->psr7Response = $this->psr7Response->withStatus($code, $reason ?? '');

        return $this;
    }

    public function getStatusCode(): int
    {
        return $this->psr7Response->getStatusCode();
    }

    public function getReasonPhrase(): string
    {
        return $this->psr7Response->getReasonPhrase();
    }

    public function setHeader(string $name, string $value): Response
    {
        $this->psr7Response = $this->psr7Response->withHeader($name, $value);

        return $this;
    }

    public function addHeader(string $name, string $value): Response
    {
        $this->psr7Response = $this->psr7Response->withAddedHeader($name, $value);

        return $this;
    }

    public function getHeader(string $name): ?string
    {
        if (!$this->psr7Response->hasHeader($name)) {
            return null;
        }

        return $this->psr7Response->getHeaderLine($name);
    }

    public function hasHeader(string $name): bool
    {
        return $this->psr7Response->hasHeader($name);
    }

    /**
     * @return string[]
     */
    public function getHeaderAsArray(string $name): array
    {
        if (!$this->psr7Response->hasHeader($name)) {
            return [];
        }

        return $this->psr7Response->getHeader($name);
    }

    /**
     * @return string[]
     */
    public function getHeaderNames(): array
    {
        return array_keys($this->psr7Response->getHeaders());
    }

    public function writeBody(string $string): Response
    {
        $this->psr7Response->getBody()->write($string);

        return $this;
    }

    public function setBody(StreamInterface $body): Response
    {
        $this->psr7Response = $this->psr7Response->withBody($body);

        return $this;
    }

    public function getBody(): StreamInterface
    {
        return $this->psr7Response->getBody();
    }

    public function toPsr7(): Psr7Response
    {
        return $this->psr7Response;
    }
}
