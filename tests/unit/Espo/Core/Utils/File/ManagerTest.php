<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2017 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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

namespace tests\unit\Espo\Core\Utils\File;
use tests\unit\ReflectionHelper;
use Espo\Core\Utils\Util;

class ManagerTest extends \PHPUnit_Framework_TestCase
{
    protected $object;

    protected $objects;

    protected $filesPath= 'tests/unit/testData/FileManager';
    protected $cachePath = 'tests/unit/testData/cache/FileManager';

    protected $reflection;

    protected function setUp()
    {
        $this->objects['config'] = $this->getMockBuilder('\Espo\Core\Utils\Config')->disableOriginalConstructor()->getMock();

        $this->object = new \Espo\Core\Utils\File\Manager();

        $this->reflection = new ReflectionHelper($this->object);
    }

    protected function tearDown()
    {
        $this->object = NULL;
    }

    public function testGetFileName()
    {
        $this->assertEquals('Donwload', $this->object->getFileName('Donwload.php'));

        $this->assertEquals('Donwload', $this->object->getFileName('/Donwload.php'));

        $this->assertEquals('Donwload', $this->object->getFileName('\Donwload.php'));

        $this->assertEquals('Donwload', $this->object->getFileName('application/Espo/EntryPoints/Donwload.php'));
    }

    public function testGetContents()
    {
        $result = file_get_contents($this->filesPath.'/getContent/test.json');
        $this->assertEquals($result, $this->object->getContents( array($this->filesPath, 'getContent/test.json') ));
    }

    public function testPutContents()
    {
        $testPath= $this->filesPath.'/setContent';

        $result= 'next value';
        $this->assertTrue($this->object->putContents(array($testPath, 'test.json'), $result));

        $this->assertEquals($result, $this->object->getContents( array($testPath, 'test.json')) );

        $this->assertTrue($this->object->putContents(array($testPath, 'test.json'), 'initial value'));
    }

    public function testConcatPaths()
    {
        $input = Util::fixPath('application/Espo/Resources/metadata/app/panel.json');
        $result = Util::fixPath('application/Espo/Resources/metadata/app/panel.json');

        $this->assertEquals($result, $this->reflection->invokeMethod('concatPaths', array($input)) );


        $input = array(
            'application',
            'Espo/Resources/metadata/',
            'app',
            'panel.json',
        );
        $result = Util::fixPath('application/Espo/Resources/metadata/app/panel.json');

        $this->assertEquals($result, $this->reflection->invokeMethod('concatPaths', array($input)) );


        $input = array(
            'application/Espo/Resources/metadata/app',
            'panel.json',
        );
        $result = Util::fixPath('application/Espo/Resources/metadata/app/panel.json');

        $this->assertEquals($result, $this->reflection->invokeMethod('concatPaths', array($input)) );


        $input = array(
            'application/Espo/Resources/metadata/app/',
            'panel.json',
        );
        $result = Util::fixPath('application/Espo/Resources/metadata/app/panel.json');

        $this->assertEquals($result, $this->reflection->invokeMethod('concatPaths', array($input)) );
    }

    public function testGetDirName()
    {
        $input = 'data/logs/espo.log';
        $result = 'logs';
        $this->assertEquals($result, $this->object->getDirName($input, false));

        $input = 'data/logs/espo.log/';
        $result = 'logs';
        $this->assertEquals($result, $this->object->getDirName($input, false));

        $input = 'application/Espo/Resources/metadata/entityDefs';
        $result = 'entityDefs';
        $this->assertEquals($result, $this->object->getDirName($input, false));

        $input = 'application/Espo/Resources/metadata/entityDefs/';
        $result = 'entityDefs';
        $this->assertEquals($result, $this->object->getDirName($input, false));

        //path doesn't exists. Be careful to use "/" at the beginning
        $input = '/application/Espo/Resources/metadata/entityDefs';
        $result = 'metadata';
        $this->assertEquals($result, $this->object->getDirName($input, false));

        $input = 'notRealPath/logs/espo.log';
        $result = 'logs';
        $this->assertEquals($result, $this->object->getDirName($input, false));

        $input = 'tests/unit/testData/FileManager/getContent';
        $result = 'getContent';
        $this->assertEquals($result, $this->object->getDirName($input, false));
    }

    public function testGetDirNameFullPath()
    {
        $input = 'data/logs/espo.log';
        $result = 'data/logs';
        $this->assertEquals($result, $this->object->getDirName($input));

        $input = 'data/logs/espo.log/';
        $result = 'data/logs';
        $this->assertEquals($result, $this->object->getDirName($input));

        $input = 'application/Espo/Resources/metadata/entityDefs';
        $result = 'application/Espo/Resources/metadata/entityDefs';
        $this->assertEquals($result, $this->object->getDirName($input));

        $input = 'application/Espo/Resources/metadata/entityDefs/';
        $result = 'application/Espo/Resources/metadata/entityDefs';
        $this->assertEquals($result, $this->object->getDirName($input));

        //path doesn't exists. Be careful to use "/" at the beginning
        $input = '/application/Espo/Resources/metadata/entityDefs';
        $result = '/application/Espo/Resources/metadata';
        $this->assertEquals($result, $this->object->getDirName($input));

        $input = 'notRealPath/logs/espo.log';
        $result = 'notRealPath/logs';
        $this->assertEquals($result, $this->object->getDirName($input));

        $input = 'tests/unit/testData/FileManager/getContent';
        $result = 'tests/unit/testData/FileManager/getContent';
        $this->assertEquals($result, $this->object->getDirName($input, true));
    }

    public function testUnsetContents()
    {
        $testPath = $this->filesPath.'/unsets/test.json';

        $initData = '{"fields":{"someName":{"type":"varchar","maxLength":40},"someName2":{"type":"varchar","maxLength":36}}}';
        $this->object->putContents($testPath, $initData);

        $unsets = 'fields.someName2';
        $this->assertTrue($this->object->unsetContents($testPath, $unsets));

        $result = '{"fields":{"someName":{"type":"varchar","maxLength":40}}}';
        $this->assertJsonStringEqualsJsonFile($testPath, $result);
    }

    public function testIsDirEmpty()
    {
        $this->assertFalse($this->object->isDirEmpty('application'));
        $this->assertFalse($this->object->isDirEmpty('tests/unit/Espo'));
        $this->assertFalse($this->object->isDirEmpty('tests/unit/Espo/Core/Utils/File'));

        $dirPath = 'tests/unit/testData/cache/EmptyDir';
        if (file_exists($dirPath) || mkdir($dirPath, 0755)) {
            $this->assertTrue($this->object->isDirEmpty($dirPath));
        }
    }

    public function testGetParentDirName()
    {
        $input = 'application/Espo/Resources/metadata/entityDefs';
        $result = 'metadata';
        $this->assertEquals($result, $this->object->getParentDirName($input, false));

        $input = 'application/Espo/Resources/metadata/entityDefs/';
        $result = 'metadata';
        $this->assertEquals($result, $this->object->getParentDirName($input, false));

        //path doesn't exists. Be careful to use "/" at the beginning
        $input = '/application/Espo/Resources/metadata/entityDefs';
        $result = 'metadata';
        $this->assertEquals($result, $this->object->getParentDirName($input, false));

        //path doesn't exists. Be careful to use "/" at the beginning
        $input = '/application/Espo/Resources/metadata/entityDefs';
        $result = '/application/Espo/Resources/metadata';
        $this->assertEquals($result, $this->object->getParentDirName($input));

        $input = 'notRealPath/logs/espo.log';
        $result = 'notRealPath/logs';
        $this->assertEquals($result, $this->object->getParentDirName($input));

        $input = 'tests/unit/testData/FileManager/getContent';
        $result = 'tests/unit/testData/FileManager';
        $this->assertEquals($result, $this->object->getParentDirName($input, true));
    }

    public function testGetSingeFileListAll()
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

        $this->assertEquals($result, $this->reflection->invokeMethod('getSingeFileList', array($input)));
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
            Util::fixPath('custom/Espo/Custom/Modules/ExtensionTest/File.json'),
            Util::fixPath('custom/Espo/Custom/Modules/ExtensionTest/File.php'),
        );

        $this->assertEquals($result, $this->reflection->invokeMethod('getSingeFileList', array($input, true)));
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

        $this->assertEquals($result, $this->reflection->invokeMethod('getSingeFileList', array($input, false)));
    }

    public function fileListSets()
    {
        return array(
          array( 'Set1', array(
                'custom',
                'custom/Espo',
                'custom/Espo/Custom',
                'custom/Espo/Custom/Modules',
                'custom/Espo/Custom/Modules/TestModule',
                'custom/Espo/Custom/Modules/TestModule/SubFolder',
                'custom/Espo/Custom/Modules/TestModule/SubFolder/Tester.txt',
            )
          ),

          array( 'Set2', array(
                'custom',
                'custom/Espo',
                'custom/Espo/Custom',
                'custom/Espo/Custom/Resources',
                'custom/Espo/Custom/Resources/metadata',
                'custom/Espo/Custom/Resources/metadata/entityDefs',
                'custom/Espo/Custom/Resources/metadata/entityDefs/Account.json',
            )
          ),

          array( 'Set3', array(
                'custom',
                'custom/test.file',
            )
          ),
        );
    }

    /**
     * @dataProvider fileListSets
     */
    public function testRemoveWithEmptyDirs($name, $result)
    {
        $path = 'tests/unit/testData/FileManager/Remove/' . $name;
        $cachePath = $this->cachePath . '/' . $name;

        $fileList = array (
            $cachePath . '/custom/Espo/Custom/Modules/ExtensionTest/File.json',
            $cachePath . '/custom/Espo/Custom/Modules/ExtensionTest/File.php',
        );
        $result = array_map('\Espo\Core\Utils\Util::fixPath', $result);

        $res = $this->object->copy($path, $cachePath, true);
        if ($res) {
            $this->assertTrue($this->object->remove($fileList, null, true));
            $this->assertEquals($result, $this->object->getFileList($cachePath, true, '', null, true));
        }
    }

    public function existsPathSet()
    {
        return array(
          array( 'application/Espo/Core/Application.php', 'application/Espo/Core/Application.php', ),
          array( 'application/Espo/Core/NotRealApplication.php', 'application/Espo/Core'),
          array( array('application', 'Espo/Core', 'NotRealApplication.php'), 'application/Espo/Core'),
          array( 'application/NoEspo/Core/Application.php', 'application'),
          array( 'notRealPath/Espo/Core/Application.php', '.'),
        );
    }

    /**
     * @dataProvider existsPathSet
     */
    public function testGetExistsPath($input, $result)
    {
        $result = Util::fixPath($result);
        $this->assertEquals($result, $this->reflection->invokeMethod('getExistsPath', array($input)) );
    }
}

