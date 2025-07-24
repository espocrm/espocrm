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

use Espo\Core\Record\CreateParamsFetcher;
use Espo\Core\Api\RequestWrapper;
use PHPUnit\Framework\TestCase;

class CreateParamsFetcherTest extends TestCase
{
    public function test1(): void
    {
        $request = $this->createMock(RequestWrapper::class);

        $request
            ->method('hasHeader')
            ->with('X-Skip-Duplicate-Check')
            ->willReturn(true);

        $request
            ->method('getHeader')
            ->willReturnMap(
                [
                    ['X-Skip-Duplicate-Check', 'true'],
                    ['X-Duplicate-Source-Id', null],
                ]
            );

        $params = (new CreateParamsFetcher())->fetch($request);

        $this->assertTrue($params->skipDuplicateCheck());
    }

    public function test2(): void
    {
        $request = $this->createMock(RequestWrapper::class);

        $request
            ->method('hasHeader')
            ->willReturn(true);

        $request
            ->method('getHeader')
            ->willReturnMap(
                [
                    ['X-Skip-Duplicate-Check', 'false'],
                    ['X-Duplicate-Source-Id', null],
                ]
            );

        $params = (new CreateParamsFetcher())->fetch($request);

        $this->assertFalse($params->skipDuplicateCheck());
    }

    public function test3(): void
    {
        $request = $this->createMock(RequestWrapper::class);

        $request
            ->method('hasHeader')
            ->willReturn(false);

        $params = (new CreateParamsFetcher())->fetch($request);

        $this->assertFalse($params->skipDuplicateCheck());
    }

    public function test4(): void
    {
        $request = $this->createMock(RequestWrapper::class);

        $request
            ->method('hasHeader')
            ->willReturn(true);

        $request
            ->method('getHeader')
            ->willReturnMap(
                [
                    ['X-Skip-Duplicate-Check', 'TRUE'],
                    ['X-Duplicate-Source-Id', null],
                ]
            );

        $params = (new CreateParamsFetcher())->fetch($request);

        $this->assertTrue($params->skipDuplicateCheck());
    }

    public function test5(): void
    {
        $request = $this->createMock(RequestWrapper::class);

        $request
            ->method('hasHeader')
            ->willReturn(false);

        $request
            ->method('getParsedBody')
            ->willReturn((object) [
                '_skipDuplicateCheck' => true,
            ]);

        $params = (new CreateParamsFetcher())->fetch($request);

        $this->assertTrue($params->skipDuplicateCheck());
    }
}
