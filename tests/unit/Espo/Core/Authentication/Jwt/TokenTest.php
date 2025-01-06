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

namespace tests\unit\Espo\Core\Authentication\Jwt;

use Espo\Core\Authentication\Jwt\DefaultKeyFactory;
use Espo\Core\Authentication\Jwt\Key;
use Espo\Core\Authentication\Jwt\SignatureVerifiers\Hmac;
use Espo\Core\Authentication\Jwt\SignatureVerifiers\Rsa;
use Espo\Core\Authentication\Jwt\Token;
use phpseclib3\Common\Functions\Strings;
use PHPUnit\Framework\TestCase;

class TokenTest extends TestCase
{
    public function testToken1(): void
    {
        $raw = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibm" .
            "FtZSI6IkpvaG4gRG9lIiwiaWF0IjoxNTE2MjM5MDIyfQ.cThIIoDvwdueQB468K5xDc5633seEFoqwxjF_xSJyQQ";

        $token = Token::create($raw);

        $this->assertEquals('HS256', $token->getHeader()->getAlg());
        $this->assertEquals('1234567890', $token->getPayload()->getSub());
        $this->assertEquals('John Doe', $token->getPayload()->get('name'));
    }

    public function testToken2(): void
    {
        $raw = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0Ij" .
            "oxNTE2MjM5MDIyLCJleHAiOjE1MTYyMzgwMjIsIm5iZiI6MTUxNjIzODAyMywiYXV0aF90aW1lIjoiMTUxNjIzODAyNCJ9." .
            "7Z45aUyLR9o8lUaIvxkO7SzOhTaXgfXf_rEFYnvTPL8";

        $token = Token::create($raw);

        $this->assertEquals(1516238022, $token->getPayload()->getExp());
        $this->assertEquals(1516239022, $token->getPayload()->getIat());
        $this->assertEquals(1516238023, $token->getPayload()->getNbf());
        $this->assertEquals(1516238024, $token->getPayload()->getAuthTime());
    }

    public function testVerifySignatureHS256(): void
    {
        $raw = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9." .
            "eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9lIiwiaWF0" .
            "IjoxNTE2MjM5MDIyfQ.S2ZL7D-D3VeduQ44Cy2qLRFxHV43gRGSZtlfJ2MJ57g";

        $token = Token::create($raw);

        $this->assertEquals('HS256', $token->getHeader()->getAlg());
        $this->assertEquals('1234567890', $token->getPayload()->getSub());

        $verifier1 = new Hmac('HS256', '123456789');
        $this->assertTrue($verifier1->verify($token));

        $verifier2 = new Hmac('HS256', '0000000000');
        $this->assertFalse($verifier2->verify($token));

        $verifier3 = new Hmac('HS512', '123456789');
        $this->assertFalse($verifier3->verify($token));
    }

    public function testVerifySignatureRS256(): void
    {
        $raw =
            "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCIsImtpZCI6IjAwMSJ9.eyJzdWIiOiIxMjM0NTY3ODkwIiwibmFtZSI6IkpvaG4gRG9l" .
            "IiwiaWF0IjoxNTE2MjM5MDIyfQ.ZIOKC5KV6E1omC_KnQHgG5h9Z8G3g8jc1uUiI0RkQITAM-oS_3qivauy5jJnMX_N9-Bz2ZZSQ6" .
            "4AA_LbELCSoLnQ4HJBRzK8XfNeTVwhebjelUc8b_qcWC16lUWydd1GFYQQYbPfis_EN0UNM5TvfPpZ24YPujxJqZ0vrOOM-T6U73PtQ" .
            "TLfErmdO8cd_drHc75lSmWvXjpRl7zAj9vGhO_nJRh3-tPGprkFMvC4FLWOF5L_4eS3bJhhj7GUxyYYNi2ATbO7SRCPUp2Ck_aiJNlbS" .
            "Vdhbt_2Ls8VGnSPDdPf9UDUiXqYadueqmyrRucbBNHYn46cehnuONJa3gMaSA";

        $token = Token::create($raw);

        $pem =
            "-----BEGIN PUBLIC KEY-----\r\n" .
            "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAu1SU1LfVLPHCozMxH2Mo\r\n" .
            "4lgOEePzNm0tRgeLezV6ffAt0gunVTLw7onLRnrq0/IzW7yWR7QkrmBL7jTKEn5u\r\n" .
            "+qKhbwKfBstIs+bMY2Zkp18gnTxKLxoS2tFczGkPLPgizskuemMghRniWaoLcyeh\r\n" .
            "kd3qqGElvW/VDL5AaWTg0nLVkjRo9z+40RQzuVaE8AkAFmxZzow3x+VJYKdjykkJ\r\n" .
            "0iT9wCS0DRTXu269V264Vf/3jvredZiKRkgwlL9xNAwxXFg0x/XFw005UWVRIkdg\r\n" .
            "cKWTjpBP2dPwVZ4WWC+9aGVd+Gyn1o0CLelf4rEjGoXbAAEgAqeGUxrcIlbjXfbc\r\n" .
            "mwIDAQAB\r\n" .
            "-----END PUBLIC KEY-----\r\n";

        $pk = openssl_pkey_get_public($pem);
        $keyData = openssl_pkey_get_details($pk);

        $rawKey = (object) [
            'kid' => '001',
            'alg' => 'RS256',
            'kty' => 'RSA',
            'n' => Strings::base64url_encode($keyData['rsa']['n']),
            'e' => Strings::base64url_encode($keyData['rsa']['e']),
        ];

        $key = (new DefaultKeyFactory())->create($rawKey);

        $verifier1 = new Rsa('RS256', [$key]);
        $this->assertTrue($verifier1->verify($token));

        $verifier1 = new Rsa('RS256', [$this->createMock(Key::class), $key]);
        $this->assertTrue($verifier1->verify($token));

        $verifier1 = new Rsa('RS512', [$key]);
        $this->assertFalse($verifier1->verify($token));
    }
}
