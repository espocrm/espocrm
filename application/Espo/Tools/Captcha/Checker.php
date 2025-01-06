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

namespace Espo\Tools\Captcha;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Utils\Json;
use Espo\Core\Utils\Log;
use Espo\Entities\Integration;
use Espo\ORM\EntityManager;
use RuntimeException;

class Checker
{
    private const URL = 'https://www.google.com/recaptcha/api/siteverify';
    private const SCORE_THRESHOLD = 0.2;
    private const TIMEOUT = 20;

    public function __construct(
        private EntityManager $entityManager,
        private Log $log,
    ) {}

    /**
     * @throws BadRequest
     * @throws Forbidden
     */
    public function check(string $token, string $action): void
    {
        [$secret, $scoreThreshold] = $this->getCaptchaSecretKey();

        if ($secret && $token === '') {
            throw new BadRequest("No captcha token.");
        }

        if (!$secret) {
            throw new Forbidden("Captcha not configured.");
        }

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, self::URL);
        curl_setopt($ch, CURLOPT_POST, true);

        $data = [
            'secret' => $secret,
            'response' => $token,
        ];

        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, self::TIMEOUT);

        $response = curl_exec($ch);

        curl_close($ch);

        if (!is_string($response)) {
            throw new RuntimeException("Bad CURL response.");
        }

        $responseData = Json::decode($response, true);

        if (!is_array($responseData)) {
            throw new RuntimeException("Bad response from ReCaptcha.");
        }

        $success = $responseData['success'] ?? null;
        $score = $responseData['score'] ?? null;
        $resultAction = $responseData['action'] ?? null;

        if (!$success) {
            $this->log->error("Captcha error; action: {action}; response: {response}", [
                'action' => $action,
                'response' => $response,
            ]);

            throw new Forbidden("ReCaptcha error.");
        }

        if (!is_string($resultAction)) {
            throw new RuntimeException("No or bad action in ReCaptcha response.");
        }

        if (!is_int($score) && !is_float($score)) {
            throw new RuntimeException("No score in ReCaptcha response.");
        }

        if ($action !== $resultAction) {
            throw new Forbidden("ReCaptcha action mismatch.");
        }

        if ($score < $scoreThreshold) {
            throw new Forbidden("ReCaptcha low score.");
        }
    }

    /**
     * @return array{?string, ?int}
     */
    private function getCaptchaSecretKey(): array
    {
        $entity = $this->entityManager
            ->getRepositoryByClass(Integration::class)
            ->getById('GoogleReCaptcha');

        if (!$entity) {
            return [null, null];
        }

        $secretKey = $entity->get('secretKey');
        $scoreThreshold = $entity->get('scoreThreshold') ?? self::SCORE_THRESHOLD;

        if (!$secretKey) {
            return [null, null];
        }

        return [$secretKey, $scoreThreshold];
    }
}
