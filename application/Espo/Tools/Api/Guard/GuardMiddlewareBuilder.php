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

use Closure;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Log;
use InvalidArgumentException;
use RenzoFranceschini\GuardCore\Cloud\CurlHttpClient;
use RenzoFranceschini\GuardCore\Config\SecurityConfig;
use RenzoFranceschini\GuardCore\Engine\GuardEngine;
use RenzoFranceschini\GuardCore\GeoIp\IpInfoManager;
use RenzoFranceschini\GuardCore\Redis\RedisHandler;
use RenzoFranceschini\GuardCorePsr15\GuardMiddleware;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\StreamFactory;

/**
 * Wires the engine to EspoCRM: its logger, the Redis connection, the optional country database and the optional
 * telemetry agent.
 */
class GuardMiddlewareBuilder
{
    private const string DEFAULT_CLIENT_ADDRESS_PARAM = 'REMOTE_ADDR';

    public function __construct(
        private Config $config,
        private Log $log,
        private SecurityConfigBuilder $securityConfigBuilder,
        private RouteConfigBuilder $routeConfigBuilder,
        private GuardAgentFactory $agentFactory,
    ) {}

    /**
     * @param array<string, mixed> $settings Settings merged with the default policy.
     */
    public function build(array $settings): GuardMiddleware
    {
        $this->warnWhenClientAddressIsNotRemoteAddr();

        $agent = $this->buildAgent($settings);

        if ($agent) {
            $settings['enableAgent'] ??= true;
        }

        $securityConfig = $this->securityConfigBuilder->build(
            $settings,
            $this->buildCountryResolver($settings),
            $this->createOnBlockClosure(),
        );

        $engine = new GuardEngine(
            config: $securityConfig,
            redis: $this->buildRedis($securityConfig),
            log: $this->createLogClosure(),
            warn: fn (string $message) => $this->log->warning($message),
        );

        return new GuardMiddleware(
            engine: $engine,
            responseFactory: new ResponseFactory(),
            streamFactory: new StreamFactory(),
            routes: $this->routeConfigBuilder->buildMap($settings['routes'] ?? []),
            agentHandler: $agent,
        );
    }

    private function warnWhenClientAddressIsNotRemoteAddr(): void
    {
        $param = $this->config->get('ipAddressServerParam') ?? self::DEFAULT_CLIENT_ADDRESS_PARAM;

        if ($param === self::DEFAULT_CLIENT_ADDRESS_PARAM) {
            return;
        }

        $this->log->warning(
            "Guard: ipAddressServerParam is '$param', but the guard identifies the client by REMOTE_ADDR only. " .
            "Set guard.trustedProxies (and guard.trustedProxyDepth) to resolve the client behind a proxy."
        );
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function buildAgent(array $settings): ?object
    {
        $agentSettings = $settings['agent'] ?? null;

        if ($agentSettings === null) {
            return null;
        }

        if (!is_array($agentSettings)) {
            throw new InvalidArgumentException("Guard config 'agent' must be a map of agent settings.");
        }

        return $this->agentFactory->create($agentSettings);
    }

    private function buildRedis(SecurityConfig $config): RedisHandler
    {
        $url = $this->parseRedisUrl($config->redisUrl);

        return new RedisHandler(
            enableRedis: $config->enableRedis,
            prefix: $config->redisPrefix,
            host: $url['host'] ?? '127.0.0.1',
            port: $url['port'] ?? 6379,
            retries: $config->redisRetries,
            healthCheckInterval: $config->redisHealthCheckInterval,
            maxConnections: $config->redisMaxConnections,
        );
    }

    /**
     * The engine connects by host and port only, so any other part of the URL is rejected rather than ignored.
     *
     * @return array{host?: string, port?: int}
     */
    private function parseRedisUrl(string $redisUrl): array
    {
        $url = parse_url($redisUrl);

        if ($url === false || ($url['scheme'] ?? null) !== 'redis' || !isset($url['host'])) {
            throw new InvalidArgumentException("Guard redisUrl must be redis://host[:port], got '$redisUrl'.");
        }

        foreach (['pass' => 'a password', 'user' => 'a user', 'query' => 'a query', 'fragment' => 'a fragment'] as $part => $name) {
            if (isset($url[$part])) {
                throw new InvalidArgumentException("Guard redisUrl with $name is not supported, use redis://host[:port].");
            }
        }

        if (isset($url['path']) && $url['path'] !== '' && $url['path'] !== '/') {
            throw new InvalidArgumentException("Guard redisUrl with a database index is not supported, use redis://host[:port].");
        }

        return $url;
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function buildCountryResolver(array $settings): ?IpInfoManager
    {
        $ipinfo = $settings['ipinfo'] ?? null;

        if ($ipinfo === null) {
            return null;
        }

        if (!is_array($ipinfo) || !is_string($ipinfo['token'] ?? null)) {
            throw new InvalidArgumentException("Guard config 'ipinfo' needs a 'token' string.");
        }

        $resolver = new IpInfoManager(
            token: $ipinfo['token'],
            dbPath: $ipinfo['dbPath'] ?? 'data/ipinfo/country_asn.mmdb',
            maxAge: $ipinfo['maxAge'] ?? 86400,
            httpClient: new CurlHttpClient(),
        );

        $resolver->initialize();

        return $resolver;
    }

    /**
     * The engine reports blocks, and in passive mode what it would have blocked, only through this hook.
     */
    private function createOnBlockClosure(): Closure
    {
        return function (object $request, array $payload): void {
            $verb = $payload['passive_mode'] ? 'would block' : 'blocked';

            $this->log->warning(
                "Guard $verb by {$payload['check_name']}: {$payload['reason']} {$payload['trigger_info']}",
                [
                    'clientIp' => $payload['client_ip'],
                    'method' => $payload['method'],
                    'path' => $payload['path'],
                    'status' => $payload['status_code'],
                ]
            );
        };
    }

    private function createLogClosure(): Closure
    {
        return fn (string $level, string $message, array $context) =>
            $this->log->log(strtolower($level), $message, $context);
    }
}
