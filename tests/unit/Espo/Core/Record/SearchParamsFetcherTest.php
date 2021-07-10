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

namespace tests\unit\Espo\Core\Record;

use Espo\Core\Record\SearchParamsFetcher;
use Espo\Core\Api\RequestWrapper;

use Espo\Core\Utils\Config;

use Slim\Psr7\Factory\RequestFactory;


class SearchParamsFetcherTest extends \PHPUnit\Framework\TestCase
{
    private $config;

    protected function setUp(): void
    {
        $this->config = $this->createMocK(Config::class);

        $this->config
            ->method('get')
            ->with('recordListMaxSizeLimit')
            ->willReturn(null);
    }

    public function testFetchJson1(): void
    {
        $raw = [
            'textFilter' => 'test*',
            'maxSize' => 10,
        ];

        $q = http_build_query(['searchParams' => json_encode($raw)]);

        $request = (new RequestFactory)->createRequest('GET', 'http://localhost/?' . $q);

        $fetcher = new SearchParamsFetcher($this->config);

        $params = $fetcher->fetch(new RequestWrapper($request));

        $this->assertEquals($params->getTextFilter(), $raw['textFilter']);
        $this->assertEquals($params->getMaxSize(), $raw['maxSize']);
    }
}
