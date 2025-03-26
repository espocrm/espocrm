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

namespace Espo\Core\Authentication\Jwt\Token;

use Espo\Core\Utils\Json;
use RuntimeException;
use JsonException;
use stdClass;

/**
 * Immutable.
 */
class Header
{
    private string $alg;
    private ?string $kid;
    /** @var array<string, mixed> */
    private array $data;

    /**
     * @param array<string, mixed> $data
     */
    private function __construct(
        string $alg,
        ?string $kid,
        array $data
    ) {
        $this->alg = $alg;
        $this->kid = $kid;
        $this->data = $data;
    }

    /**
     * @return mixed
     */
    public function get(string $name)
    {
        return $this->data[$name] ?? null;
    }

    public static function fromRaw(string $raw): self
    {
        $parsed = null;

        try {
            $parsed = Json::decode($raw);
        } catch (JsonException) {}

        if (!$parsed instanceof stdClass) {
            throw new RuntimeException();
        }

        $alg = self::obtainFromParsedString($parsed, 'alg');
        $kid = self::obtainFromParsedStringNull($parsed, 'kid');

        return new self(
            $alg,
            $kid,
            get_object_vars($parsed)
        );
    }

    /** @noinspection PhpSameParameterValueInspection */
    private static function obtainFromParsedString(stdClass $parsed, string $name): string
    {
        $value = $parsed->$name ?? null;

        if (!is_string($value)) {
            throw new RuntimeException("No or bad `$name` in JWT header.");
        }

        return $value;
    }

    /** @noinspection PhpSameParameterValueInspection */
    private static function obtainFromParsedStringNull(stdClass $parsed, string $name): ?string
    {
        $value = $parsed->$name ?? null;

        if ($value !== null && !is_string($value)) {
            throw new RuntimeException("Bad `$name` in JWT header.");
        }

        return $value;
    }

    public function getAlg(): string
    {
        return $this->alg;
    }

    public function getKid(): ?string
    {
        return $this->kid;
    }
}
