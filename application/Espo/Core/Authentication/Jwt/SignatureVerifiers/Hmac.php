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

namespace Espo\Core\Authentication\Jwt\SignatureVerifiers;

use Espo\Core\Authentication\Jwt\Token;
use Espo\Core\Authentication\Jwt\SignatureVerifier;
use LogicException;
use RuntimeException;

class Hmac implements SignatureVerifier
{
    private const SUPPORTED_ALGORITHM_LIST = [
        self::HS256,
        self::HS384,
        self::HS512,
    ];

    private const ALGORITHM_MAP = [
        self::HS256 => 'SHA256',
        self::HS384 => 'SHA384',
        self::HS512 => 'SHA512',
    ];

    private const HS256 = 'HS256';
    private const HS384 = 'HS384';
    private const HS512 = 'HS512';

    private string $algorithm;
    private string $key;

    public function __construct(
        string $algorithm,
        string $key
    ) {
        $this->algorithm = $algorithm;
        $this->key = $key;

        if (!in_array($algorithm, self::SUPPORTED_ALGORITHM_LIST)) {
            throw new RuntimeException("Unsupported algorithm $algorithm.");
        }
    }

    public function verify(Token $token): bool
    {
        $input = $token->getSigningInput();
        $signature = $token->getSignature();

        $functionAlgorithm = self::ALGORITHM_MAP[$this->algorithm] ?? null;

        if (!$functionAlgorithm) {
            throw new LogicException();
        }

        $hash = hash_hmac($functionAlgorithm, $input, $this->key, true);

        return $hash === $signature;
    }
}
