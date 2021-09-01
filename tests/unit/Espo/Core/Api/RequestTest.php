<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace tests\unit\Espo\Core\Api;

use Psr\Http\Message\{
    ServerRequestInterface as Psr7Request,
    StreamInterface,
};

use Slim\Psr7\Factory\RequestFactory;

use Espo\Core\Api\RequestWrapper;

class RequestTest extends \PHPUnit\Framework\TestCase
{

    protected function setUp() : void
    {
        $this->request = $this->getMockBuilder(Psr7Request::class)->disableOriginalConstructor()->getMock();
    }

    protected function createRequest(array $queryParams, array $routeParams = []) : RequestWrapper
    {
        $this->request
            ->expects($this->any())
            ->method('getQueryParams')
            ->willReturn($queryParams);

        return new RequestWrapper($this->request, '', $routeParams);
    }

    public function testHasQueryParam()
    {
        $request = $this->createRequest([
            'id' => '1',
        ]);

        $this->assertTrue($request->hasQueryParam('id'));
        $this->assertFalse($request->hasQueryParam('test'));
    }

    public function testHasRouteParam()
    {
        $request = $this->createRequest(
            [
            ],
            [
                'id' => '1',
            ]
        );

        $this->assertTrue($request->hasRouteParam('id'));
        $this->assertFalse($request->hasRouteParam('test'));
    }

    public function testGetQueryParam()
    {
        $request = $this->createRequest([
            'id' => '1',
        ]);

        $this->assertEquals('1', $request->getQueryParam('id'));
    }

    public function testGetRouteParam()
    {
        $request = $this->createRequest(
            [
            ],
            [
                'id' => '1',
            ]
        );

        $this->assertEquals('1', $request->getRouteParam('id'));
    }

    public function testGet()
    {
        $request = $this->createRequest(
            [
                'id' => '1',
            ],
            [
                'id' => '2',
            ]
        );

        $this->assertEquals('2', $request->get('id'));
    }

    protected function createRequestWithBody(string $contents) : RequestWrapper
    {
        $body = $this->createMock(StreamInterface::class);

        $body
            ->expects($this->any())
            ->method('getContents')
            ->willReturn($contents);

        $this->request
            ->expects($this->any())
            ->method('getBody')
            ->willReturn($body);

        $this->request
            ->expects($this->any())
            ->method('hasHeader')
            ->with('Content-Type')
            ->willReturn(true);

        $this->request
            ->expects($this->any())
            ->method('getHeader')
            ->with('Content-Type')
            ->willReturn(['application/json']);

        return new RequestWrapper($this->request);
    }

    public function testGetParsedBody()
    {
        $original = (object) [
            'key1' => '1',
            'key2' => (object) [
                'key21' => [
                    '211',
                    '212',
                    (object) [
                        '2111' => '1',
                    ],
                ],
            ],
            'key3' => [
                '31',
                '32',
                null,
            ],
            'key4' => null,
        ];

        $contents = json_encode($original);

        $request = $this->createRequestWithBody($contents);

        $parsed = $request->getParsedBody();

        $anotherParsed = $request->getParsedBody();

        $this->assertEquals($parsed, $original);

        $this->assertEquals($parsed, $anotherParsed);

        $this->assertNotSame($parsed, $anotherParsed);

        $this->assertNotSame($parsed->key2, $anotherParsed->key2);

        $this->assertNotSame($parsed->key2->key21[2], $anotherParsed->key2->key21[2]);
    }

    public function testContentType1(): void
    {
        $request = (new RequestFactory())
            ->createRequest('POST', 'http://localhost/?')
            ->withHeader('Content-Type', 'application/json; charset=utf-8');

        $requestWrapped = new RequestWrapper($request);

        $this->assertEquals('application/json', $requestWrapped->getContentType());
    }

    public function testContentType2(): void
    {
        $request = (new RequestFactory())
            ->createRequest('POST', 'http://localhost/?')
            ->withHeader('Content-Type', 'application/json');

        $requestWrapped = new RequestWrapper($request);

        $this->assertEquals('application/json', $requestWrapped->getContentType());
    }

    public function testContentTypeEmpty(): void
    {
        $request = (new RequestFactory())
            ->createRequest('POST', 'http://localhost/?');

        $requestWrapped = new RequestWrapper($request);

        $this->assertEquals(null, $requestWrapped->getContentType());
    }

    public function testHeaderAsArray(): void
    {
        $request = (new RequestFactory())
            ->createRequest('POST', 'http://localhost/?')
            ->withAddedHeader('Test', '1')
            ->withAddedHeader('Test', '2');

        $requestWrapped = new RequestWrapper($request);

        $this->assertEquals(['1', '2'], $requestWrapped->getHeaderAsArray('Test'));
    }
}
