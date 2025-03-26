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

namespace Espo\Core\Authentication\Jwt;

use Espo\Core\Authentication\Jwt\Token\Header;
use Espo\Core\Authentication\Jwt\Token\Payload;
use RuntimeException;

/**
 * JWT token.
 *
 * Immutable.
 */
class Token
{
    private string $token;
    private string $headerPart;
    private string $payloadPart;
    private string $signaturePart;
    private string $headerRaw;
    private string $payloadRaw;
    private string $signatureRaw;
    private Header $header;
    private Payload $payload;

    private function __construct(string $token)
    {
        $this->token = $token;

        $parts = explode('.', $token);

        if (count($parts) < 3) {
            throw new RuntimeException("Too few JWT parts.");
        }

        list($this->headerPart, $this->payloadPart, $this->signaturePart) = $parts;

        $this->headerRaw = Util::base64UrlDecode($this->headerPart);
        $this->payloadRaw = Util::base64UrlDecode($this->payloadPart);
        $this->signatureRaw = Util::base64UrlDecode($this->signaturePart);

        $this->header = Header::fromRaw($this->headerRaw);
        $this->payload = Payload::fromRaw($this->payloadRaw);
    }

    public static function create(string $token): self
    {
        return new self($token);
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getSigningInput(): string
    {
        return $this->headerPart . '.' . $this->payloadPart;
    }

    public function getHeader(): Header
    {
        return $this->header;
    }

    public function getPayload(): Payload
    {
        return $this->payload;
    }

    public function getSignature(): string
    {
        return $this->signatureRaw;
    }

    public function getHeaderRaw(): string
    {
        return $this->headerRaw;
    }

    public function getPayloadRaw(): string
    {
        return $this->payloadRaw;
    }
}
