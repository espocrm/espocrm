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

namespace tests\unit\Espo\Core\Authentication\Login;

use Espo\Core\Api\Method;
use Espo\Core\Api\RequestWrapper;
use Espo\Core\Authentication\ConfigDataProvider;
use Espo\Core\Authentication\Login\MetadataParams;
use Espo\Core\Authentication\Login\MethodResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;

class MethodResolverTest extends TestCase
{

    /**
     * @param MetadataParams[] $paramsList
     * @param array{string, string} $headerPair
     */
    #[DataProvider('provider')]
    public function testResolve(array $paramsList, array $headerPair, ?string $expected): void
    {
        $this->processTest($paramsList, $headerPair, $expected);
    }

    public static function provider(): array
    {
        $paramsList1 = [
            new MetadataParams(
                method: 'Test',
            ),
            new MetadataParams(
                method: 'ApiKey',
                api: true,
                credentialsHeader: 'X-Api-Key',
            ),
            new MetadataParams(
                method: 'OAuth',
                api: true,
                credentialsHeader: 'Authorization',
                credentialsHeaderScheme: 'Bearer',
            ),
        ];

        $paramsList2 = [
            new MetadataParams(
                method: 'Some',
                api: true,
                credentialsHeader: 'Authorization',
            ),
            new MetadataParams(
                method: 'OAuth',
                api: true,
                credentialsHeader: 'Authorization',
                credentialsHeaderScheme: 'Bearer',
            ),
        ];

        $paramsList3 = [
            new MetadataParams(
                method: 'OAuth',
                api: true,
                credentialsHeader: 'Authorization',
                credentialsHeaderScheme: 'Bearer',
            ),
            new MetadataParams(
                method: 'Some',
                api: true,
                credentialsHeader: 'Authorization',
            ),
        ];

        return [
            [
                $paramsList1,
                ['Authorization', 'Bearer test'],
                'OAuth',
            ],
            [
                $paramsList1,
                ['X-Api-Key', 'test'],
                'ApiKey',
            ],
            [
                $paramsList1,
                ['Espo-Authorization', 'test'],
                null,
            ],
            [
                $paramsList2,
                ['Authorization', 'Bearer test'],
                'OAuth',
            ],
            [
                $paramsList3,
                ['Authorization', 'Bearer test'],
                'OAuth',
            ],
            [
                $paramsList2,
                ['Authorization', 'test'],
                'Some',
            ],
            [
                $paramsList3,
                ['Authorization', 'test'],
                'Some',
            ],
        ];
    }

    /**
     * @param MetadataParams[] $paramsList
     * @param array{string, string} $headerPair
     */
    private function processTest(array $paramsList, array $headerPair, ?string $expected): void
    {
        $config = $this->createMock(ConfigDataProvider::class);

        $config
            ->expects(self::any())
            ->method('getLoginMetadataParamsList')
            ->willReturn($paramsList);

        $request = new RequestWrapper(
            psr7Request: (new ServerRequestFactory())->createServerRequest(Method::GET, 'http://localhost')
                ->withHeader($headerPair[0], $headerPair[1]),
        );

        $resolver = new MethodResolver($config);

        $this->assertEquals($expected, $resolver->resolve($request));
    }
}
