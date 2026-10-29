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
 * In accordance with Section 7(b) of the GNU Affero General Public License
 * version 3, these Appropriate Legal Notices must retain the "EspoCRM" word.
 * EspoCRM" must not be used to endorse or promote products derived from
 * this software without prior written permission. For written permission,
 * please contact info@espocrm.com.
 ************************************************************************/

namespace Espo\Core\Authentication\Jwt\Keys;

use Espo\Core\Authentication\Jwt\Key;
use UnexpectedValueException;
use stdClass;

/**
 * EC (Elliptic Curve) key for ES256/ES384/ES512 JWT algorithms.
 *
 * Immutable.
 */
class Ec implements Key
{
    private string $kid;

    private string $kty;

    private ?string $alg;

    private string $crv;

    private string $x;

    private string $y;

    private function __construct(stdClass $raw)
    {
        $kid = $raw->kid ?? null;
        $kty = $raw->kty ?? null;
        $alg = $raw->alg ?? null;
        $crv = $raw->crv ?? null;
        $x = $raw->x ?? null;
        $y = $raw->y ?? null;

        if ($kid === null || $kty === null) {
            throw new UnexpectedValueException("Bad JWK value.");
        }

        if ($crv === null || $x === null || $y === null) {
            throw new UnexpectedValueException("Bad JWK EC key. No `crv`, `x` or `y` values.");
        }

        $this->kid = $kid;
        $this->kty = $kty;
        $this->alg = $alg;
        $this->crv = $crv;
        $this->x = $x;
        $this->y = $y;
    }

    public static function fromRaw(stdClass $raw): self
    {
        return new self($raw);
    }

    public function getKid(): string
    {
        return $this->kid;
    }

    public function getKty(): string
    {
        return $this->kty;
    }

    public function getAlg(): ?string
    {
        return $this->alg;
    }

    public function getCrv(): string
    {
        return $this->crv;
    }

    public function getX(): string
    {
        return $this->x;
    }

    public function getY(): string
    {
        return $this->y;
    }
}
