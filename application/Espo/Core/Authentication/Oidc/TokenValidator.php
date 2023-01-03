<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2023 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Core\Authentication\Oidc;

use Espo\Core\Authentication\Jwt\Exceptions\Invalid;
use Espo\Core\Authentication\Jwt\Exceptions\SignatureNotVerified;
use Espo\Core\Authentication\Jwt\SignatureVerifierFactory;
use Espo\Core\Authentication\Jwt\Token;
use Espo\Core\Utils\Config;
use RuntimeException;

class TokenValidator
{
    private Config $config;
    private SignatureVerifierFactory $signatureVerifierFactory;

    public function __construct(
        Config $config,
        SignatureVerifierFactory $signatureVerifierFactory
    ) {
        $this->config = $config;
        $this->signatureVerifierFactory = $signatureVerifierFactory;
    }

    /**
     * @throws SignatureNotVerified
     * @throws Invalid
     */
    public function validateSignature(Token $token): void
    {
        $algorithm = $token->getHeader()->getAlg();

        /** @var string[] $allowedAlgorithmList */
        $allowedAlgorithmList = $this->config->get('oidcJwtSignatureAlgorithmList') ?? [];

        if (!in_array($algorithm, $allowedAlgorithmList)) {
            throw new Invalid("JWT signing algorithm `{$algorithm}` not allowed.");
        }

        $verifier = $this->signatureVerifierFactory->create($algorithm);

        if (!$verifier->verify($token)) {
            throw new SignatureNotVerified("JWT signature not verified.");
        }
    }

    /**
     * @throws Invalid
     */
    public function validateFields(Token $token): void
    {
        /** @var ?string $oidcClientId */
        $oidcClientId = $this->config->get('oidcClientId');

        if (!$oidcClientId) {
            throw new RuntimeException("OIDC: No client ID.");
        }

        if (!in_array($oidcClientId, $token->getPayload()->getAud())) {
            throw new Invalid("JWT the `aud` field does not contain matching client ID.");
        }

        if (!$token->getPayload()->getSub()) {
            throw new Invalid("JWT does not contain the `sub` value.");
        }

        if (!$token->getPayload()->getIss()) {
            throw new Invalid("JWT does not contain the `iss` value.");
        }
    }
}
