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

namespace tests\unit\Espo\Core\Utils;

use Espo\Core\Utils\DataCache;
use Espo\Core\Utils\File\Manager as FileManager;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use InvalidArgumentException;

class DataCacheTest extends TestCase
{
    private $fileManager;
    private $dataCache;

    protected function setUp() : void
    {
        $this->fileManager = $this->createMock(FileManager::class);

        $this->dataCache = new DataCache($this->fileManager);
    }

    public function testHasTrue()
    {
        $this->fileManager
            ->expects($this->once())
            ->method('isFile')
            ->with('data/cache/application/autoload.php')
            ->willReturn(true);

        $result = $this->dataCache->has('autoload');

        $this->assertTrue($result);
    }

    public function testHasFalse()
    {
        $this->fileManager
            ->expects($this->once())
            ->method('isFile')
            ->with('data/cache/application/autoload.php')
            ->willReturn(false);

        $result = $this->dataCache->has('autoload');

        $this->assertFalse($result);
    }

    public function testStoreData()
    {
        $data = [
            'test' => 1,
        ];

        $this->fileManager
            ->expects($this->once())
            ->method('putPhpContents')
            ->with('data/cache/application/autoload.php', $data, true, true)
            ->willReturn(true);

        $this->dataCache->store('autoload', $data);
    }

    public function testStoreError()
    {
        $this->expectException(RuntimeException::class);

        $data = [
            'test' => 1,
        ];

        $this->fileManager
            ->expects($this->once())
            ->method('putPhpContents')
            ->with('data/cache/application/autoload.php', $data, true, true)
            ->willReturn(false);

        $this->dataCache->store('autoload', $data);
    }

    public function testStoreBadDataType()
    {
        $this->expectException(InvalidArgumentException::class);

        $data = false;

        $this->dataCache->store('autoload', $data);
    }

    public function testSubDir()
    {
        $this->fileManager
            ->expects($this->once())
            ->method('isFile')
            ->with('data/cache/application/languageTest/test0.php')
            ->willReturn(true);

        $result = $this->dataCache->has('languageTest/test0');

        $this->assertTrue($result);
    }

    public function testBadKey1()
    {
        $this->expectException(InvalidArgumentException::class);

        $result = $this->dataCache->has('/language');

        $this->assertTrue($result);
    }

    public function testBadKey2()
    {
        $this->expectException(InvalidArgumentException::class);

        $result = $this->dataCache->has('language/');

        $this->assertTrue($result);
    }

    public function testBadKey3()
    {
        $this->expectException(InvalidArgumentException::class);

        $result = $this->dataCache->has('');

        $this->assertTrue($result);
    }

    public function testBadKey4()
    {
        $this->expectException(InvalidArgumentException::class);

        $result = $this->dataCache->has('language\test');

        $this->assertTrue($result);
    }
}
