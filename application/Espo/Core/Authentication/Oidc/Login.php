<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

use Espo\Core\Api\Request;
use Espo\Core\Authentication\Login as LoginInterface;
use Espo\Core\Authentication\Login\Data;
use Espo\Core\Authentication\Jwt\Token;
use Espo\Core\Authentication\Logins\Espo;
use Espo\Core\Authentication\Jwt\Exceptions\Invalid;
use Espo\Core\Authentication\Jwt\Exceptions\SignatureNotVerified;
use Espo\Core\Authentication\Jwt\Validator;
use Espo\Core\Authentication\Result;
use Espo\Core\Authentication\Result\FailReason;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Json;
use Espo\Core\Utils\Log;
use JsonException;
use LogicException;
use RuntimeException;
use stdClass;

class Login implements LoginInterface
{
    public const NAME = 'Oidc';

    private const OIDC_USERNAME = '**oidc';
    private const REQUEST_TIMEOUT = 10;
    private const NONCE_HEADER = 'X-Oidc-Authorization-Nonce';

    public function __construct(
        private Espo $espoLogin,
        private Config $config,
        private Log $log,
        private Validator $validator,
        private TokenValidator $tokenValidator,
        private UserProvider $userProvider
    ) {}

    public function login(Data $data, Request $request): Result
    {
        if ($data->getUsername() !== self::OIDC_USERNAME) {
            return $this->loginFallback($data, $request);
        }

        $code = $data->getPassword();

        if (!$code) {
            return Result::fail(FailReason::NO_PASSWORD);
        }

        return $this->loginWithCode($code, $request);
    }

    private function loginWithCode(string $code, Request $request): Result
    {
        /** @var ?string $endpoint */
        $endpoint = $this->config->get('oidcTokenEndpoint');
        /** @var ?string $clientId */
        $clientId = $this->config->get('oidcClientId');
        $redirectUri = rtrim($this->config->get('siteUrl'), '/') . '/oauth-callback.php';

        if (!$endpoint) {
            throw new RuntimeException("No token endpoint.");
        }

        if (!$clientId) {
            throw new RuntimeException("No client ID.");
        }

        [$rawToken, $failResult] = $this->requestToken($endpoint, $clientId, $code, $redirectUri);

        if ($failResult) {
            return $failResult;
        }

        if (!$rawToken) {
            throw new LogicException();
        }

        try {
            $token = Token::create($rawToken);
        }
        catch (RuntimeException $e) {
            $message = self::composeLogMessage('JWT parsing error.');

            if ($e->getMessage()) {
                $message .= " " . $e->getMessage();
            }

            $this->log->error($message);

            throw new RuntimeException("JWT parsing error.");
        }

        $this->log->debug("OIDC: JWT header: " . $token->getHeaderRaw());
        $this->log->debug("OIDC: JWT payload: " . $token->getPayloadRaw());

        try {
            $this->validateToken($token);
        }
        catch (Invalid $e) {
            $this->log->error("OIDC: " . $e->getMessage());

            return Result::fail(FailReason::DENIED);
        }

        $tokenPayload = $token->getPayload();

        $nonce = $request->getHeader(self::NONCE_HEADER);

        if ($nonce && $nonce !== $tokenPayload->getNonce()) {
            $this->log->warning(self::composeLogMessage('JWT nonce mismatch.'));

            return Result::fail(FailReason::DENIED);
        }

        $user = $this->userProvider->get($tokenPayload);

        if (!$user) {
            return Result::fail(FailReason::USER_NOT_FOUND);
        }

        return Result::success($user);
    }

    private function loginFallback(Data $data, Request $request): Result
    {
        if (
            !$data->getAuthToken() &&
            !$this->config->get('oidcFallback')
        ) {
            return Result::fail(FailReason::METHOD_NOT_ALLOWED);
        }

        $result = $this->espoLogin->login($data, $request);

        $user = $result->getUser();

        if (
            !$data->getAuthToken() &&
            $user &&
            $user->isRegular() &&
            !$this->config->get('oidcAllowRegularUserFallback')
            // Portal users are allowed.
        ) {
            return Result::fail(FailReason::METHOD_NOT_ALLOWED);
        }

        return $result;
    }

    /**
     * @return array{?string, ?Result}
     */
    private function requestToken(string $endpoint, string $clientId, string $code, string $redirectUri): array
    {
        $params = [
            'grant_type' => 'authorization_code',
            'client_id' => $clientId,
            'code' => $code,
            'redirect_uri' => $redirectUri,
        ];

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => self::REQUEST_TIMEOUT,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => http_build_query($params),
            CURLOPT_HTTPHEADER => ['content-type: application/x-www-form-urlencoded'],
        ]);

        /** @var string|false $response */
        $response = curl_exec($curl);
        $error = curl_error($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if ($response === false) {
            $response = '';
        }

        if ($error || is_int($status) && ($status >= 400 && $status < 500)) {
            if ($status === 400) {
                $this->log->error(self::composeLogMessage('Bad token request.', $status, $response));

                throw new RuntimeException();
            }

            $this->log->warning(self::composeLogMessage('Token request error.', $status, $response));

            return [null, Result::fail(FailReason::DENIED)];
        }

        $parsedResponse = null;

        try {
            $parsedResponse = Json::decode($response);
        }
        catch (JsonException $e) {}

        if (!$parsedResponse instanceof stdClass) {
            $this->log->error(self::composeLogMessage('Bad token response.', $status, $response));

            throw new RuntimeException();
        }

        $token = $parsedResponse->id_token ?? null;

        if (!$token || !is_string($token)) {
            $this->log->error(self::composeLogMessage('Bad token response.', $status, $response));

            throw new RuntimeException();
        }

        return [$token, null];
    }

    private static function composeLogMessage(string $text, ?int $status = null, ?string $response = null): string
    {
        if ($status === null) {
            return "OIDC: {$text}";
        }

        return "OIDC: {$text}; Status: {$status}; Response: {$response}";
    }

    /**
     * @throws SignatureNotVerified
     * @throws Invalid
     */
    private function validateToken(Token $token): void
    {
        $this->validator->validate($token);
        $this->tokenValidator->validateFields($token);
        $this->tokenValidator->validateSignature($token);
    }
}
