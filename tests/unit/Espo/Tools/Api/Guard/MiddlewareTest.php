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

namespace tests\unit\Espo\Tools\Api\Guard;

use Espo\Core\Utils\Config;
use Espo\Core\Utils\Log;
use Espo\Tools\Api\Guard\DefaultPolicy;
use Espo\Tools\Api\Guard\GuardAgentFactory;
use Espo\Tools\Api\Guard\GuardMiddlewareBuilder;
use Espo\Tools\Api\Guard\Middleware;
use Espo\Tools\Api\Guard\RouteConfigBuilder;
use Espo\Tools\Api\Guard\SecurityConfigBuilder;
use InvalidArgumentException;
use Monolog\Handler\TestHandler;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\StreamFactory;

class MiddlewareTest extends TestCase
{
    private const string CLIENT_IP = '203.0.113.5';

    private const int REDIS_PORT = 6420;

    private TestHandler $logHandler;

    private int $handlerCalls = 0;

    private ?string $receivedBody = null;

    protected function setUp(): void
    {
        $this->logHandler = new TestHandler();
    }

    public function testDisabledPassesThroughAndBuildsNothing(): void
    {
        $builder = $this->createMock(GuardMiddlewareBuilder::class);
        $builder->expects(self::never())->method('build');

        $middleware = new Middleware($this->createConfig(['guardEnabled' => false]), new DefaultPolicy(), $builder);

        $request = $this->createRequest('GET', "/api/v1/App/user?q=1' OR '1'='1' UNION SELECT 1--");

        $response = $middleware->process($request, $this->createHandler());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(1, $this->handlerCalls);
    }

    public function testMissingGuardEnabledParamIsDisabled(): void
    {
        $builder = $this->createMock(GuardMiddlewareBuilder::class);
        $builder->expects(self::never())->method('build');

        $middleware = new Middleware($this->createConfig([]), new DefaultPolicy(), $builder);

        $response = $middleware->process($this->createRequest('GET', '/'), $this->createHandler());

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testSqlInjectionInQueryIsBlocked(): void
    {
        $middleware = $this->createMiddleware(['passiveMode' => false]);

        $response = $middleware->process(
            $this->createRequest('GET', "/api/v1/Account?where=1' OR '1'='1' UNION SELECT password FROM user--"),
            $this->createHandler()
        );

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame(0, $this->handlerCalls);
    }

    public function testXssInJsonBodyIsBlocked(): void
    {
        $middleware = $this->createMiddleware(['passiveMode' => false]);

        $request = $this->createRequest('POST', '/api/v1/Note', '{"post":"<script>alert(document.cookie)</script>"}')
            ->withHeader('Content-Type', 'application/json');

        $this->assertSame(400, $middleware->process($request, $this->createHandler())->getStatusCode());
        $this->assertSame(0, $this->handlerCalls);
    }

    public function testPathTraversalIsBlocked(): void
    {
        $middleware = $this->createMiddleware(['passiveMode' => false]);

        $response = $middleware->process(
            $this->createRequest('GET', '/api/v1/Attachment/file?path=../../../../etc/passwd'),
            $this->createHandler()
        );

        $this->assertSame(400, $response->getStatusCode());
    }

    public function testNormalRequestPassesWithBodyIntact(): void
    {
        $middleware = $this->createMiddleware(['passiveMode' => false]);
        $body = '{"name":"Acme","description":"Weekly sync"}';

        $request = $this->createRequest('POST', '/api/v1/Account', $body)
            ->withHeader('Content-Type', 'application/json');

        $response = $middleware->process($request, $this->createHandler());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($body, $this->receivedBody);
    }

    public function testCredentialHeadersPass(): void
    {
        $middleware = $this->createMiddleware(['passiveMode' => false]);

        $request = $this->createRequest('GET', '/api/v1/Account')
            ->withHeader('X-Api-Key', 'c3a9f1d27be84a0c9d6e5f4b3a2c1d0e')
            ->withHeader('X-Hmac-Authorization', 'Hmac QVBJLUtFWToxNzAwMDAwMDAwOnNpZw==')
            ->withHeader('Authorization', 'Basic YWRtaW46cGFzc3dvcmQ=')
            ->withHeader('Espo-Authorization', 'YWRtaW46cGFzc3dvcmQ=');

        $this->assertSame(200, $middleware->process($request, $this->createHandler())->getStatusCode());
    }

    public function testChunkUploadBodyIsNotScanned(): void
    {
        $middleware = $this->createMiddleware(['passiveMode' => false]);

        $request = $this->createRequest('POST', '/api/v1/Attachment/chunk/abc', "<script>alert(1)</script>' OR 1=1 --");

        $this->assertSame(200, $middleware->process($request, $this->createHandler())->getStatusCode());
    }

    public function testPassiveModeLogsAndPasses(): void
    {
        $middleware = $this->createMiddleware([]);

        $response = $middleware->process(
            $this->createRequest('GET', "/api/v1/Account?where=1' OR '1'='1' UNION SELECT password FROM user--"),
            $this->createHandler()
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertTrue($this->logHandler->hasWarningThatContains('Guard would block by suspicious_activity'));
    }

    public function testRouteRateLimitReturns429AfterLimit(): void
    {
        $middleware = $this->createMiddleware([
            'passiveMode' => false,
            'routes' => ['POST /api/v1/LeadCapture/' => ['rateLimit' => 3, 'rateLimitWindow' => 60]],
        ]);

        $request = $this->createRequest('POST', '/api/v1/LeadCapture/key', '{"lastName":"A"}');

        $this->assertSame([200, 200, 200, 429, 429], $this->collectStatuses($middleware, $request, 5));
    }

    public function testDefaultPasswordRouteRateLimit(): void
    {
        $middleware = $this->createMiddleware(['passiveMode' => false]);

        $request = $this->createRequest('POST', '/api/v1/User/passwordChangeRequest', '{"userName":"a"}');

        $this->assertSame([200, 200, 200, 200, 200, 429], $this->collectStatuses($middleware, $request, 6));
    }

    public function testIpDenyListReturns403(): void
    {
        $middleware = $this->createMiddleware(['passiveMode' => false, 'blacklist' => [self::CLIENT_IP]]);

        $response = $middleware->process($this->createRequest('GET', '/api/v1/App/user'), $this->createHandler());

        $this->assertSame(403, $response->getStatusCode());
    }

    public function testIpAllowListBlocksOtherClients(): void
    {
        $middleware = $this->createMiddleware(['passiveMode' => false, 'whitelist' => ['198.51.100.0/24']]);

        $response = $middleware->process($this->createRequest('GET', '/api/v1/App/user'), $this->createHandler());

        $this->assertSame(403, $response->getStatusCode());
    }

    public function testMissingRequiredHeaderReturns400(): void
    {
        $middleware = $this->createMiddleware([
            'passiveMode' => false,
            'routes' => ['/api/v1/Hook/' => ['requiredHeaders' => ['X-Hook-Token' => 'required']]],
        ]);

        $missing = $this->createRequest('POST', '/api/v1/Hook/1', '{}');

        $this->assertSame(400, $middleware->process($missing, $this->createHandler())->getStatusCode());

        $present = $this->createRequest('POST', '/api/v1/Hook/1', '{}')->withHeader('X-Hook-Token', 'abc');

        $this->assertSame(200, $middleware->process($present, $this->createHandler())->getStatusCode());
    }

    public function testOversizeBodyReturns413(): void
    {
        $middleware = $this->createMiddleware([
            'passiveMode' => false,
            'routes' => ['/api/v1/Hook/' => ['maxRequestSize' => 10]],
        ]);

        $request = $this->createRequest('POST', '/api/v1/Hook/1', str_repeat('a', 50))
            ->withHeader('Content-Length', '50');

        $this->assertSame(413, $middleware->process($request, $this->createHandler())->getStatusCode());
    }

    public function testDisallowedContentTypeIsBlocked(): void
    {
        $middleware = $this->createMiddleware([
            'passiveMode' => false,
            'routes' => ['/api/v1/Hook/' => ['allowedContentTypes' => ['application/json']]],
        ]);

        $request = $this->createRequest('POST', '/api/v1/Hook/1', 'x=1')
            ->withHeader('Content-Type', 'text/plain');

        $this->assertSame(415, $middleware->process($request, $this->createHandler())->getStatusCode());
    }

    public function testBlockedUserAgentReturns403(): void
    {
        $middleware = $this->createMiddleware(['passiveMode' => false, 'blockedUserAgents' => ['sqlmap']]);

        $request = $this->createRequest('GET', '/api/v1/App/user')->withHeader('User-Agent', 'sqlmap/1.7');

        $this->assertSame(403, $middleware->process($request, $this->createHandler())->getStatusCode());
    }

    public function testEmergencyModeReturns503OutsideAllowlist(): void
    {
        $middleware = $this->createMiddleware([
            'passiveMode' => false,
            'emergencyMode' => true,
            'emergencyWhitelist' => ['198.51.100.7'],
        ]);

        $outside = $this->createRequest('GET', '/api/v1/App/user');

        $this->assertSame(503, $middleware->process($outside, $this->createHandler())->getStatusCode());

        $allowed = $this->createRequest('GET', '/api/v1/App/user', serverParams: ['REMOTE_ADDR' => '198.51.100.7']);

        $this->assertSame(200, $middleware->process($allowed, $this->createHandler())->getStatusCode());
    }

    public function testRepeatedUnauthorizedResponsesBanTheClient(): void
    {
        $rule = [
            'rule_type' => 'return_pattern',
            'pattern' => 'status:401',
            'threshold' => 3,
            'window' => 60,
            'action' => 'ban',
            'ban_duration' => 600,
        ];

        $middleware = $this->createMiddleware([
            'passiveMode' => false,
            'routes' => ['GET /api/v1/App/user' => ['behaviorRules' => [$rule]]],
        ]);

        $request = $this->createRequest('GET', '/api/v1/App/user');

        $this->assertSame([401, 401, 401, 401, 403], $this->collectStatuses($middleware, $request, 5, 401));
    }

    public function testBanFromBehaviorRuleIsSharedThroughRedis(): void
    {
        $connection = @fsockopen('127.0.0.1', self::REDIS_PORT, timeout: 1);

        if ($connection === false) {
            $this->markTestSkipped('No Redis on 127.0.0.1:' . self::REDIS_PORT . '.');
        }

        fclose($connection);

        $guard = [
            'passiveMode' => false,
            'enableRedis' => true,
            'redisUrl' => 'redis://127.0.0.1:' . self::REDIS_PORT,
            'redisPrefix' => 'espo_guard_test_' . bin2hex(random_bytes(4)) . ':',
            'routes' => ['GET /api/v1/App/user' => ['behaviorRules' => [[
                'rule_type' => 'return_pattern',
                'pattern' => 'status:401',
                'threshold' => 2,
                'window' => 60,
                'action' => 'ban',
                'ban_duration' => 60,
            ]]]],
        ];

        $request = $this->createRequest('GET', '/api/v1/App/user');

        $this->collectStatuses($this->createMiddleware($guard), $request, 3, 401);

        $anotherWorker = $this->createMiddleware($guard);

        $this->assertSame(403, $anotherWorker->process($request, $this->createHandler())->getStatusCode());
    }

    public function testHoneypotFieldReturns403(): void
    {
        $middleware = $this->createMiddleware([
            'passiveMode' => false,
            'routes' => ['POST /api/v1/LeadCapture/' => ['honeypotFields' => ['website_url']]],
        ]);

        $trap = $this->createRequest('POST', '/api/v1/LeadCapture/key', '{"lastName":"A","website_url":"http://spam"}')
            ->withHeader('Content-Type', 'application/json');

        $this->assertSame(403, $middleware->process($trap, $this->createHandler())->getStatusCode());

        $human = $this->createRequest('POST', '/api/v1/LeadCapture/key', '{"lastName":"A","website_url":""}')
            ->withHeader('Content-Type', 'application/json');

        $this->assertSame(200, $middleware->process($human, $this->createHandler())->getStatusCode());
    }

    public function testDefaultPolicyValues(): void
    {
        $settings = (new DefaultPolicy())->merge([]);

        $this->assertTrue($settings['passiveMode']);
        $this->assertTrue($settings['enableRateLimiting']);
        $this->assertSame(10000, $settings['rateLimit']);

        $routes = $settings['routes'];

        $this->assertFalse($routes['POST /api/v1/Attachment/chunk/']['detectionScanBody']);
        $this->assertSame(10, $routes['POST /api/v1/LeadCapture/']['rateLimit']);
        $this->assertSame(5, $routes['POST /api/v1/User/passwordChangeRequest']['rateLimit']);
        $this->assertSame(5, $routes['POST /api/v1/User/changePasswordByRequest']['rateLimit']);

        foreach (['GET /api/v1/App/user', '/api/v1/'] as $key) {
            $rule = $routes[$key]['behaviorRules'][0];

            $this->assertSame('return_pattern', $rule['rule_type']);
            $this->assertSame('status:401', $rule['pattern']);
            $this->assertSame('ban', $rule['action']);
        }
    }

    public function testRedisIsOffByDefault(): void
    {
        $this->assertFalse((new DefaultPolicy())->merge([])['enableRedis']);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function unsupportedRedisUrlProvider(): array
    {
        return [
            'user' => ['redis://bob@127.0.0.1:6379', 'a user'],
            'password' => ['redis://:secret@127.0.0.1:6379', 'a password'],
            'database index' => ['redis://127.0.0.1:6379/2', 'a database index'],
            'tls scheme' => ['rediss://127.0.0.1:6379', 'redis://host[:port]'],
        ];
    }

    #[DataProvider('unsupportedRedisUrlProvider')]
    public function testUnsupportedRedisUrlFailsLoudly(string $url, string $expected): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expected);

        $this->createMiddleware(['redisUrl' => $url])
            ->process($this->createRequest('GET', '/'), $this->createHandler());
    }

    public function testPlainRedisUrlIsAccepted(): void
    {
        $response = $this->createMiddleware(['redisUrl' => 'redis://127.0.0.1:6420'])
            ->process($this->createRequest('GET', '/'), $this->createHandler());

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testOperatorSettingsOverrideDefaultsKeyByKey(): void
    {
        $settings = (new DefaultPolicy())->merge([
            'passiveMode' => false,
            'routes' => [
                'POST /api/v1/LeadCapture/' => ['rateLimit' => 2],
                'POST /api/v1/User/passwordChangeRequest' => null,
            ],
        ]);

        $this->assertFalse($settings['passiveMode']);
        $this->assertSame(['rateLimit' => 2], $settings['routes']['POST /api/v1/LeadCapture/']);
        $this->assertArrayNotHasKey('POST /api/v1/User/passwordChangeRequest', $settings['routes']);
        $this->assertArrayHasKey('POST /api/v1/User/changePasswordByRequest', $settings['routes']);
    }

    public function testUnknownConfigKeyFailsLoudly(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown guard config key(s): rateLimitt');

        $this->createMiddleware(['rateLimitt' => 5])
            ->process($this->createRequest('GET', '/'), $this->createHandler());
    }

    public function testUnknownRouteOptionFailsLoudly(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Unknown option(s) for guard route '/x/': rateLimitt");

        $this->createMiddleware(['routes' => ['/x/' => ['rateLimitt' => 5]]])
            ->process($this->createRequest('GET', '/'), $this->createHandler());
    }

    public function testInvalidBehaviorRuleFailsLoudly(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid behavior rule for guard route '/x/'");

        $routes = ['/x/' => ['behaviorRules' => [['rule_type' => 'nope', 'threshold' => 1]]]];

        $this->createMiddleware(['routes' => $routes])
            ->process($this->createRequest('GET', '/'), $this->createHandler());
    }

    public function testAgentSettingsWithoutThePackageFailLoudly(): void
    {
        if (class_exists('RenzoFranceschini\\GuardAgent\\GuardAgent')) {
            $this->markTestSkipped('The agent package is installed.');
        }

        $this->expectException(RuntimeException::class);

        $this->createMiddleware(['agent' => ['apiKey' => 'k']])
            ->process($this->createRequest('GET', '/'), $this->createHandler());
    }

    public function testWarnsWhenClientAddressParamIsNotRemoteAddr(): void
    {
        $this->createMiddleware([], ['ipAddressServerParam' => 'HTTP_X_FORWARDED_FOR'])
            ->process($this->createRequest('GET', '/'), $this->createHandler());

        $this->assertTrue($this->logHandler->hasWarningThatContains('guard.trustedProxies'));
    }

    public function testNoWarningWithDefaultClientAddressParam(): void
    {
        $this->createMiddleware([])->process($this->createRequest('GET', '/'), $this->createHandler());

        $this->assertFalse($this->logHandler->hasWarningThatContains('guard.trustedProxies'));
    }

    public function recordHandled(ServerRequestInterface $request): void
    {
        $this->handlerCalls++;
        $this->receivedBody = $request->getBody()->getContents();
    }

    /**
     * @param array<string, mixed> $guard
     * @param array<string, mixed> $extraConfig
     */
    private function createMiddleware(array $guard, array $extraConfig = []): Middleware
    {
        $config = $this->createConfig(['guardEnabled' => true, 'guard' => $guard, ...$extraConfig]);

        $builder = new GuardMiddlewareBuilder(
            $config,
            new Log('test', [$this->logHandler]),
            new SecurityConfigBuilder(),
            new RouteConfigBuilder(),
            new GuardAgentFactory(),
        );

        return new Middleware($config, new DefaultPolicy(), $builder);
    }

    /**
     * @param array<string, mixed> $values
     */
    private function createConfig(array $values): Config
    {
        $config = $this->createMock(Config::class);
        $config->method('get')->willReturnCallback(fn (string $name, $default = null) => $values[$name] ?? $default);

        return $config;
    }

    /**
     * @param array<string, string> $serverParams
     */
    private function createRequest(
        string $method,
        string $uri,
        string $body = '',
        array $serverParams = ['REMOTE_ADDR' => self::CLIENT_IP],
    ): ServerRequestInterface {

        $request = (new ServerRequestFactory())->createServerRequest($method, $uri, $serverParams);

        return $body === '' ? $request : $request->withBody((new StreamFactory())->createStream($body));
    }

    /**
     * @return int[]
     */
    private function collectStatuses(
        Middleware $middleware,
        ServerRequestInterface $request,
        int $count,
        int $handlerStatus = 200,
    ): array {

        $statuses = [];

        for ($i = 0; $i < $count; $i++) {
            $statuses[] = $middleware->process($request, $this->createHandler($handlerStatus))->getStatusCode();
        }

        return $statuses;
    }

    private function createHandler(int $status = 200): RequestHandlerInterface
    {
        $this->handlerCalls = 0;

        return new class ($this, $status) implements RequestHandlerInterface {
            public function __construct(private MiddlewareTest $test, private int $status) {}

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->test->recordHandled($request);

                return (new ResponseFactory())->createResponse($this->status);
            }
        };
    }
}
