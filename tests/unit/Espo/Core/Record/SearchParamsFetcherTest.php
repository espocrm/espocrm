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

namespace tests\unit\Espo\Core\Record;

use Espo\Core\Record\SearchParamsFetcher;
use Espo\Core\Api\RequestWrapper;

use Espo\Core\Utils\Config;
use Espo\Core\Select\Text\MetadataProvider as TextMetadataProvider;

use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\RequestFactory;

class SearchParamsFetcherTest extends TestCase
{
    private $config;
    private $textMetadataProvider;

    protected function setUp(): void
    {
        $this->config = $this->createMocK(Config::class);
        $this->textMetadataProvider = $this->createMocK(TextMetadataProvider::class);

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

        $fetcher = new SearchParamsFetcher($this->config, $this->textMetadataProvider);

        $params = $fetcher->fetch(new RequestWrapper($request));

        $this->assertEquals($raw['textFilter'], $params->getTextFilter());
        $this->assertEquals($raw['maxSize'], $params->getMaxSize());
    }

    public function testFetchQuery(): void
    {
        $q = http_build_query(['attributeSelect' => 'a,b']);

        $request = (new RequestFactory)->createRequest('GET', 'http://localhost/?' . $q);

        $fetcher = new SearchParamsFetcher($this->config, $this->textMetadataProvider);

        $params = $fetcher->fetch(new RequestWrapper($request));

        $this->assertEquals(['a', 'b'], $params->getSelect());
    }
}
