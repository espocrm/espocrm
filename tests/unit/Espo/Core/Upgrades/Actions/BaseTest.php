<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

namespace tests\unit\Espo\Core\Upgrades\Actions;
use tests\unit\ReflectionHelper;
use Espo\Core\Utils\Util;

class BaseTest extends \PHPUnit_Framework_TestCase
{
    protected $object;

    protected $objects;

    protected $fileManager;

    protected $reflection;

    protected $actionManagerParams = array(
        'name' => 'Extension',
        'packagePath' => 'tests/unit/testData/Upgrades/data/upload/extensions',
        'backupPath' => 'tests/unit/testData/Upgrades/data/.backup/extensions',

        'scriptNames' => array(
            'before' => 'BeforeInstall',
            'after' => 'AfterInstall',
            'beforeUninstall' => 'BeforeUninstall',
            'afterUninstall' => 'AfterUninstall',
        )
    );

    protected $currentVersion = '11.5.2';

    protected function setUp()
    {
        $this->objects['container'] = $this->getMockBuilder('\Espo\Core\Container')->disableOriginalConstructor()->getMock();
        $this->objects['actionManager'] = $this->getMockBuilder('\Espo\Core\Upgrades\ActionManager')->disableOriginalConstructor()->getMock();

        $this->objects['config'] = $this->getMockBuilder('\Espo\Core\Utils\Config')->disableOriginalConstructor()->getMock();
        $this->objects['fileManager'] = $this->getMockBuilder('\Espo\Core\Utils\File\Manager')->disableOriginalConstructor()->getMock();

        $GLOBALS['log'] = $this->getMockBuilder('\Espo\Core\Utils\Log')->disableOriginalConstructor()->getMock();

        $map = array(
          array('config', $this->objects['config']),
          array('fileManager', $this->objects['fileManager']),
        );

        $this->objects['container']
            ->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($map));

        $actionManagerParams = $this->actionManagerParams;
        $this->objects['actionManager']
            ->expects($this->once())
            ->method('getParams')
            ->will($this->returnValue($actionManagerParams));

        $this->object = new Base( $this->objects['container'], $this->objects['actionManager'] );

        $this->reflection = new ReflectionHelper($this->object);

        $this->reflection->setProperty('processId', 'ngkdf54n566n45');

        /* create a package durectory with manifest.json file */
        $packagePath = $this->reflection->invokeMethod('getPath');
        $manifestName = $this->reflection->getProperty('manifestName');
        $filename = $packagePath . '/' .$manifestName;

        $this->fileManager = new \Espo\Core\Utils\File\Manager();
        $this->fileManager->putContents($filename, '');
        /* END */
    }

    protected function tearDown()
    {
        $this->object = NULL;

        $processId = $this->reflection->getProperty('processId');
        if (isset($processId)) {
            $packagePath = $this->reflection->invokeMethod('getPath');
            $this->fileManager->removeInDir($packagePath, true);
        }
    }

    public function testCreateProcessIdWithExists()
    {
        $this->setExpectedException('\Espo\Core\Exceptions\Error');

        $processId = $this->reflection->invokeMethod('createProcessId', array());
    }

    public function testCreateProcessId()
    {
        $processId = $this->reflection->setProperty('processId', null);

        $processId = $this->reflection->invokeMethod('createProcessId');
        $this->assertEquals( $processId, $this->reflection->invokeMethod('getProcessId') );
    }

    public function testGetProcessId()
    {
        $this->setExpectedException('\Espo\Core\Exceptions\Error');

        $this->reflection->setProperty('processId', null);
        $this->reflection->invokeMethod('getProcessId');
    }

    public function testGetManifestIncorrect()
    {
        $this->setExpectedException('\Espo\Core\Exceptions\Error');

        $manifest = '{
            "name": "Upgrade 1.0-b3 to 1.0-b4"
        }';

        $this->objects['fileManager']
            ->expects($this->once())
            ->method('getContents')
            ->will($this->returnValue($manifest));

        $this->reflection->invokeMethod('getManifest', array());
    }

    public function testGetManifest()
    {
        $manifest = '{
            "name": "Extension Test",
            "version": "1.2.0",
            "acceptableVersions": [
            ],
            "releaseDate": "2014-09-25",
            "author": "EspoCRM",
            "description": "My Description"
        }';

        $this->objects['fileManager']
            ->expects($this->once())
            ->method('getContents')
            ->will($this->returnValue($manifest));

        $this->assertEquals( json_decode($manifest,true), $this->reflection->invokeMethod('getManifest') );
    }

    public function acceptableVersions()
    {
        return array(
          array( '11.5.2' ),
          array( array('11.5.2') ),
          array( array('1.4', '11.5.2')),
          array( '11.*', ),
          array( '11.5.*', ),
          array( '~11.5', ),
          array( '~11', ),
          array( '^11.1', ),
          array( '^11', ),
          array( '11.1 - 11.9', ),
          array( '>=11.1', ),
          array( '<=12', ),
          array( '>=11 <=12', ),
        );
    }

    /**
     * @dataProvider acceptableVersions
     */
    public function testCheckVersions($versions, $currentVersion = null)
    {
        if (!isset($currentVersion)) {
            $currentVersion = $this->currentVersion;
        }

        $this->assertTrue( $this->reflection->invokeMethod('checkVersions', array($versions, $currentVersion, 'error') ) );
    }

    public function unacceptableVersions()
    {
        return array(
          array( '1.*', ),
          array( '11\.*', ),
          array( '11\.5\.2', ),
          array( '11.5*', ),
          array( '11.1-11.9', ),
          array( '.0.1' ),
        );
    }

    /**
     * @dataProvider unacceptableVersions
     */
    public function testCheckVersionsException($versions, $currentVersion = null)
    {
        if (!isset($currentVersion)) {
            $currentVersion = $this->currentVersion;
        }

        $this->setExpectedException('\Espo\Core\Exceptions\Error');

        $this->reflection->invokeMethod('checkVersions', array($versions, $currentVersion, 'error'));
    }

    public function testIsAcceptableEmpty()
    {
        $version = array();

        $this->reflection->setProperty('data', array('manifest' => array('acceptableVersions' => $version)));
        $this->assertTrue( $this->reflection->invokeMethod('isAcceptable') );
    }

    public function testGetPath()
    {
        $packageId = $this->reflection->invokeMethod('getProcessId');
        $packagePath = Util::fixPath($this->actionManagerParams['packagePath'] . '/' . $packageId);

        $this->assertEquals( $packagePath, $this->reflection->invokeMethod('getPath', array()) );
        $this->assertEquals( $packagePath, $this->reflection->invokeMethod('getPath', array('packagePath')) );

        $postfix = $this->reflection->getProperty('packagePostfix');
        $this->assertEquals( $packagePath.$postfix, $this->reflection->invokeMethod('getPath', array('packagePath', true)) );

        $backupPath = Util::fixPath($this->actionManagerParams['backupPath'] . '/' . $packageId);

        $this->assertEquals( $backupPath, $this->reflection->invokeMethod('getPath', array('backupPath')) );
    }

    public function testCheckPackageType()
    {
        $this->reflection->setProperty('data', array('manifest' => array()));
        $this->assertTrue( $this->reflection->invokeMethod('checkPackageType') );

        $this->reflection->setProperty('data', array('manifest' => array('type' => 'extension')));
        $this->assertTrue( $this->reflection->invokeMethod('checkPackageType') );
    }

    public function testCheckPackageTypeUpgrade()
    {
        $this->setExpectedException('\Espo\Core\Exceptions\Error');

        $this->reflection->setProperty('data', array('manifest' => array('type' => 'upgrade')));
        $this->assertTrue( $this->reflection->invokeMethod('checkPackageType') );
    }
}

?>
