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

namespace Espo\Core\Authentication\Jwt\Keys;

use Espo\Core\Authentication\Jwt\Key;
use UnexpectedValueException;
use stdClass;

/**
 * Immutable.
 */
class Rsa implements Key
{
    private string $kid;
    private string $kty;
    private ?string $alg;
    private string $n;
    private string $e;

    private function __construct(stdClass $raw)
    {
        $kid = $raw->kid ?? null;
        $kty = $raw->kty ?? null;
        $alg = $raw->alg ?? null;
        $n = $raw->n ?? null;
        $e = $raw->e ?? null;

        if ($kid === null || $kty === null) {
            throw new UnexpectedValueException("Bad JWK value.");
        }

        if ($n === null || $e === null) {
            throw new UnexpectedValueException("Bad JWK RSE key. No `n` or `e` values.");
        }

        $this->kid = $kid;
        $this->kty = $kty;
        $this->alg = $alg;
        $this->n = $n;
        $this->e = $e;
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

    public function getN(): string
    {
        return $this->n;
    }

    public function getE(): string
    {
        return $this->e;
    }
}
