<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014  Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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
 ************************************************************************/

namespace tests\Espo\Core\Upgrades;

use tests\ReflectionHelper;


class BaseTest extends \PHPUnit_Framework_TestCase
{
	protected $object;

	protected $objects;

	protected function setUp()
	{
		$this->objects['container'] = $this->getMockBuilder('\Espo\Core\Container')->disableOriginalConstructor()->getMock();

		$this->objects['config'] = $this->getMockBuilder('\Espo\Core\Utils\Config')->disableOriginalConstructor()->getMock();
		$this->objects['fileManager'] = $this->getMockBuilder('\Espo\Core\Utils\File\Manager')->disableOriginalConstructor()->getMock();

		$map = array(
		  array('config', $this->objects['config']),
		  array('fileManager', $this->objects['fileManager']),
		);

		$this->objects['container']
			->expects($this->any())
			->method('get')
			->will($this->returnValueMap($map));

		$this->object = new Base( $this->objects['container'] );

		$this->reflection = new ReflectionHelper($this->object);

		$this->reflection->setProperty('upgradeId', 'ngkdf54n566n45');
	}

	protected function tearDown()
	{
		$this->object = NULL;
	}

	public function testCreateUpgradeIdWithExists()
	{
		$this->setExpectedException('\Espo\Core\Exceptions\Error');

		$upgradeId = $this->reflection->invokeMethod('createUpgradeId', array());
		$this->assertEquals( $upgradeId, $this->reflection->invokeMethod('getUpgradeId', array()) );
	}


	public function testCreateUpgradeId()
	{
		$upgradeId = $this->reflection->setProperty('upgradeId', null);

		$upgradeId = $this->reflection->invokeMethod('createUpgradeId', array());
		$this->assertEquals( $upgradeId, $this->reflection->invokeMethod('getUpgradeId', array()) );
	}

	public function testGetUpgradeId()
	{
		$this->setExpectedException('\Espo\Core\Exceptions\Error');

		$this->reflection->setProperty('upgradeId', null);
		$this->reflection->invokeMethod('getUpgradeId', array());
	}


	public function testGetMainFileIncorrect()
	{
		$this->setExpectedException('\Espo\Core\Exceptions\Error');

		$mainFile = array(
			'name' => 'Upgrade 1.0-b3 to 1.0-b4',
		);

		$this->objects['fileManager']
			->expects($this->once())
			->method('getContents')
			->will($this->returnValue($mainFile));

		$this->reflection->invokeMethod('getMainFile', array());
	}


	public function testGetMainFile()
	{
		$mainFile = array(
			'name' => 'Upgrade 1.0-b3 to 1.0-b4',
			'version' => '1.0-b4',
			'acceptableVersions' =>array(
				'1.0-b3',
			),
			'releaseDate' => '2014-04-08',
			'author' => 'EspoCRM',
			'description' => 'Upgrade 1.0-b3 to 1.0-b4',
		);

		$this->objects['fileManager']
			->expects($this->once())
			->method('getContents')
			->will($this->returnValue($mainFile));

		$this->assertEquals( $mainFile, $this->reflection->invokeMethod('getMainFile', array()) );
	}

	/**
     * @dataProvider acceptableData
     */
	public function testIsAcceptable($version)
	{
		$this->objects['config']
			->expects($this->once())
			->method('get')
			->will($this->returnValue('11.5.2'));

		$this->reflection->setProperty('data', array('mainFile' => array('acceptableVersions' => $version)));
		$this->assertTrue( $this->reflection->invokeMethod('isAcceptable', array()) );
	}

	public function acceptableData()
    {
        return array(
          array( '11.5.2' ),
          array( array('11.5.2') ),
          array( array('1.4', '11.5.2') ),
          array( '11.*' ),
          array( '11\.*' ),
          array( '11.5*' ),
        );
    }


    /**
     * @dataProvider acceptableDataFalse
     */
	public function testIsAcceptableFalse($version)
	{
		$this->objects['config']
			->expects($this->once())
			->method('get')
			->will($this->returnValue('11.5.2'));

		$this->reflection->setProperty('data', array('mainFile' => array('acceptableVersions' => $version)));
		$this->assertFalse( $this->reflection->invokeMethod('isAcceptable', array()) );
	}

	public function acceptableDataFalse()
    {
        return array(
          array( '1.*' ),
        );
    }


    public function testGetUpgradePath()
    {
    	$upgradeId = $this->reflection->invokeMethod('getUpgradeId', array());
    	$upgradePath = 'tests/testData/Upgrades/data/upload/upgrades/'.$upgradeId;

    	$this->assertEquals( $upgradePath, $this->reflection->invokeMethod('getUpgradePath', array()) );

    	$postfix = $this->reflection->getProperty('packagePostfix');
    	$this->assertEquals( $upgradePath.$postfix, $this->reflection->invokeMethod('getUpgradePath', array(true)) );
    }






}

?>
