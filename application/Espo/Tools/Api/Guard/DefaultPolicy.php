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
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Tools\Api\Guard;

/**
 * The policy applied when `guardEnabled` is true: passive mode, screening on, and route policies for the
 * endpoints that are internet facing or credential related. Operator config overrides it key by key.
 *
 * Redis is off by default, and without it route rate limits, behavior rules and auto-bans keep state per request only,
 * so under PHP-FPM they never trigger across requests until `enableRedis` is set.
 */
class DefaultPolicy
{
    private const string API_PREFIX = '/api/v1/';

    /**
     * @param array<string, mixed> $operatorSettings
     * @return array<string, mixed>
     */
    public function merge(array $operatorSettings): array
    {
        $settings = array_replace($this->getSettings(), $operatorSettings);

        $operatorRoutes = $operatorSettings['routes'] ?? [];

        if (!is_array($operatorRoutes)) {
            throw new \InvalidArgumentException("Guard config 'routes' must be a map of route key to options.");
        }

        // A null route entry switches a default route off.
        $settings['routes'] = array_filter(
            array_replace($this->getRoutes(), $operatorRoutes),
            fn ($route) => $route !== null
        );

        return $settings;
    }

    /**
     * @return array<string, mixed>
     */
    private function getSettings(): array
    {
        return [
            'passiveMode' => true,
            'enableRedis' => false,
            // Route limits only apply while rate limiting is on, so the global per-IP limit is set out of reach.
            'enableRateLimiting' => true,
            'rateLimit' => 10000,
            'rateLimitWindow' => 60,
            'enablePenetrationDetection' => true,
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function getRoutes(): array
    {
        return [
            'GET ' . self::API_PREFIX . 'App/user' => ['behaviorRules' => [$this->getRepeatedUnauthorizedRule()]],
            self::API_PREFIX => ['behaviorRules' => [$this->getRepeatedUnauthorizedRule()]],
            'POST ' . self::API_PREFIX . 'Attachment/chunk/' => [
                'detectionScanBody' => false,
                'behaviorRules' => [$this->getRepeatedUnauthorizedRule()],
            ],
            'POST ' . self::API_PREFIX . 'LeadCapture/' => ['rateLimit' => 10, 'rateLimitWindow' => 60],
            'POST ' . self::API_PREFIX . 'User/passwordChangeRequest' => ['rateLimit' => 5, 'rateLimitWindow' => 300],
            'POST ' . self::API_PREFIX . 'User/changePasswordByRequest' => ['rateLimit' => 5, 'rateLimitWindow' => 300],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getRepeatedUnauthorizedRule(): array
    {
        return [
            'rule_type' => 'return_pattern',
            'pattern' => 'status:401',
            'threshold' => 10,
            'window' => 300,
            'action' => 'ban',
            'ban_duration' => 3600,
        ];
    }
}
