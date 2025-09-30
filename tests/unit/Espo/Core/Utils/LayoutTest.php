<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

namespace tests\unit\Espo\Core\Utils;

use Espo\Core\Utils\Metadata;
use Espo\Tools\Layout\LayoutProvider;
use Espo\Core\Utils\File\Manager as FileManager;
use Espo\Core\InjectableFactory;

use Espo\Core\Utils\Resource\FileReader;
use Espo\Core\Utils\Resource\FileReader\Params as FileReaderParams;

class LayoutTest extends \PHPUnit\Framework\TestCase
{
    /** @var LayoutProvider */
    private $layout;
    /** @var InjectableFactory */
    private $injectableFactory;
    /** @var FileManager */
    private $fileManager;
    private $fileReader;

    protected function setUp(): void
    {
        $this->fileManager = $this->createMock(FileManager::class);
        $this->injectableFactory = $this->createMock(InjectableFactory::class);
        $this->fileReader = $this->createMock(FileReader::class);

        $metadata = $this->createMock(Metadata::class);

        $this->layout = new LayoutProvider(
            $this->fileManager,
            $this->injectableFactory,
            $metadata,
            $this->fileReader
        );
    }

    public function testGet1(): void
    {
        $this->fileReader
            ->expects($this->once())
            ->method('exists')
            ->with(
                'layouts/Test/test.json',
                $this->callback(
                    function (FileReaderParams $params): bool {
                        return $params->getScope() === 'Test';
                    }
                )
            )
            ->willReturn(true);

        $this->fileReader
            ->expects($this->once())
            ->method('read')
            ->with(
                'layouts/Test/test.json',
                $this->callback(
                    function (FileReaderParams $params): bool {
                        return $params->getScope() === 'Test';
                    }
                )
            )
            ->willReturn('["test"]');

        $result = $this->layout->get('Test', 'test');

        $this->assertEquals('["test"]', $result);
    }

    public function testGetDefault(): void
    {
        $this->fileReader
            ->expects($this->once())
            ->method('exists')
            ->with(
                'layouts/Test/test.json',
                $this->callback(
                    function (FileReaderParams $params): bool {
                        return $params->getScope() === 'Test';
                    }
                )
            )
            ->willReturn(false);

        $this->fileManager
            ->expects($this->once())
            ->method('isFile')
            ->with('application/Espo/Resources/defaults/layouts/test.json')
            ->willReturn(true);

        $this->fileManager
            ->expects($this->once())
            ->method('getContents')
            ->with('application/Espo/Resources/defaults/layouts/test.json')
            ->willReturn('["test"]');

        $result = $this->layout->get('Test', 'test');

        $this->assertEquals('["test"]', $result);
    }
}
