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

use Espo\Core\Authentication\Jwt\Key;
use Espo\Core\Authentication\Jwt\Keys\Rsa as RsaKey;
use Espo\Core\Authentication\Jwt\Token;
use Espo\Core\Authentication\Jwt\SignatureVerifier;
use Espo\Core\Authentication\Jwt\Util;
use LogicException;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Math\BigInteger;
use RuntimeException;

class Rsa implements SignatureVerifier
{
    private const SUPPORTED_ALGORITHM_LIST = [
        self::RS256,
        self::RS384,
        self::RS512,
    ];

    private const ALGORITHM_MAP = [
        self::RS256 => 'SHA256',
        self::RS384 => 'SHA384',
        self::RS512 => 'SHA512',
    ];

    private const RS256 = 'RS256';
    private const RS384 = 'RS384';
    private const RS512 = 'RS512';

    private string $algorithm;
    /** @var Key[] */
    private array $keys;

    /**
     * @param Key[] $keys
     */
    public function __construct(string $algorithm, array $keys)
    {
        $this->algorithm = $algorithm;
        $this->keys = $keys;

        if (!in_array($algorithm, self::SUPPORTED_ALGORITHM_LIST)) {
            throw new RuntimeException("Unsupported algorithm $algorithm.");
        }
    }

    public function verify(Token $token): bool
    {
        $input = $token->getSigningInput();
        $signature = $token->getSignature();
        $kid = $token->getHeader()->getKid();

        $functionAlgorithm = self::ALGORITHM_MAP[$this->algorithm] ?? null;

        if (!$functionAlgorithm) {
            throw new LogicException();
        }

        $key = array_values(
            array_filter($this->keys, fn ($key) => $key->getKid() === $kid)
        )[0] ?? null;

        if (!$key) {
            return false;
        }

        if (!$key instanceof RsaKey) {
            throw new RuntimeException("Wrong key.");
        }

        $publicKey = openssl_pkey_get_public($this->getPemFromKey($key));

        if ($publicKey === false) {
            throw new RuntimeException("Bad RSA public key.");
        }

        $result = openssl_verify($input, $signature, $publicKey, $functionAlgorithm);

        if ($result === false) {
            throw new RuntimeException("RSA public key verify error: " . openssl_error_string());
        }

        return $result === 1;
    }

    private function getPemFromKey(RsaKey $key): string
    {
        $publicKey = PublicKeyLoader::load([
            'n' => new BigInteger('0x' . bin2hex(Util::base64UrlDecode($key->getN())), 16),
            'e' => new BigInteger('0x' . bin2hex(Util::base64UrlDecode($key->getE())), 16),
        ]);

        $pem = $publicKey->toString('PKCS8');

        if (!is_string($pem)) {
            throw new RuntimeException();
        }

        return $pem;
    }
}
