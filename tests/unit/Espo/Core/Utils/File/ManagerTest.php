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

namespace tests\unit\Espo\Core\Utils\File;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use tests\unit\ReflectionHelper;
use Espo\Core\Utils\Util;
use Espo\Core\Utils\File\Manager as FileManager;

class ManagerTest extends TestCase
{
    /**
     * @var FileManager
     */
    private $fileManager;

    protected $filesPath = 'tests/unit/testData/FileManager';
    protected const CACHE_PATH = 'tests/unit/testData/cache/FileManager';
    protected $reflection;

    protected function setUp(): void
    {
        $this->fileManager = new FileManager();

        $this->reflection = new ReflectionHelper($this->fileManager);
    }

    public function testGetFileName()
    {
        $this->assertEquals('Donwload', $this->fileManager->getFileName('Donwload.php'));

        $this->assertEquals('Donwload', $this->fileManager->getFileName('/Donwload.php'));

        $this->assertEquals('Donwload', $this->fileManager->getFileName('\Donwload.php'));

        $this->assertEquals('Donwload', $this->fileManager->getFileName('application/Espo/EntryPoints/Donwload.php'));
    }

    public function testGetContents()
    {
        $result = file_get_contents($this->filesPath . '/getContent/test.json');

        $this->assertEquals(
            $result,
            $this->fileManager->getContents($this->filesPath . '/getContent/test.json')
        );
    }

    public function testPutContents()
    {
        $testPath = self::CACHE_PATH;

        $result= 'next value';

        $this->assertTrue(
            $this->fileManager->putContents($testPath . '/setContent.json', $result)
        );

        $this->assertEquals(
            $result,
            $this->fileManager->getContents($testPath . '/setContent.json')
        );

        @unlink($testPath . '/setContent.json');
    }

    public function testGetDirName()
    {
        $input = 'data/logs/espo.log';
        $result = 'logs';
        $this->assertEquals($result, $this->fileManager->getDirName($input, false));

        $input = 'data/logs/espo.log/';
        $result = 'logs';
        $this->assertEquals($result, $this->fileManager->getDirName($input, false));

        $input = 'application/Espo/Resources/metadata/entityDefs';
        $result = 'entityDefs';
        $this->assertEquals($result, $this->fileManager->getDirName($input, false));

        $input = 'application/Espo/Resources/metadata/entityDefs/';
        $result = 'entityDefs';
        $this->assertEquals($result, $this->fileManager->getDirName($input, false));

        //path doesn't exists. Be careful to use "/" at the beginning
        $input = '/application/Espo/Resources/metadata/entityDefs';
        $result = 'metadata';
        $this->assertEquals($result, $this->fileManager->getDirName($input, false));

        $input = 'notRealPath/logs/espo.log';
        $result = 'logs';
        $this->assertEquals($result, $this->fileManager->getDirName($input, false));

        $input = $this->filesPath . '/getContent';
        $result = 'getContent';
        $this->assertEquals($result, $this->fileManager->getDirName($input, false));
    }

    public function testGetDirNameFullPath()
    {
        $input = 'data/logs/espo.log';
        $result = 'data/logs';
        $this->assertEquals($result, $this->fileManager->getDirName($input));

        $input = 'data/logs/espo.log/';
        $result = 'data/logs';
        $this->assertEquals($result, $this->fileManager->getDirName($input));

        $input = 'application/Espo/Resources/metadata/entityDefs';
        $result = 'application/Espo/Resources/metadata/entityDefs';
        $this->assertEquals($result, $this->fileManager->getDirName($input));

        $input = 'application/Espo/Resources/metadata/entityDefs/';
        $result = 'application/Espo/Resources/metadata/entityDefs';
        $this->assertEquals($result, $this->fileManager->getDirName($input));

        //path doesn't exists. Be careful to use "/" at the beginning
        $input = '/application/Espo/Resources/metadata/entityDefs';
        $result = '/application/Espo/Resources/metadata';
        $this->assertEquals($result, $this->fileManager->getDirName($input));

        $input = 'notRealPath/logs/espo.log';
        $result = 'notRealPath/logs';
        $this->assertEquals($result, $this->fileManager->getDirName($input));

        $input = $this->filesPath . '/getContent';
        $result = $this->filesPath . '/getContent';
        $this->assertEquals($result, $this->fileManager->getDirName($input, true));
    }

    public function testUnsetContents()
    {
        $testPath = self::CACHE_PATH.'/unsets.json';

        $initData = '{"fields":{"someName":{"type":"varchar","maxLength":40},"someName2":{"type":"varchar","maxLength":36}}}';
        $this->fileManager->putContents($testPath, $initData);

        $unsets = ['fields.someName2'];
        $this->assertTrue($this->fileManager->unsetJsonContents($testPath, $unsets));

        $result = '{"fields":{"someName":{"type":"varchar","maxLength":40}}}';

        $this->assertJsonStringEqualsJsonFile($testPath, $result);
    }

    public function testIsDirEmpty()
    {
        $this->assertFalse($this->fileManager->isDirEmpty('application'));
        $this->assertFalse($this->fileManager->isDirEmpty('tests/unit/Espo'));
        $this->assertFalse($this->fileManager->isDirEmpty('tests/unit/Espo/Core/Utils/File'));

        $dirPath = 'tests/unit/testData/cache/EmptyDir';
        if (file_exists($dirPath) || mkdir($dirPath, 0755)) {
            $this->assertTrue($this->fileManager->isDirEmpty($dirPath));
        }
    }

    public function testGetParentDirName()
    {
        $input = 'application/Espo/Resources/metadata/entityDefs';
        $result = 'metadata';
        $this->assertEquals($result, $this->fileManager->getParentDirName($input, false));

        $input = 'application/Espo/Resources/metadata/entityDefs/';
        $result = 'metadata';
        $this->assertEquals($result, $this->fileManager->getParentDirName($input, false));

        //path doesn't exists. Be careful to use "/" at the beginning
        $input = '/application/Espo/Resources/metadata/entityDefs';
        $result = 'metadata';
        $this->assertEquals($result, $this->fileManager->getParentDirName($input, false));

        //path doesn't exists. Be careful to use "/" at the beginning
        $input = '/application/Espo/Resources/metadata/entityDefs';
        $result = '/application/Espo/Resources/metadata';
        $this->assertEquals($result, $this->fileManager->getParentDirName($input));

        $input = 'notRealPath/logs/espo.log';
        $result = 'notRealPath/logs';
        $this->assertEquals($result, $this->fileManager->getParentDirName($input));

        $input = $this->filesPath . '/getContent';
        $result = $this->filesPath;
        $this->assertEquals($result, $this->fileManager->getParentDirName($input, true));
    }

    public function testGetSingleFileListAll()
    {
        $input = array (
          'custom' =>
          array (
            'Espo' =>
            array (
              'Custom' =>
              array (
                'Modules' =>
                array (
                  'ExtensionTest' =>
                  array (
                    0 => 'File.json',
                    1 => 'File.php',
                  ),
                ),
              ),
            ),
          ),
        );

        $result = array (
            'custom',
            'custom/Espo',
            'custom/Espo/Custom',
            'custom/Espo/Custom/Modules',
            'custom/Espo/Custom/Modules/ExtensionTest',
            'custom/Espo/Custom/Modules/ExtensionTest/File.json',
            'custom/Espo/Custom/Modules/ExtensionTest/File.php',
        );
        $result = array_map('\Espo\Core\Utils\Util::fixPath', $result);

        $this->assertEquals($result, $this->reflection->invokeMethod('getSingleFileList', array($input)));
    }

    public function testGetSingeFileListOnlyFiles()
    {
        $input = array (
          'custom' =>
          array (
            'Espo' =>
            array (
              'Custom' =>
              array (
                'Modules' =>
                array (
                  'ExtensionTest' =>
                  array (
                    0 => 'File.json',
                    1 => 'File.php',
                  ),
                ),
              ),
            ),
          ),
        );

        $result = array (
            'custom/Espo/Custom/Modules/ExtensionTest/File.json',
            'custom/Espo/Custom/Modules/ExtensionTest/File.php',
        );
        $result = array_map('\Espo\Core\Utils\Util::fixPath', $result);

        $this->assertEquals($result, $this->reflection->invokeMethod('getSingleFileList', array($input, true)));
    }

    public function testGetSingeFileListOnlyDirs()
    {
        $input = array (
          'custom' =>
          array (
            'Espo' =>
            array (
              'Custom' =>
              array (
                'Modules' =>
                array (
                  'ExtensionTest' =>
                  array (
                    0 => 'File.json',
                    1 => 'File.php',
                  ),
                ),
              ),
            ),
          ),
        );

        $result = array (
            'custom',
            'custom/Espo',
            'custom/Espo/Custom',
            'custom/Espo/Custom/Modules',
            'custom/Espo/Custom/Modules/ExtensionTest',
        );
        $result = array_map('\Espo\Core\Utils\Util::fixPath', $result);

        $this->assertEquals($result, $this->reflection->invokeMethod('getSingleFileList', array($input, false)));
    }

    static public function fileListSets()
    {
        return [
          ['Set1', [
                'custom',
                'custom/Espo',
                'custom/Espo/Custom',
                'custom/Espo/Custom/Modules',
                'custom/Espo/Custom/Modules/TestModule',
                'custom/Espo/Custom/Modules/TestModule/SubFolder',
                'custom/Espo/Custom/Modules/TestModule/SubFolder/Tester.txt',
          ]
          ],

          ['Set2', [
                'custom',
                'custom/Espo',
                'custom/Espo/Custom',
                'custom/Espo/Custom/Resources',
                'custom/Espo/Custom/Resources/metadata',
                'custom/Espo/Custom/Resources/metadata/entityDefs',
                'custom/Espo/Custom/Resources/metadata/entityDefs/Account.json',
          ]
          ],

          ['Set3', [
                'custom',
                'custom/test.file',
          ]
          ],
        ];
    }

    #[DataProvider('fileListSets')]
    public function testRemoveWithEmptyDirs($name, $result)
    {
        $path = Util::fixPath($this->filesPath . '/Remove/' . $name);
        $cachePath = Util::fixPath(self::CACHE_PATH . '/' . $name);
        $result = array_map('\Espo\Core\Utils\Util::fixPath', $result);

        $fileList = array (
            $cachePath . '/custom/Espo/Custom/Modules/ExtensionTest/File.json',
            $cachePath . '/custom/Espo/Custom/Modules/ExtensionTest/File.php',
        );
        $fileList = array_map('\Espo\Core\Utils\Util::fixPath', $fileList);

        $res = $this->fileManager->copy($path, $cachePath, true);

        if ($res) {
            $this->assertTrue($this->fileManager->remove($fileList, null, true));
            $this->assertEquals($result, $this->fileManager->getFileList($cachePath, true, '', null, true));
        }
    }

    static public function existsPathSet()
    {
        return [
          ['application/Espo/Core/Application.php', 'application/Espo/Core/Application.php',],
          ['application/Espo/Core/NotRealApplication.php', 'application/Espo/Core'],
          ['application/Espo/Core/NotRealApplication.php', 'application/Espo/Core'],
          ['application/NoEspo/Core/Application.php', 'application'],
          ['notRealPath/Espo/Core/Application.php', '.'],
        ];
    }

    #[DataProvider('existsPathSet')]
    public function testGetExistsPath($input, $result)
    {
        $this->assertEquals(
            $result,
            $this->reflection->invokeMethod('getExistsPath', [$input])
        );
    }

    public function testCopyTestCase1()
    {
        $path = Util::fixPath($this->filesPath . '/copy/testCase1');
        $cachePath = Util::fixPath(self::CACHE_PATH . '/copy/testCase1');

        $expectedResult = [
            'custom/Espo/Custom/Modules/ExtensionTest/File.json',
            'custom/Espo/Custom/Modules/ExtensionTest/File.php',
            'custom/Espo/Custom/Modules/TestModule/SubFolder/Tester.txt',
        ];

        $expectedResult = array_map('\Espo\Core\Utils\Util::fixPath', $expectedResult);

        $result = $this->fileManager->copy($path, $cachePath, true);

        if ($result) {
            $this->assertEquals($expectedResult, $this->fileManager->getFileList($cachePath, true, '', true, true));
            $this->fileManager->removeInDir($cachePath);
        }
    }

    public function testCopyTestCase2()
    {
        $path = Util::fixPath($this->filesPath . '/copy/testCase2');
        $cachePath = Util::fixPath(self::CACHE_PATH . '/copy/testCase2');

        $expectedResult = [
            'custom/Espo/Custom/test1.php',
            'data/test2.php',
            'data/upload/5a86d9bf1154968dc',
            'test0.php'
        ];

        $expectedResult = array_map('\Espo\Core\Utils\Util::fixPath', $expectedResult);

        $result = $this->fileManager->copy($path, $cachePath, true);

        if ($result) {
            $this->assertEquals($expectedResult, $this->fileManager->getFileList($cachePath, true, '', true, true));
            $this->fileManager->removeInDir($cachePath);
        }
    }

    public function testCopyTestCase3()
    {
        $path = Util::fixPath($this->filesPath . '/copy/testCase3');
        $cachePath = Util::fixPath(self::CACHE_PATH . '/copy/testCase3');

        $expectedResult = [
            'custom/Espo/Custom/test1.php',
            'data/test2.php',
            'data/upload/5a86d9bf1154968dc',
            'test0.php'
        ];

        $expectedResult = array_map('\Espo\Core\Utils\Util::fixPath', $expectedResult);

        $fileList = $this->fileManager->getFileList($path, true, '', true, true);

        $this->assertEquals($expectedResult, $fileList, "Expected Result and File List");

        $result = $this->fileManager->copy($path, $cachePath, true, $fileList);

        if ($result) {
            $this->assertEquals(
                $expectedResult,
                $this->fileManager->getFileList($cachePath, true, '', true, true),
                "Expected Result and List of copied files"
            );

            $this->fileManager->removeInDir($cachePath);
        }
    }

    public function testCopyTestCase4()
    {
        $path = Util::fixPath($this->filesPath . '/copy/testCase4');
        $cachePath = Util::fixPath(self::CACHE_PATH . '/copy/testCase4');

        $expectedResult = [
            'custom',
            'custom/Espo',
            'custom/Espo/Custom',
            'custom/Espo/Custom/test1.php',
            'data',
            'data/test2.php',
            'data/upload',
            'data/upload/5a86d9bf1154968dc',
            'test0.php'
        ];

        $expectedResult = array_map('\Espo\Core\Utils\Util::fixPath', $expectedResult);

        $fileList = $this->fileManager->getFileList($path, true, '', null, true);

        $this->assertEquals($expectedResult, $fileList, "Expected Result and File List");

        $result = $this->fileManager->copy($path, $cachePath, true, $fileList);

        if ($result) {
            $this->assertEquals(
                $expectedResult,
                $this->fileManager->getFileList($cachePath, true, '', null, true),
                "Expected Result and List of copied files"
            );

            $this->fileManager->removeInDir($cachePath);
        }
    }

    static public function relativePathData()
    {
        $tmpPath = self::CACHE_PATH;

        if (!file_exists($tmpPath)) {
            mkdir($tmpPath, 0775, true);
        }

        $tmpFile = tempnam($tmpPath, 'tmp');

        $data = [
            ['data/config.php', 'data/config.php'],
            [realpath('vendor/autoload.php'), 'vendor/autoload.php'],
            [$tmpFile, $tmpPath . '/' . basename($tmpFile)],
            [realpath('application/Espo/Core'), 'application/Espo/Core'],
            [realpath('application/Espo/Core') . '/', 'application/Espo/Core/'],
            [realpath('application/Espo/Core/Application.php'), 'application/Espo/Core/Application.php'],
            ['C:\\espocrm\\data\\config.php', 'data\\config.php', 'C:\\espocrm', '\\'],
            ['C:espocrm\\data\\config.php', 'data\\config.php', 'C:espocrm', '\\'],
            ['C:\\espocrm\\data\\tmp\\' . basename($tmpFile), 'data\\tmp\\' . basename($tmpFile), 'C:\\espocrm', '\\'],
        ];

        @unlink($tmpFile);

        return $data;
    }

    #[DataProvider('relativePathData')]
    public function testGetRelativePath($path, $expectedResult, $basePath = null, $dirSeparator = null)
    {
        $this->assertEquals(
            Util::fixPath($expectedResult),
            $this->fileManager->getRelativePath($path, $basePath, $dirSeparator)
        );
    }
}
