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

namespace Espo\Core\Authentication\Oidc;

use Espo\Core\Authentication\Jwt\SignatureVerifier;
use Espo\Core\Authentication\Jwt\SignatureVerifierFactory;
use Espo\Core\Authentication\Jwt\SignatureVerifiers\Hmac;
use Espo\Core\Authentication\Jwt\SignatureVerifiers\Rsa;
use RuntimeException;

class DefaultSignatureVerifierFactory implements SignatureVerifierFactory
{
    private const RS256 = 'RS256';
    private const RS384 = 'RS384';
    private const RS512 = 'RS512';
    private const HS256 = 'HS256';
    private const HS384 = 'HS384';
    private const HS512 = 'HS512';

    private const ALGORITHM_VERIFIER_CLASS_NAME_MAP = [
        self::RS256 => Rsa::class,
        self::RS384 => Rsa::class,
        self::RS512 => Rsa::class,
        self::HS256 => Hmac::class,
        self::HS384 => Hmac::class,
        self::HS512 => Hmac::class,
    ];

    public function __construct(
        private KeysProvider $keysProvider,
        private ConfigDataProvider $configDataProvider
    ) {}

    public function create(string $algorithm): SignatureVerifier
    {
        /** @var ?class-string<SignatureVerifier> $className */
        $className = self::ALGORITHM_VERIFIER_CLASS_NAME_MAP[$algorithm] ?? null;

        if (!$className) {
            throw new RuntimeException("Not supported algorithm $algorithm.");
        }

        if ($className === Rsa::class) {
            $keys = $this->keysProvider->get();

            return new Rsa($algorithm, $keys);
        }

        if ($className === Hmac::class) {
            $key = $this->configDataProvider->getClientSecret();

            if (!$key) {
                throw new RuntimeException("No client secret.");
            }

            return new Hmac($algorithm, $key);
        }

        throw new RuntimeException();
    }
}
