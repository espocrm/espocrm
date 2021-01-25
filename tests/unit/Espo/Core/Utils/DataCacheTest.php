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

namespace tests\Espo\Core\Utils;

use Espo\Core\{
    Utils\DataCache,
    Utils\File\Manager as FileManager,
    Exceptions\Error,
};

use InvalidArgumentException;

class DataCacheTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp() : void
    {
        $this->fileManager = $this->getMockBuilder(FileManager::class)->disableOriginalConstructor()->getMock();

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

    public function testGetDataInt()
    {
        $this->expectException(Error::class);

        $this->fileManager
            ->expects($this->once())
            ->method('getPhpSafeContents')
            ->with('data/cache/application/autoload.php')
            ->willReturn(1);

        $result = $this->dataCache->get('autoload');
    }

    public function testGetError()
    {
        $this->expectException(Error::class);

        $this->fileManager
            ->expects($this->once())
            ->method('getPhpSafeContents')
            ->with('data/cache/application/autoload.php')
            ->willReturn(false);

        $result = $this->dataCache->get('autoload');
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
        $this->expectException(Error::class);

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
