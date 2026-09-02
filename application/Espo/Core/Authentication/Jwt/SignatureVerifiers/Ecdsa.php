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

namespace Espo\Core\Authentication\Jwt\SignatureVerifiers;

use Espo\Core\Authentication\Jwt\Key;
use Espo\Core\Authentication\Jwt\Keys\Ec as EcKey;
use Espo\Core\Authentication\Jwt\Token;
use Espo\Core\Authentication\Jwt\SignatureVerifier;
use Espo\Core\Authentication\Jwt\Util;
use LogicException;
use RuntimeException;

/**
 * ECDSA signature verifier for ES256, ES384, ES512 JWT algorithms.
 *
 * Uses OpenSSL for EC signature verification.
 */
class Ecdsa implements SignatureVerifier
{
    private const SUPPORTED_ALGORITHM_LIST = [
        self::ES256,
        self::ES384,
        self::ES512,
    ];

    private const ALGORITHM_CURVE_MAP = [
        self::ES256 => 'prime256v1',
        self::ES384 => 'secp384r1',
        self::ES512 => 'secp521r1',
    ];

    private const ALGORITHM_DIGEST_MAP = [
        self::ES256 => 'SHA256',
        self::ES384 => 'SHA384',
        self::ES512 => 'SHA512',
    ];

    private const ES256 = 'ES256';
    private const ES384 = 'ES384';
    private const ES512 = 'ES512';

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

        $curveName = self::ALGORITHM_CURVE_MAP[$this->algorithm] ?? null;
        $digestName = self::ALGORITHM_DIGEST_MAP[$this->algorithm] ?? null;

        if (!$curveName || !$digestName) {
            throw new LogicException();
        }

        $key = array_values(
            array_filter($this->keys, fn ($key) => $key->getKid() === $kid)
        )[0] ?? null;

        if (!$key) {
            return false;
        }

        if (!$key instanceof EcKey) {
            throw new RuntimeException("Wrong key type for ECDSA algorithm.");
        }

        $publicKey = $this->createOpenSslKey($key, $curveName);

        if ($publicKey === false) {
            throw new RuntimeException("Bad EC public key.");
        }

        // ECDSA signatures in JWT are raw (r || s), not DER-encoded.
        // OpenSSL expects DER format, so we need to convert.
        $derSignature = $this->rawToDer($signature, $this->algorithm);

        $result = openssl_verify($input, $derSignature, $publicKey, $digestName);

        if ($result === false) {
            throw new RuntimeException("EC public key verify error: " . openssl_error_string());
        }

        return $result === 1;
    }

    /**
     * Create an OpenSSL public key from EC JWK parameters.
     */
    private function createOpenSslKey(EcKey $key, string $curveName): \OpenSSLAsymmetricKey|false
    {
        $x = Util::base64UrlDecode($key->getX());
        $y = Util::base64UrlDecode($key->getY());

        // Get the curve parameters
        $curve = openssl_get_curve_names();

        if (!in_array($curveName, $curve ?? [])) {
            throw new RuntimeException("Unsupported curve: $curveName.");
        }

        // Build the EC point uncompressed format: 04 || x || y
        $point = "\x04" . $x . $y;

        // Create EC key from explicit parameters
        $ecKey = openssl_pkey_new([
            'curve_name' => $curveName,
            'private_key_bits' => 0,
            'private_key_type' => OPENSSL_KEYTYPE_EC,
        ]);

        // Alternative: use the raw point to create a key
        // We'll use a simpler approach with phpseclib or direct ASN.1
        $asn1 = $this->buildEcPublicKeyAsn1($x, $y, $curveName);

        if ($asn1 === null) {
            throw new RuntimeException("Failed to build EC public key ASN.1 structure.");
        }

        return openssl_pkey_get_public($asn1);
    }

    /**
     * Build ASN.1 DER-encoded EC public key from x, y coordinates.
     *
     * @return ?string PEM-encoded public key
     */
    private function buildEcPublicKeyAsn1(string $x, string $y, string $curveName): ?string
    {
        // OID for the curve
        $curveOid = $this->getCurveOid($curveName);

        if ($curveOid === null) {
            return null;
        }

        // Build the SubjectPublicKeyInfo ASN.1 structure
        // This is a simplified approach — for production, consider using phpseclib

        $ecPoint = "\x04" . $x . $y;

        // ECPublicKey ASN.1: SEQUENCE { SEQUENCE { OID ecPublicKey, OID curve }, BIT STRING { point } }
        $algorithm = $this->buildAlgorithmSequence($curveOid);
        $subjectPublicKey = $this->buildBitString($ecPoint);

        $subjectPublicKeyInfo = $this->buildSequence($algorithm . $subjectPublicKey);

        // Convert to PEM
        $der = $this->buildSEQUENCE([
            $this->buildAlgorithmSequence("\x06\x08\x2a\x86\x48\xce\x3d\x02\x01"),  // RSA OID (for PEM header)
            $this->buildBitString(''),
        ]);

        // Use a simpler approach: build the full DER manually
        $derHex = $this->buildSpkiDer($x, $y, $curveName);

        if ($derHex === null) {
            return null;
        }

        $der = hex2bin($derHex);

        return "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END PUBLIC KEY-----\n";
    }

    /**
     * Build SubjectPublicKeyInfo DER for EC key.
     *
     * @return ?string hex-encoded DER
     */
    private function buildSpkiDer(string $x, string $y, string $curveName): ?string
    {
        $curveOid = $this->getCurveOidHex($curveName);

        if ($curveOid === null) {
            return null;
        }

        // Uncompressed point
        $point = "04" . bin2hex($x) . bin2hex($y);
        $pointLen = intdiv(strlen($point), 2);

        // BIT STRING: 03 || length || 00 || point
        $bitString = "03" . $this->encodeLength($pointLen + 1) . "00" . $point;

        // AlgorithmIdentifier: 30 || length || 06 || 07 || 2a8648ce3d0201 || 06 || length || curveOid
        $algorithmId = "30" . $this->encodeLength(2 + 7 + 2 + intdiv(strlen($curveOid), 2))
            . "06" . "07" . "2a8648ce3d0201"
            . "06" . $this->encodeLength(intdiv(strlen($curveOid), 2)) . $curveOid;

        // SubjectPublicKeyInfo: 30 || length || algorithmId || bitString
        $spki = "30" . $this->encodeLength(intdiv(strlen($algorithmId), 2) + intdiv(strlen($bitString), 2))
            . $algorithmId . $bitString;

        return $spki;
    }

    /**
     * Convert raw ECDSA signature (r || s) to DER format.
     */
    private function rawToDer(string $rawSignature, string $algorithm): string
    {
        $keySize = $this->getKeySize($algorithm);
        $halfSize = intdiv($keySize, 2);

        if (strlen($rawSignature) !== $keySize) {
            throw new RuntimeException("Invalid ECDSA signature length.");
        }

        $r = substr($rawSignature, 0, $halfSize);
        $s = substr($rawSignature, $halfSize);

        // Remove leading zeros
        $r = ltrim($r, "\x00");
        $s = ltrim($s, "\x00");

        // Add leading zero if high bit is set (negative number in ASN.1)
        if (ord($r[0]) >= 0x80) {
            $r = "\x00" . $r;
        }

        if (ord($s[0]) >= 0x80) {
            $s = "\x00" . $s;
        }

        // Build INTEGER tags
        $rDer = "02" . $this->encodeLength(strlen($r)) . bin2hex($r);
        $sDer = "02" . $this->encodeLength(strlen($s)) . bin2hex($s);

        // SEQUENCE { INTEGER r, INTEGER s }
        $derHex = "30" . $this->encodeLength(intdiv(strlen($rDer . $sDer), 2)) . $rDer . $sDer;

        return hex2bin($derHex);
    }

    /**
     * Get key size in bytes for the algorithm.
     */
    private function getKeySize(string $algorithm): int
    {
        return match ($algorithm) {
            self::ES256 => 64,
            self::ES384 => 96,
            self::ES512 => 132,
            default => throw new RuntimeException("Unknown algorithm."),
        };
    }

    /**
     * Get curve OID hex for the curve name.
     */
    private function getCurveOidHex(string $curveName): ?string
    {
        return match ($curveName) {
            'prime256v1' => '2a8648ce3d030107',   // P-256
            'secp384r1' => '2b81040022',           // P-384
            'secp521r1' => '2b81040023',           // P-521
            default => null,
        };
    }

    /**
     * Encode ASN.1 length.
     */
    private function encodeLength(int $length): string
    {
        if ($length < 0x80) {
            return sprintf('%02x', $length);
        }

        $temp = ltrim(pack('N', $length), chr(0));

        return sprintf('%02x', 0x80 | strlen($temp)) . bin2hex($temp);
    }
}
